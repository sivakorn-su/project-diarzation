<?php
namespace App\Jobs;

use App\Models\MeetingInfo;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\TranscriptSegments;

class ProcessMeetingTranscript implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;            // retry 3 รอบ
    public int $timeout = 1200;       // 20 นาทีพอ (ตัดใจได้)

    public function __construct(public int $meetingInfoId) {}

    public function middleware(): array
    {
        // กันซ้อนงานตัวเดียวกัน
        return [ new WithoutOverlapping("meeting-transcript:{$this->meetingInfoId}") ];
    }

    public function backoff(): array { return [5, 15, 60]; }

    public function handle(): void
    {
        $meetingInfo = MeetingInfo::findOrFail($this->meetingInfoId);
        if (!$meetingInfo->media_paths) {
            throw new \RuntimeException('No media file found.');
        }

        $mediaUrl = $meetingInfo->media_paths;

        // เตรียม temp
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
        $filename = basename(parse_url($mediaUrl, PHP_URL_PATH) ?: ('media_'.uniqid().'.bin'));
        $tempPath = $tempDir . '/' . $filename;

        // ดาวน์โหลดด้วย retry manual
        $downloaded = false;
        for ($i=0; $i<3 && !$downloaded; $i++) {
            try {
                Http::timeout(120)->sink($tempPath)->get($mediaUrl);
                if (file_exists($tempPath) && filesize($tempPath) > 0) {
                    $downloaded = true;
                } else {
                    usleep(300000);
                }
            } catch (\Throwable $e) {
                usleep(300000);
            }
        }
        if (!$downloaded) {
            throw new \RuntimeException('Download failed after retries.');
        }

        // อัพโหลดไป HF space
        $client = new Guzzle();
        $url = env('MODEL_TRANSCRIPTS', 'https://inwneon-project-voice-diarzation.hf.space/upload_video/');
        $resp = $client->request('POST', $url, [
            'multipart' => [[
                'name' => 'file',
                'contents' => fopen($tempPath, 'r'),
                'filename' => $filename,
            ]],
            'timeout' => 300,
        ]);

        @unlink($tempPath);

        $result = json_decode((string)$resp->getBody(), true);
        if (!isset($result['data']) || !is_array($result['data']) || !count($result['data'])) {
            throw new \RuntimeException('No transcript data received.');
        }

        // อัพเดตข้อมูล transcript
        $data = $result['data'] ?? [];
        if (!is_array($data)) $data = [];
        DB::transaction(function () use ($meetingInfo, $result, $data) {

            // ล้างของเก่าก่อน (ถ้าอยาก keep เดิม เปลี่ยนเป็น upsert ด้านล่าง)
            TranscriptSegment::where('meeting_info_id', $meetingInfo->id)->delete();
        
            // วนสร้างทีละ segment ด้วย Eloquent::create()
            foreach ($data as $i => $seg) {
                TranscriptSegment::create([
                    'meeting_info_id'    => $meetingInfo->id,
                    'idx'                => $i,
                    'start'              => $seg['start'] ?? null,
                    'end'                => $seg['end'] ?? null,
                    'text'               => $seg['text'] ?? null,
                    'llm_corrected_text' => $seg['llm_corrected_text'] ?? null,
                    'speaker'            => $seg['speaker'] ?? null,
                    'filename'           => $seg['filename'] ?? null,
                    'avg_probability'    => $seg['avg_probability'] ?? null,
                    'confidence'         => $seg['confidence'] ?? null,
                    'tag'                => $seg['tag'] ?? null,
                    'remove_reason'      => $seg['remove_reason'] ?? null,
                    'has_overlap'        => $seg['has_overlap'] ?? false,
                    'overlap_ratio'      => $seg['overlap_ratio'] ?? null,
                    'overlap_intervals'  => $seg['overlap_intervals'] ?? null,
                ]);
            }
            $meetingInfo->update([
                'status' => 'done',
            ]);
        });
    }

    public function failed(\Throwable $e): void
    {
        if ($meetingInfo = MeetingInfo::find($this->meetingInfoId)) {
            $meetingInfo->update(['status' => 'failed']);
        }
        // เขียน error ลง field อื่นหรือ log ตามสะดวก
        \Log::error('ProcessMeetingTranscript failed', [
            'meeting_info_id' => $this->meetingInfoId,
            'error' => $e->getMessage(),
        ]);
    }
}
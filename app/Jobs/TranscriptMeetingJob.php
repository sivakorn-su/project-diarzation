<?php

namespace App\Jobs;

use App\Models\MeetingInfo;
use App\Models\TranscriptSegments;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client as Guzzle;

class TranscriptMeetingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600;   // 1 ชม.
    public bool $failOnTimeout = true;

    public function __construct(public int $meetingInfoId)
    {
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping("meeting-transcript:{$this->meetingInfoId}")];
    }

    public function handle(): void
    {
        $meetingInfo = MeetingInfo::find($this->meetingInfoId);

        // 1) สร้าง/เลือก URL สำหรับส่งให้ FastAPI
        $presignedUrl = null;

        // ถ้ามี object key (private) → ออกลิงก์ชั่วคราวจาก R2/S3
        if ($meetingInfo->media_object_key) {
            try {
                // หมายเหตุ: ใช้ได้เมื่อ disks.s3 ตั้งค่า key/secret/endpoint ถูกต้อง
                $presignedUrl = Storage::disk('s3')->temporaryUrl(
                    $meetingInfo->media_object_key,
                    now()->addHours(1)
                );
            } catch (\Throwable $e) {
                Log::warning('temporaryUrl failed, fallback to media_paths', [
                    'meeting_info_id' => $meetingInfo->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ถ้าออก presigned ไม่ได้ และมี media_paths (public URL เดิม) → ใช้ต่อ
        if (!$presignedUrl && $meetingInfo->media_paths) {
            $presignedUrl = $meetingInfo->media_paths;
        }

        if (!$presignedUrl) {
            throw new \RuntimeException('No media URL to send to FastAPI.');
        }

        // 2) ยิงไป FastAPI แบบ JSON { url: "<presigned>" } แล้ว "รอ" ผลลัพธ์กลับมา
        $client = new Guzzle([
            'timeout' => 3600,
            'connect_timeout' => 30,
            'http_errors' => false,
            'allow_redirects' => false,
        ]);

        $api = rtrim(env('MODEL_TRANSCRIPTS', 'https://inwneon-project-voice-diarzation.hf.space'), '/')
            . '/upload_video/';

        // if (!$this->warmHfSpace($api, 25)) {
        //     $this->release(60); return;
        // }

        $resp = $client->post($api, [
            'headers' => ['Accept' => 'application/json'],
            'json' => [
                'url' => $presignedUrl
            ],
        ]);

        $status = $resp->getStatusCode();
        $body = (string) $resp->getBody();

        if ($status < 200 || $status >= 300) {
            // โยน error พร้อม body (ช่วย debug 422/500)
            throw new \RuntimeException("Transcribe API HTTP {$status}: " . substr($body, 0, 1000));
        }

        $result = json_decode($body, true);
        if (!isset($result['data']) || !is_array($result['data']) || !count($result['data'])) {
            throw new \RuntimeException('No transcript data received.');
        }

        $data = $result['data'];

        // 3) เขียนผลลัพธ์ลง DB
        DB::transaction(function () use ($meetingInfo, $data, $result) {
            TranscriptSegments::where('meeting_info_id', $meetingInfo->id)->delete();

            foreach ($data as $i => $seg) {
                TranscriptSegments::create([
                    'meeting_info_id' => $meetingInfo->id,
                    'idx' => $i,
                    'start' => $seg['start'] ?? null,
                    'end' => $seg['end'] ?? null,
                    'text' => $seg['text'] ?? null,
                    'llm_corrected_text' => $seg['llm_corrected_text'] ?? null,
                    'speaker' => $seg['speaker'] ?? null,
                    'filename' => $seg['filename'] ?? null,
                    'avg_probability' => $seg['avg_probability'] ?? null,
                    'confidence' => $seg['confidence'] ?? null,
                    'tag' => $seg['tag'] ?? null,
                    'is_remove' => $seg['is_remove'] ?? false,
                    'remove_reason' => $seg['remove_reason'] ?? null,
                    'has_overlap' => $seg['has_overlap'] ?? false,
                    'overlap_ratio' => $seg['overlap_ratio'] ?? null,
                    'overlap_intervals' => $seg['overlap_intervals'] ?? null,
                ]);
            }

            $meetingInfo->update([
                'status' => 'done',
                'summaries' => $result['summaries'] ?? null,
            ]);
        });
    }

    public function failed(\Throwable $e): void
    {
        if ($meetingInfo = MeetingInfo::find($this->meetingInfoId)) {
            $meetingInfo->update(['status' => 'failed']);
        }
        Log::error('ProcessMeetingTranscript failed', [
            'meeting_info_id' => $this->meetingInfoId,
            'error' => $e->getMessage(),
        ]);
    }

    private function warmHfSpace(string $baseUrl, int $maxWaitSec = 25): bool
    {
        $deadline = time() + $maxWaitSec;
        $delay = 2;
        while (time() < $deadline) {
            try {
                $r = Http::withHeaders(['User-Agent' => 'zenitcomp-worker/1.0'])
                    ->timeout(8)->connectTimeout(5)->withOptions(['verify' => false])
                    ->get($baseUrl);
                if ($r->successful())
                    return true;
                if (in_array($r->status(), [502, 503, 504], true)) {
                    sleep($delay);
                    $delay = min($delay * 2, 8);
                    continue;
                }
                return false; // 4xx = ไม่ใช่ภาวะหลับ
            } catch (\Throwable $e) {
                sleep($delay);
                $delay = min($delay * 2, 8);
            }
        }
        return false;
    }

}

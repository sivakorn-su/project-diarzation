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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client as Guzzle;

class ProcessMeetingTranscript implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600;   // 1 ชม.
    public bool $failOnTimeout = true;

    public function __construct(public int $meetingInfoId, public ?string $presignedUrl = null) {}

    public function middleware(): array
    {
        return [ new WithoutOverlapping("meeting-transcript:{$this->meetingInfoId}") ];
    }

    public function handle(): void
    {
        $meetingInfo = MeetingInfo::findOrFail($this->meetingInfoId);

        // ✅ เลือกแหล่งดาวน์โหลด: key ก่อน, ถ้าไม่มีค่อย fallback ไป URL
        $key = $meetingInfo->media_object_key;
        $mediaUrl = $meetingInfo->media_paths; // dev/public URL (ถ้ามี)
        $urlForName = $this->presignedUrl ?: $mediaUrl;

        // เตรียม temp path
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

        $filename = $key
            ? basename($key)
            : (basename(parse_url($urlForName, PHP_URL_PATH) ?: ('media_'.uniqid().'.bin')));

        $tempPath = $tempDir . '/' . $filename;

        $downloadViaHttp = function (string $url) use ($tempPath): bool {
            for ($i = 0; $i < 3; $i++) {
                try {
                    Http::timeout(300)->sink($tempPath)->get($url);
                    if (file_exists($tempPath) && filesize($tempPath) > 0) {
                        return true;
                    }
                    @unlink($tempPath);
                } catch (\Throwable $e) {
                    @unlink($tempPath);
                }
                usleep(300000);
            }

            return false;
        };

        // ===== ดาวน์โหลดไฟล์แบบ Stream จาก R2 (แนะนำ) =====
        $downloaded = false;
        if ($this->presignedUrl) {
            $downloaded = $downloadViaHttp($this->presignedUrl);
        }

        if (!$downloaded && $key) {
            $in = Storage::disk('s3')->readStream($key);
            if ($in === false) {
                throw new \RuntimeException("Cannot open R2 object stream: {$key}");
            }
            $out = fopen($tempPath, 'w');
            if ($out === false) {
                if (is_resource($in)) fclose($in);
                throw new \RuntimeException("Cannot open temp file for write: {$tempPath}");
            }
            stream_copy_to_stream($in, $out);
            if (is_resource($in)) fclose($in);
            if (is_resource($out)) fclose($out);
            $downloaded = file_exists($tempPath) && filesize($tempPath) > 0;
        }

        // ===== Fallback: ดาวน์โหลดผ่าน URL (เช่น public dev URL) =====
        if (!$downloaded && $mediaUrl) {
            $downloaded = $downloadViaHttp($mediaUrl);
        }

        if (!$downloaded) {
            throw new \RuntimeException('Download failed (R2 stream and URL fallback).');
        }

        // ===== ส่งไปยังบริการถอดเสียง (HF Space) แบบ multipart stream =====
        $client = new Guzzle([
            'timeout' => 3600,
            'read_timeout' => 3600,
            'connect_timeout' => 30,
            'allow_redirects' => false,
            'http_errors' => false,
        ]);

        $url = rtrim(env('MODEL_TRANSCRIPTS', 'https://inwneon-project-voice-diarzation.hf.space'), '/') . '/upload_video/';

        try {
            $resp = $client->request('POST', $url, [
                'multipart' => [[
                    'name'     => 'file',
                    'contents' => fopen($tempPath, 'r'),
                    'filename' => $filename,
                ]],
            ]);
        } finally {
            @unlink($tempPath);
        }

        $status = $resp->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException("Transcribe API HTTP {$status}: " . substr((string)$resp->getBody(), 0, 500));
        }

        $result = json_decode((string)$resp->getBody(), true);
        if (!isset($result['data']) || !is_array($result['data']) || !count($result['data'])) {
            throw new \RuntimeException('No transcript data received.');
        }

        $data = $result['data'];

        // ===== เขียนผลลัพธ์ลง DB =====
        DB::transaction(function () use ($meetingInfo, $data) {
            TranscriptSegments::where('meeting_info_id', $meetingInfo->id)->delete();

            foreach ($data as $i => $seg) {
                TranscriptSegments::create([
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
                    'is_remove'          => $seg['is_remove'] ?? false,
                    'remove_reason'      => $seg['remove_reason'] ?? null,
                    'has_overlap'        => $seg['has_overlap'] ?? false,
                    'overlap_ratio'      => $seg['overlap_ratio'] ?? null,
                    'overlap_intervals'  => $seg['overlap_intervals'] ?? null,
                ]);
            }

            $meetingInfo->update(['status' => 'done']);
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
}

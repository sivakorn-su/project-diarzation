<?php

namespace App\Jobs;

use App\Models\Transcript;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTranscript implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 1200;

    public function backoff(): array { return [5,15,60]; }
    public function middleware(): array { return [new WithoutOverlapping("transcript:{$this->transcriptId}")]; }

    public function __construct(public int $transcriptId) {}

    public function handle(): void
    {
        $t = Transcript::findOrFail($this->transcriptId);
        $t->update(['status' => 'processing']);

        $mediaUrl = $t->media_path;
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
        $tmp = $tempDir.'/'.uniqid('media_');

        $ok = false;
        for ($i=0;$i<3 && !$ok;$i++) {
            try {
                Http::timeout(120)->sink($tmp)->get($mediaUrl);
                if (file_exists($tmp) && filesize($tmp) > 0) $ok = true;
            } catch (\Throwable $e) { usleep(300000); }
        }
        if (!$ok) throw new \RuntimeException('Download media failed.');

        $client = new Guzzle();
        $resp = $client->request('POST',
            'https://inwneon-project-voice-diarzation.hf.space/upload_video/',
            ['multipart' => [[
                'name' => 'file', 'contents' => fopen($tmp,'r'), 'filename' => basename($tmp).'.bin',
            ]]]
        );
        @unlink($tmp);

        $result = json_decode((string)$resp->getBody(), true);
        if (!isset($result['data']) || !is_array($result['data']) || !count($result['data'])) {
            throw new \RuntimeException('No transcript data received.');
        }

        DB::transaction(function () use ($t,$result) {
            $t->update([
                'status'          => 'done',
                'transcript_json' => $result, // เก็บทั้งก้อน (ไม่ใช้ค่านับจากนี่เวลาตอบ)
            ]);

            $t->segments()->delete();
            $rows = [];
            foreach ($result['data'] as $item) {
                $rows[] = [
                    'transcript_id'      => $t->id,
                    'speaker'            => $item['speaker'] ?? null,
                    'filename'           => $item['filename'] ?? null,
                    'start'              => isset($item['start']) ? (float)$item['start'] : null,
                    'end'                => isset($item['end']) ? (float)$item['end'] : null,
                    'avg_probability'    => isset($item['avg_probability']) ? (float)$item['avg_probability'] : null,
                    'text'               => $item['text'] ?? null,
                    'llm_corrected_text' => $item['llm_corrected_text'] ?? null,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }
            TranscriptSegment::insert($rows);
        });
    }

    public function failed(\Throwable $e): void
    {
        Transcript::whereKey($this->transcriptId)->update(['status' => 'failed']);
    }
}
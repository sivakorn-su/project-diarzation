<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MeetingInfo;
use App\Models\TranscriptSegments;

class MigrateTranscriptSegments extends Command
{
    protected $signature = 'transcripts:segments:migrate
        {--from-id=0 : เริ่มจาก meeting_infos.id เท่านี้ขึ้นไป}
        {--limit=0 : จำกัดจำนวนเรคคอร์ด meeting_infos ที่จะ migrate (0 = ทั้งหมด)}
        {--chunk=50 : ขนาดชุด chunkById}
        {--clear-old : เคลียร์ transcript_segments ของ meeting นั้น ๆ ก่อน (replace)}
        {--append : ไม่ลบเดิม เพิ่ม/ทับตาม (meeting_id,idx)}
        {--dry-run : แค่สแกน/สรุป ไม่เขียน DB}
        {--nullify-json : เซ็ต meeting_infos.transcript_json = NULL หลัง migrate (ประหยัดที่)}
    ';

    protected $description = 'Migrate meeting_infos.transcript_json -> transcript_segments (no queue, run inline)';

    public function handle(): int
    {
        $fromId      = (int) $this->option('from-id');
        $limit       = (int) $this->option('limit');
        $chunkSize   = (int) $this->option('chunk');
        $clearOld    = (bool) $this->option('clear-old');
        $append      = (bool) $this->option('append');
        $dryRun      = (bool) $this->option('dry-run');
        $nullifyJson = (bool) $this->option('nullify-json');

        if ($clearOld && $append) {
            $this->error('--clear-old และ --append เลือกอย่างใดอย่างหนึ่ง');
            return self::FAILURE;
        }

        $q = MeetingInfo::query()
            ->whereNotNull('transcript_json')
            ->when($fromId > 0, fn($w) => $w->where('id', '>=', $fromId))
            ->orderBy('id');

        $total = (clone $q)->count();
        if ($limit > 0) $total = min($total, $limit);

        $this->info("Total meeting_infos to process: {$total}");
        $processed = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $remaining = $limit > 0 ? $limit : PHP_INT_MAX;

        $q->chunkById($chunkSize, function ($infos) use (
            &$processed, &$remaining, $bar, $clearOld, $append, $dryRun, $nullifyJson
        ) {
            foreach ($infos as $info) {
                if ($remaining <= 0) break;

                $payload = $this->decodeSafe($info->transcript_json);
                if (!$payload) {
                    $this->warn("\nSkipping id={$info->id} (invalid JSON)");
                    $bar->advance();
                    $processed++;
                    $remaining--;
                    continue;
                }

                $data = $payload['data'] ?? [];
                if (!is_array($data)) $data = [];

                try {
                    DB::transaction(function () use ($info, $data, $clearOld, $append, $dryRun, $nullifyJson) {

                        if ($clearOld && !$dryRun) {
                            TranscriptSegments::where('meeting_info_id', $info->meeting_id)->delete();
                        }

                        // สร้าง rows
                        $now = now();
                        $rows = [];
                        foreach ($data as $i => $seg) {
                            $rows[] = [
                                'meeting_info_id'    => $info->meeting_id,
                                'idx'                => $i,
                                'start'              => $seg['start'] ?? null,
                                'end'                => $seg['end'] ?? null,
                                'text'               => $seg['text'] ?? null,
                                'llm_corrected_text' => $seg['llm_corrected_text'] ?? null,
                                'speaker'            => $seg['speaker'] ?? null,
                                'filename'           => $seg['filename'] ?? null,
                                'avg_probability'    => $seg['avg_probability'] ?? null,
                                'created_at'         => $now,
                                'updated_at'         => $now,
                            ];
                        }

                        if (!$dryRun && !empty($rows)) {
                            if ($append) {
                                // ทับ/เพิ่มตาม unique key (meeting_id, idx)
                                TranscriptSegments::upsert(
                                    $rows,
                                    ['meeting_info_id', 'idx'],
                                    ['start','end','text','llm_corrected_text','speaker','filename','avg_probability','updated_at']
                                );
                            } else {
                                // replace mode แต่เฉพาะ meeting นี้ (แนะนำใช้คู่กับ --clear-old)
                                foreach (array_chunk($rows, 1000) as $chunk) {
                                    TranscriptSegments::insert($chunk);
                                }
                            }
                        }

                        if ($nullifyJson && !$dryRun) {
                            $info->update(['transcript_json' => null]);
                        }
                    });
                } catch (\Throwable $e) {
                    $this->error("\nError at meeting_infos.id={$info->id}: {$e->getMessage()}");
                }

                $bar->advance();
                $processed++;
                $remaining--;
                if ($remaining <= 0) break;
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Done. processed={$processed}");

        if ($dryRun) $this->comment('Dry-run: no DB writes performed.');
        return self::SUCCESS;
    }

    private function decodeSafe($json)
    {
        if (is_array($json)) return $json;
        if (is_string($json)) {
            try {
                return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                return json_decode($json, true) ?: null;
            }
        }
        return null;
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transcript;
use App\Models\TranscriptSegments as TranscriptSegment;

class TranscriptSeeder extends Seeder
{
    public function run(): void
    {
    
        // สร้าง transcripts ปลอม 5 อัน
        for ($i = 1; $i <= 5; $i++) {
            $t = Transcript::create([
                'title'          => "Sample Transcript #{$i}",
                'media_path'     => "https://example.com/media/sample{$i}.mp4",
                'storage_driver' => 'public',
                'status'         => fake()->randomElement(['pending', 'processing', 'done', 'failed']),
                'transcript_json'=> null, // เวลาจริงจะเก็บ JSON เต็ม
            ]);

            // สุ่มจำนวน speaker
            $speakers = collect(['SPEAKER_00','SPEAKER_01','SPEAKER_02'])->random(rand(1,3));

            // สร้าง segments ปลอม 5–10 อัน
            $numSegments = rand(5, 10);
            $start = 0;
            for ($j = 1; $j <= $numSegments; $j++) {
                $dur = fake()->randomFloat(3, 2.0, 8.0); // ความยาว segment 2-8 วิ
                $end = $start + $dur;

                TranscriptSegment::create([
                    'transcript_id'      => $t->id,
                    'speaker'            => $speakers instanceof \Illuminate\Support\Collection ? $speakers->random() : $speakers,
                    'filename'           => "segment_".str_pad($j,3,'0',STR_PAD_LEFT).".wav",
                    'start'              => $start,
                    'end'                => $end,
                    'avg_probability'    => fake()->randomFloat(4, 0.7, 0.95),
                    'text'               => fake()->sentence(12),
                    'llm_corrected_text' => fake()->sentence(12),
                ]);

                $start = $end + fake()->randomFloat(3, 0.5, 2.0); // เว้นช่วง
            }
        }
    }
}

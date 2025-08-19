<?php
// app/Http/Resources/MeetingResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
{
    public function toArray($request)
    {
        $info = $this->whenLoaded('info');
        // ถ้าไม่โหลดความสัมพันธ์ segments มาก็ให้เป็น collection ว่าง
        $segments = ($info && $info->relationLoaded('segments')) ? $info->segments : collect();

        // map segments -> array ที่ FE ใช้ได้เลย
        $segmentsArray = $segments->map(function ($s) {
            return [
                'end'                => is_null($s->end) ? null : (float) $s->end,
                'text'               => $s->text,
                'start'              => is_null($s->start) ? null : (float) $s->start,
                'speaker'            => $s->speaker,
                'filename'           => $s->filename,
                'avg_probability'    => is_null($s->avg_probability) ? null : (float) $s->avg_probability,
                'llm_corrected_text' => $s->llm_corrected_text ?? '',
                'id'                => $s->id,
            ];
        })->values()->all();

        // ถ้ามี segments ค่อยคำนวณสรุป
        $hasSeg = count($segmentsArray) > 0;

        // helper ลบคีย์ที่เป็น null ออก (อยากมินิมอล)
        $stripNulls = fn (array $a) => array_filter($a, fn($v) => !is_null($v));

        // สรุป (คำนวณก็ต่อเมื่อมี segments)
        $audioLength   = $hasSeg ? (float) ($segments->max('end') ?? 0) : null;
        $speakersCol   = $hasSeg ? $segments->pluck('speaker')->filter()->unique()->values() : collect();
        $numSpeakers   = $hasSeg ? $speakersCol->count() : null;
        $totalSentence = $hasSeg ? $segments->count() : null;
        $countSpeaker  = $hasSeg
            ? $segments->groupBy('speaker')->map(fn($g) => [
                'speaker' => $g->first()->speaker,
                'count'   => $g->count(),
              ])->values()->all()
            : null;

        // base transcript_json: data เท่านั้น
        $transcript = ['data' => $segmentsArray];

        // ถ้ามี segments ค่อยใส่ฟิลด์อื่น (และตัด null ออก)
        if ($hasSeg) {
            $transcript = $stripNulls(array_merge($transcript, [
                'audio_path'     => $info->audio_path ?? null,
                'video_path'     => $info->video_path ?? null,
                'audio_length'   => $audioLength,
                'num_speakers'   => $numSpeakers,
                'count_speaker'  => $countSpeaker,
                'speaker_array'  => $speakersCol->all(),
                'total_sentence' => $totalSentence,
                'summaries' => '-'
            ]));
        }

        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'start_date' => optional($this->start_date)->toJSON(),
            'end_date'   => optional($this->end_date)->toJSON(),
            'level'      => $this->level,
            'user_id'    => $this->user_id,
            'created_at' => optional($this->created_at)->toJSON(),
            'updated_at' => optional($this->updated_at)->toJSON(),

            'user' => $this->whenLoaded('user', fn() => [
                'id'                => $this->user->id,
                'name'              => $this->user->name,
                'email'             => $this->user->email,
                'email_verified_at' => optional($this->user->email_verified_at)->toJSON(),
                'created_at'        => optional($this->user->created_at)->toJSON(),
                'updated_at'        => optional($this->user->updated_at)->toJSON(),
            ]),

            'info' => $this->when($info, function () use ($info, $transcript) {
                return [
                    'id'          => $info->id,
                    'meeting_id'  => $info->meeting_id,
                    'description' => $info->description,
                    'media_paths' => $info->media_paths,
                    'status'      => $info->status,
                    'created_at'  => optional($info->created_at)->toJSON(),
                    'updated_at'  => optional($info->updated_at)->toJSON(),
                    'transcript_json' => $transcript,
                ];
            }),
        ];
    }
}

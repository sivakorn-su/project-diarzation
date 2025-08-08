<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TranscriptSegments as TranscriptSegment;

class Transcript extends Model
{
    /** @use HasFactory<\Database\Factories\TranscriptFactory> */
    use HasFactory;

    protected $fillable = ['title','media_path','storage_driver','status','transcript_json'];
    protected $casts = ['transcript_json' => 'array'];

    // auto แนบ 3 ค่านี้ทุก response (คำนวณสดจาก segments)
    protected $appends = ['num_speakers','count_speaker','total_sentence'];

    public function segments()
    {
        return $this->hasMany(TranscriptSegment::class);
    }

    public function getNumSpeakersAttribute(): int
    {
        if ($this->relationLoaded('segments')) {
            return $this->segments->pluck('speaker')->filter()->unique()->count();
        }
        return (int) $this->segments()->whereNotNull('speaker')->distinct('speaker')->count('speaker');
    }

    public function getTotalSentenceAttribute(): int
    {
        if ($this->relationLoaded('segments')) {
            return $this->segments->count();
        }
        return (int) $this->segments()->count();
    }

    public function getCountSpeakerAttribute(): array
    {
        if ($this->relationLoaded('segments')) {
            $counts = $this->segments->groupBy('speaker')->map(fn($g,$spk)=>[
                'speaker'=>$spk,'count'=>$g->count(),
            ])->values()->all();
        } else {
            $counts = $this->segments()
                ->selectRaw('speaker, COUNT(*) as count')
                ->groupBy('speaker')
                ->get()
                ->map(fn($r)=>['speaker'=>$r->speaker,'count'=>(int)$r->count])
                ->all();
        }

        usort($counts, function($a,$b){
            if ($a['speaker']===$b['speaker']) return 0;
            if ($a['speaker']===null) return 1;
            if ($b['speaker']===null) return -1;
            return strcmp($a['speaker'],$b['speaker']);
        });
        return $counts;
    }


}

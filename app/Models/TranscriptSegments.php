<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranscriptSegments extends Model
{
    protected $fillable = [
        'transcript_id','speaker','filename','start','end','avg_probability','text','llm_corrected_text'
    ];

    public function transcript()
    {
        return $this->belongsTo(Transcript::class);
    }
}

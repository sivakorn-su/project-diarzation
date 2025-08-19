<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranscriptSegments extends Model
{
    protected $fillable = [
        'meeting_info_id','idx','start','end','text','llm_corrected_text',
        'speaker','filename','avg_probability'
    ];

    protected $casts = [
        'start' => 'decimal:3',
        'end'   => 'decimal:3',
        'avg_probability' => 'decimal:4',
    ];

    public function meetingInfo() {
        return $this->belongsTo(MeetingInfo::class, 'meeting_info_id');
    }
}

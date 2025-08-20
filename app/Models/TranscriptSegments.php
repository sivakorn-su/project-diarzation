<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranscriptSegments extends Model
{
    protected $fillable = [
        'meeting_info_id',
        'transcript_id',
        'idx',
        'start',
        'end',
        'text',
        'llm_corrected_text',
        'speaker',
        'filename',
        'avg_probability',
        'confidence',
        'tag',
        'remove_reason',
        'has_overlap',
        'overlap_ratio',
        'overlap_intervals',
    ];

    protected $casts = [
        'start' => 'decimal:3',
        'end'   => 'decimal:3',
        'avg_probability' => 'decimal:4',
        'confidence'        => 'float',
        'has_overlap'       => 'boolean',
        'overlap_ratio'     => 'float',
        'overlap_intervals' => 'array',
    ];

    public function meetingInfo() {
        return $this->belongsTo(MeetingInfo::class, 'meeting_info_id');
    }
}

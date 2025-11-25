<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranscriptSegments extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'is_remove',
        'remove_reason',
        'has_overlap',
        'overlap_ratio',
        'overlap_intervals',
        'overlap_detail'
    ];

    protected $casts = [
        'start' => 'decimal:3',
        'end' => 'decimal:3',
        'avg_probability' => 'decimal:4',
        'confidence' => 'float',
        'has_overlap' => 'boolean',
        'overlap_ratio' => 'float',
        'overlap_intervals' => 'array',
        'is_remove' => 'boolean',
        'overlap_detail' => 'array',
        'remove_reason' => 'string',
    ];

    public function meetingInfo()
    {
        return $this->belongsTo(MeetingInfo::class, 'meeting_info_id');
    }
}

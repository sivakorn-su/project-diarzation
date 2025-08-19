<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingInfo extends Model
{
    /** @use HasFactory<\Database\Factories\MeetingInfoFactory> */
    use HasFactory;

    protected $fillable =[
        'description',
        'meeting_id',
        'media_paths',
        'status'
        ,'summaries'
    ];

    protected $casts = [
        'summaries' => 'string',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class,'meeting_id');
    }
    public function segments()
    {
        return $this->hasMany(TranscriptSegments::class, 'meeting_info_id');
    }
}

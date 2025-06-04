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
        'video_path',
        'transcript_json'
    ];

    protected $casts = [
        'transcript_json' => 'array',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class,'meeting_id');
    }
}

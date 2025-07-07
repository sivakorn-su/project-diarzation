<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MeetingInfo extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\MeetingInfoFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable =[
        'description',
        'meeting_id',
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

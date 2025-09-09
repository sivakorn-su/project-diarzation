<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
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
        ,'media_object_key'
    ];

    protected $casts = [
        'summaries' => 'array',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class,'meeting_id');
    }
    public function segments()
    {
        return $this->hasMany(TranscriptSegments::class, 'meeting_info_id');
    }

    public function getMediaUrlAttribute(): ?string
    {
    if (!$this->media_object_key) return null;

    // ถ้ามี CDN/Custom domain:
    if (config('filesystems.disks.s3.url')) {
        return rtrim(config('filesystems.disks.s3.url'), '/').'/'.$this->media_object_key;
    }

    // ถ้า private: ให้ลิงก์ชั่วคราว
    return Storage::disk('s3')->temporaryUrl($this->media_object_key, now()->addHours(6));
    }
}

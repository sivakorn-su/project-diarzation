<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    /** @use HasFactory<\Database\Factories\MeetingFactory> */
    use HasFactory;

    protected $fillable =[
        'title',
        'start_date',
        'end_date',
        'level',
        'user_id',
    ];
    protected function casts(): array
    {
        return [
            'start_date'=>'datetime',
            'end_date'=>'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function info()
    {
        return $this->hasOne(MeetingInfo::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];

    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_WORKING = 'working';
    const STATUS_ON_BREAK = 'on_break';
    const STATUS_FINISHED = 'finished';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function revisionRequests()
    {
        return $this->hasMany(RevisionRequest::class);
    }
}

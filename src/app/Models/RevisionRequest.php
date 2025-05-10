<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevisionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'old_start_time',
        'new_start_time',
        'old_end_time',
        'new_end_time',
        'break_modifications',
        'note',
        'status'
    ];

    protected $casts = [
        'break_modifications' => 'json',
        'old_start_time' => 'datetime',
        'new_start_time' => 'datetime',
        'old_end_time' => 'datetime',
        'new_end_time' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}

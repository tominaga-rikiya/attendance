<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    // ステータス定数
    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_WORKING = 'working';
    const STATUS_ON_BREAK = 'on_break';
    const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'status'
    ];

    protected $casts = [
        'date' => 'date',  
    ];

    /**
     * ユーザーとのリレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 休憩時間とのリレーション
     */
    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function revisionRequests()
    {
        return $this->hasMany(RevisionRequest::class);
    }

    public function hasPendingRevision()
    {
        return $this->revisionRequests()
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * 出勤時間を時間:分
     */
    public function getTimeOnlyStartTimeAttribute()
    {
        if (!$this->start_time) {
            return '';
        }

        return Carbon::parse($this->start_time)->format('H:i');
    }

    /**
     * 退勤時間を時間:分
     */
    public function getTimeOnlyEndTimeAttribute()
    {
        if (!$this->end_time) {
            return '';
        }

        return Carbon::parse($this->end_time)->format('H:i');
    }

    /**
     * 日付をフォーマット
     */
    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->date)->format('Y/m/d');
    }

    /**
     * 休憩時間の合計を時間:分
     */
    public function getFormattedBreakTimeAttribute()
    {
        $totalMinutes = $this->total_break_minutes;

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * 日付を「MM/DD（曜日）」形式
     */
    public function getFormattedDateWithDayAttribute()
    {
        if (!$this->date) {
            return '';
        }

        $date = Carbon::parse($this->date);
        $dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek];

        return $date->format('m/d') . '（' . $dayOfWeek . '）';
    }

    /**
     * 実労働時間を時間:分
     */
    public function getFormattedWorkTimeAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        $startTime = Carbon::parse($this->start_time)->format('Y-m-d H:i');
        $startTime = Carbon::parse($startTime);

        $endTime = Carbon::parse($this->end_time)->format('Y-m-d H:i');
        $endTime = Carbon::parse($endTime);

        $totalMinutes = $endTime->diffInMinutes($startTime);

        $breakMinutes = $this->total_break_minutes;

        $workMinutes = max(0, $totalMinutes - $breakMinutes);

        $hours = floor($workMinutes / 60);
        $minutes = $workMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * 休憩時間の合計:分
     */
    public function getTotalBreakMinutesAttribute()
    {
        $totalMinutes = 0;

        foreach ($this->breakTimes as $break) {
            if ($break->start_time && $break->end_time) {
                // 開始と終了時刻が同じ場合は除外
                $startFormatted = Carbon::parse($break->start_time)->format('H:i');
                $endFormatted = Carbon::parse($break->end_time)->format('H:i');

                if ($startFormatted !== $endFormatted) {
                    $totalMinutes += $break->duration_minutes;
                }
            }
        }

        return $totalMinutes;
    }
}

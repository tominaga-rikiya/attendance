<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BreakTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 開始時間を時間:分の形式で取得
     */
    public function getTimeOnlyStartTimeAttribute()
    {
        if (!$this->start_time) {
            return '';
        }

        return Carbon::parse($this->start_time)->format('H:i');
    }

    /**
     * 終了時間を時間:分の形式で取得
     */
    public function getTimeOnlyEndTimeAttribute()
    {
        if (!$this->end_time) {
            return '';
        }

        return Carbon::parse($this->end_time)->format('H:i');
    }

    /**
     * この休憩の時間を分で計算（秒は切り捨て）
     */
    public function getDurationMinutesAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        // 秒を切り捨てるため、時と分だけの文字列に変換してから再度Carbonオブジェクトに変換
        $start = Carbon::parse($this->start_time)->format('Y-m-d H:i');
        $start = Carbon::parse($start);

        $end = Carbon::parse($this->end_time)->format('Y-m-d H:i');
        $end = Carbon::parse($end);

        // 分単位の差分を計算
        return $end->diffInMinutes($start);
    }
}

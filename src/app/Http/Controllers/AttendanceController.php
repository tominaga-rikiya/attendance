<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRevisionRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * 勤怠画面表示
     */
    public function create()
    {
        $user = auth()->user();
        $today = Carbon::now()->toDateString();

        // 今日の勤怠記録を取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // 現在のステータスを確認
        $status = $attendance ? $attendance->status : Attendance::STATUS_NOT_STARTED;

        // 現在の日時
        $currentDateTime = Carbon::now()->format('Y-m-d H:i:s');

        return view('attendance.create', compact('status', 'currentDateTime'));
    }

    /**
     * 出勤処理
     */
    public function clockIn(AttendanceRevisionRequest $request)
    {
        $user = auth()->user();
        $today = Carbon::now()->toDateString();
        $now = Carbon::now();

        // 今日の勤怠記録を確認
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // 勤怠記録がなければ新規作成
        if (!$attendance) {
            $attendance = new Attendance([
                'user_id' => $user->id,
                'date' => $today,
                'start_time' => $now,
                'status' => Attendance::STATUS_WORKING
            ]);
            $attendance->save();
        } else {
            // 既存の記録を更新
            $attendance->start_time = $now;
            $attendance->status = Attendance::STATUS_WORKING;
            $attendance->save();
        }

        return redirect()->route('attendance.create');
    }

    /**
     * 休憩開始処理
     */
    public function breakStart(AttendanceRevisionRequest $request)
    {
        $user = auth()->user();
        $today = Carbon::now()->toDateString();
        $now = Carbon::now();

        // 今日の勤怠記録を確認
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // 休憩記録を作成
        $break = new BreakTime([
            'attendance_id' => $attendance->id,
            'break_start' => $now
        ]);
        $break->save();

        // 勤怠状態を更新
        $attendance->status = Attendance::STATUS_ON_BREAK;
        $attendance->save();

        return redirect()->route('attendance.create');
    }

    /**
     * 休憩終了処理
     */
    public function breakEnd(AttendanceRevisionRequest $request)
    {
        $user = auth()->user();
        $today = Carbon::now()->toDateString();
        $now = Carbon::now();

        // 今日の勤怠記録を確認
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // 最新の休憩記録を更新
        $break = BreakTime::where('attendance_id', $attendance->id)
            ->whereNull('end_time')
            ->latest()
            ->first();

        if ($break) {
            $break->end_time = $now;
            $break->save();
        }

        // 勤怠状態を更新
        $attendance->status = Attendance::STATUS_WORKING;
        $attendance->save();

        return redirect()->route('attendance.create');
    }

    /**
     * 退勤処理
     */
    public function clockOut(AttendanceRevisionRequest $request)
    {
        $user = auth()->user();
        $today = Carbon::now()->toDateString();
        $now = Carbon::now();

        // 今日の勤怠記録を確認
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // 退勤処理
        $attendance->end_time = $now;
        $attendance->status = Attendance::STATUS_FINISHED;
        $attendance->save();

        return redirect()->route('attendance.create')
            ->with('success', 'お疲れ様でした。');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRevisionRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\RevisionRequest;
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
    public function clockIn(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::now()->toDateString();

        // 秒を切り捨てた現在時刻を使用
        $now = Carbon::now()->format('Y-m-d H:i:00');
        $now = Carbon::parse($now);


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
    public function breakStart(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::now()->toDateString();

        // 秒を切り捨てた現在時刻を使用
        $now = Carbon::now()->format('Y-m-d H:i:00');
        $now = Carbon::parse($now);


        // 今日の勤怠記録を確認
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // 休憩記録を作成
        $break = new BreakTime([
            'attendance_id' => $attendance->id,
            'start_time' => $now
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
    public function breakEnd(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::now()->toDateString();
        // 秒を切り捨てた現在時刻を使用
        $now = Carbon::now()->format('Y-m-d H:i:00');
        $now = Carbon::parse($now);


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
    public function clockOut(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::now()->toDateString();
        // 秒を切り捨てた現在時刻を使用
        $now = Carbon::now()->format('Y-m-d H:i:00');
        $now = Carbon::parse($now);


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
    /**
     * 勤怠一覧表示
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $searchMonth = $request->input('month', date('Y-m'));
        $startDate = $searchMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        $today = Carbon::now()->format('Y-m-d');

        // 現在の月かどうかをチェック
        $isCurrentMonth = (date('Y-m', strtotime($startDate)) === date('Y-m'));

        // 現在の月の場合は今日までの日付のみを生成
        if ($isCurrentMonth) {
            $endDateForPeriod = $today;
        } else {
            // 過去の月の場合は月末まで
            $endDateForPeriod = $endDate;
        }

        // 月の全日付を生成
        $period = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            new \DateTime($endDateForPeriod . ' 23:59:59')
        );

        // 日付をキーとした配列を初期化
        $datesArray = [];
        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $datesArray[$dateString] = null;
        }

        // 勤怠データを取得
        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDateForPeriod])
            ->get();

        // 勤怠データを日付配列にマッピング
        foreach ($attendances as $attendance) {
            // dateがCarbonオブジェクトの場合は文字列に変換
            $dateKey = $attendance->date instanceof \Carbon\Carbon
                ? $attendance->date->format('Y-m-d')
                : $attendance->date;

            $datesArray[$dateKey] = $attendance;
        }
        return view('attendance.index', [
            'datesArray' => $datesArray,
            'searchMonth' => $searchMonth,
            'isCurrentMonth' => $isCurrentMonth
        ]);
    }
    /**
     * 勤怠詳細表示
     */
    public function show($id)
    {
        $attendance = Attendance::with('breakTimes')
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        // 承認待ちの申請があるか確認
        $pendingRevision = RevisionRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->first();

        return view('attendance.show', compact('attendance', 'pendingRevision'));
    }

    public function update(AttendanceRevisionRequest $request, $id)
{
    // バリデーションはAttendanceRevisionRequestで処理済み
    
    $attendance = Attendance::findOrFail($id);
    
    // ユーザーが編集権限を持っているか確認
    if ($attendance->user_id !== auth()->id()) {
        return redirect()->back()->with('error', '権限がありません');
    }
    
    // 承認待ちの修正リクエストがある場合はリダイレクト
    if ($attendance->hasPendingRevision()) {
        return redirect()->back()->with('error', '承認待ちの修正リクエストがあります');
    }
    
    // 修正リクエストの作成
    $revision = new RevisionRequest();
    $revision->user_id = auth()->id(); // user_idを追加
    $revision->old_start_time = $attendance->start_time; // 元の値も保存
    $revision->old_end_time = $attendance->end_time; // 元の値も保存
    $revision->attendance_id = $attendance->id;
    $revision->new_start_time = $request->start_time;
    $revision->new_end_time = $request->end_time;
    $revision->note = $request->note;
    $revision->status = 'pending';
    $revision->save();   
    
        // 休憩時間の保存
        if ($request->has('breaks')) {
            foreach ($request->breaks as $breakData) {
                if (!empty($breakData['start_time']) && !empty($breakData['end_time'])) {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,  // 修正: revision_id → attendance_id
                        'start_time' => $breakData['start_time'],
                        'end_time' => $breakData['end_time']
                    ]);
                }
            }
        }
    
    return redirect()->route('attendance.show', $id)->with('success', '修正リクエストを送信しました');
}
   
    /**
     * 勤怠修正申請一覧表示
     */
    public function correctionIndex()
    {
        // 自分が行った申請のうち、管理者が承認していないもの
        $pendingRequests = RevisionRequest::where('revision_requests.user_id', auth()->id())
            ->where('revision_requests.status', 'pending')  
            ->with(['attendance', 'user'])
            ->join('attendances', 'revision_requests.attendance_id', '=', 'attendances.id')
            ->orderBy('attendances.date', 'asc')
            ->select('revision_requests.*')
            ->get();

        // 自分が行った申請のうち、承認済みのもの
        $approvedRequests = RevisionRequest::where('revision_requests.user_id', auth()->id())  
            ->where('revision_requests.status', 'approved')  
            ->with(['attendance', 'user'])
            ->join('attendances', 'revision_requests.attendance_id', '=', 'attendances.id')
            ->orderBy('attendances.date', 'asc')
            ->select('revision_requests.*')
            ->get();

        return view('attendance.correction_index', compact('pendingRequests', 'approvedRequests'));
    }

    // 管理者用の承認機能
    public function approve($id)
    {
        $request = RevisionRequest::findOrFail($id);
        $request->status = 'approved';
        $request->approved_at = now();
        $request->save();

        $attendance = $request->attendance;
        $attendance->start_time = $request->new_start_time;
        $attendance->end_time = $request->new_end_time;
        $attendance->save();

        return redirect()->back()->with('success', '申請を承認しました');
    }

    /**
     * 勤怠修正申請詳細表示
     */
    public function correctionShow($id)
    {
        $revision = RevisionRequest::with(['attendance', 'user'])->findOrFail($id);

        if ($revision->user_id != auth()->id() && !auth()->user()->isAdmin()) {
            return redirect()->route('attendance.correction')->with('error', '権限がありません');
        }

        return view('attendance.correction_show', compact('revision'));
    }
}

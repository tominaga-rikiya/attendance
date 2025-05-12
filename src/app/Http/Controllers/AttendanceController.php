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

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        $status = $attendance ? $attendance->status : Attendance::STATUS_NOT_STARTED;

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

        $now = Carbon::now()->format('Y-m-d H:i:00');
        $now = Carbon::parse($now);

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

        $now = Carbon::now()->format('Y-m-d H:i:00');
        $now = Carbon::parse($now);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        $break = new BreakTime([
            'attendance_id' => $attendance->id,
            'start_time' => $now
        ]);
        $break->save();

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
        
        $now = Carbon::now()->format('Y-m-d H:i:00');
        $now = Carbon::parse($now);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        $break = BreakTime::where('attendance_id', $attendance->id)
            ->whereNull('end_time')
            ->latest()
            ->first();

        if ($break) {
            $break->end_time = $now;
            $break->save();
        }

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
       
        $now = Carbon::now()->format('Y-m-d H:i:00');
        $now = Carbon::parse($now);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

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

        $isCurrentMonth = (date('Y-m', strtotime($startDate)) === date('Y-m'));

        if ($isCurrentMonth) {
            $endDateForPeriod = $today;
        } else {
            $endDateForPeriod = $endDate;
        }

        $period = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            new \DateTime($endDateForPeriod . ' 23:59:59')
        );

        $datesArray = [];
        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $datesArray[$dateString] = null;
        }

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDateForPeriod])
            ->get();

        foreach ($attendances as $attendance) {
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

        $pendingRevision = RevisionRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->first();

        return view('attendance.show', compact('attendance', 'pendingRevision'));
    }

    public function update(AttendanceRevisionRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        if ($attendance->user_id !== auth()->id()) {
            return redirect()->back()->with('error', '権限がありません');
        }

        if ($attendance->hasPendingRevision()) {
            return redirect()->back()->with('error', '承認待ちの修正リクエストがあります');
        }

        // 修正リクエストの作成
        $revision = new RevisionRequest();
        $revision->user_id = auth()->id();
        $revision->attendance_id = $attendance->id;
        $revision->old_start_time = $attendance->start_time;
        $revision->old_end_time = $attendance->end_time;

        // 時刻のみを保存
        $revision->new_start_time = $request->start_time;
        $revision->new_end_time = $request->end_time;
        $revision->note = $request->note;
        $revision->status = 'pending';

        // 休憩時間の変更情報を保存
        $breakModifications = [];
        if ($request->has('breaks')) {
            foreach ($request->breaks as $breakData) {
                if (!empty($breakData['start_time']) && !empty($breakData['end_time'])) {
                    $breakModifications[] = [
                        'start_time' => $breakData['start_time'],
                        'end_time' => $breakData['end_time']
                    ];
                }
            }
        }

        $revision->break_modifications = json_encode($breakModifications);
        $revision->save();

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

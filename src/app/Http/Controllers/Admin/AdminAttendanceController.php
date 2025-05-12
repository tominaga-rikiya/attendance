<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRevisionRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\RevisionRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAttendanceController extends Controller
{
    /**
     * 勤怠一覧表示
     */
    public function index(Request $request)
    {
        $searchDate = $request->input('date', Carbon::now()->toDateString());
        $searchUserId = $request->input('user_id');

        $query = Attendance::with(['user', 'breakTimes'])
            ->where('date', $searchDate);

        if ($searchUserId) {
            $query->where('user_id', $searchUserId);
        }

        $attendances = $query->get();
        $prevDate = Carbon::parse($searchDate)->subDay()->toDateString();
        $nextDate = Carbon::parse($searchDate)->addDay()->toDateString();
        $formattedDate = Carbon::parse($searchDate)->format('Y年n月j日');

        return view('admin.admin_index', compact(
            'attendances',
            'searchDate',
            'searchUserId',
            'prevDate',
            'nextDate',
            'formattedDate'
        ));
    }

    /**
     * 勤怠詳細表示
     */
    public function show($id)
    {
        $attendance = Attendance::with('user', 'breakTimes')
            ->findOrFail($id);

        return view('admin.admin_show', compact('attendance'));
    }

    /**
     * 勤怠情報の更新
     */
    public function update(AttendanceRevisionRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $attendance->start_time = $request->start_time;

        if ($attendance->status === 'finished' || $request->filled('end_time')) {
            $attendance->end_time = $request->end_time;

            if ($attendance->end_time) {
                $attendance->status = 'finished';
            }
        }

        $attendance->save();

        $attendance->breakTimes()->delete();

        if ($request->has('breaks')) {
            foreach ($request->breaks as $breakData) {
                if (!empty($breakData['start_time']) && !empty($breakData['end_time'])) {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => $breakData['start_time'],
                        'end_time' => $breakData['end_time']
                    ]);
                }
            }
        }

        $revisionRequest = new RevisionRequest();
        $revisionRequest->user_id = auth()->id();
        $revisionRequest->attendance_id = $attendance->id;
        $revisionRequest->old_start_time = null;
        $revisionRequest->new_start_time = $request->start_time;
        $revisionRequest->old_end_time = null;
        $revisionRequest->new_end_time = $request->end_time;
        $revisionRequest->note = $request->note;
        $revisionRequest->status = 'approved';
        $revisionRequest->save();

        return redirect()->route('admin.attendances.show', $id)
            ->with('success', '勤怠情報を修正しました');
    }

    /**
     * 修正申請一覧
     */
    public function indexCorrectionRequests()
    {
        $pendingRequests = RevisionRequest::where('revision_requests.status', 'pending')
            ->with(['attendance', 'user'])
            ->join('attendances', 'revision_requests.attendance_id', '=', 'attendances.id')
            ->orderBy('attendances.date', 'asc')
            ->orderBy('attendances.start_time', 'asc')
            ->select('revision_requests.*')
            ->get();

        $approvedRequests = RevisionRequest::where('revision_requests.status', 'approved')
            ->with(['attendance', 'user'])
            ->join('attendances', 'revision_requests.attendance_id', '=', 'attendances.id')
            ->orderBy('attendances.date', 'asc')
            ->orderBy('attendances.start_time', 'asc')
            ->select('revision_requests.*')
            ->get();

        return view('admin.admin_request_index', compact('pendingRequests', 'approvedRequests'));
    }

    /**
     * 修正申請詳細
     */
    public function showCorrectionRequest($id)
    {
        $revisionRequest = RevisionRequest::findOrFail($id);
        $attendance = $revisionRequest->attendance;
        $pendingRevision = $revisionRequest;

        return view('admin.admin_correction_requests_approve', compact('revisionRequest', 'attendance', 'pendingRevision'));
    }

    public function approveCorrectionRequest($id)
    {
        DB::beginTransaction();

        try {
            $revisionRequest = RevisionRequest::findOrFail($id);

            if ($revisionRequest->status === 'approved') {
                return redirect()->route('admin.correction-requests.index')
                    ->with('error', 'この申請は既に承認されています');
            }

            $attendance = $revisionRequest->attendance;

            // 開始時刻・終了時刻の更新
            if ($revisionRequest->new_start_time) {
                $attendance->start_time = Carbon::parse($revisionRequest->new_start_time)->format('H:i:s');
            }

            if ($revisionRequest->new_end_time) {
                $attendance->end_time = Carbon::parse($revisionRequest->new_end_time)->format('H:i:s');
                $attendance->status = 'finished';
            }

            // 休憩時間の処理
            if ($revisionRequest->break_modifications) {
                $attendance->breakTimes()->delete();

                $breakModifications = json_decode($revisionRequest->break_modifications, true);

                if (is_array($breakModifications)) {
                    foreach ($breakModifications as $index => $breakData) {
                        if (!empty($breakData['start_time']) && !empty($breakData['end_time'])) {
                            BreakTime::create([
                                'attendance_id' => $attendance->id,
                                'start_time' => Carbon::parse($breakData['start_time'])->format('H:i:s'),
                                'end_time' => Carbon::parse($breakData['end_time'])->format('H:i:s')
                            ]);
                        }
                    }
                }
            }

            $attendance->save();

            $revisionRequest->status = 'approved';
            $revisionRequest->save();

            DB::commit();

            return redirect()->route('admin.correction-requests.index')
                ->with('success', '修正申請を承認しました');
        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()
                ->with('error', 'エラーが発生しました: ' . $e->getMessage());
        }
    }
}
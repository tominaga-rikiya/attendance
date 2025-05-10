<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRevisionRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\RevisionRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;


class AdminAttendanceController extends Controller
{
    
    /**
     * 勤怠一覧表示 (管理者用)
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
        $attendance = Attendance::with('user','breakTimes')
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
        $attendance->end_time = $request->end_time;
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
     * 管理者用の修正申請一覧を表示
     *
     */
    public function indexCorrectionRequests()
    {
        // 自分が行った申請のうち、管理者が承認していないもの
        $pendingRequests = RevisionRequest::where('revision_requests.status', 'pending')
            ->with(['attendance', 'user'])
            ->join('attendances', 'revision_requests.attendance_id', '=', 'attendances.id')
            ->orderBy('attendances.date', 'asc')
            ->select('revision_requests.*')
            ->get();

        // 自分が行った申請のうち、承認済みのもの
        $approvedRequests = RevisionRequest::where('revision_requests.status', 'approved')
            ->with(['attendance', 'user'])
            ->join('attendances', 'revision_requests.attendance_id', '=', 'attendances.id')
            ->orderBy('attendances.date', 'asc')
            ->select('revision_requests.*')
            ->get();

        return view('admin.admin_request_index', compact('pendingRequests', 'approvedRequests'));
    }


    /**
     * 修正申請詳細を表示
     */
    public function showCorrectionRequest($id)
    {
        $revisionRequest = RevisionRequest::findOrFail($id);
        $attendance = $revisionRequest->attendance;
        $pendingRevision = $revisionRequest;

        return view('admin.admin_correction_requests_approve', compact('revisionRequest', 'attendance', 'pendingRevision'));
    }


    /**
     * 修正申請を承認する
     */
    public function approveCorrectionRequest($id)
    {
        $revisionRequest = RevisionRequest::findOrFail($id);

        if ($revisionRequest->status === 'approved') {
            return redirect()->route('admin.correction-requests.index')
                ->with('error', 'この申請は既に承認されています');
        }

        $attendance = $revisionRequest->attendance;

        if ($revisionRequest->new_start_time) {
            $attendance->start_time = $revisionRequest->new_start_time;
        }

        if ($revisionRequest->new_end_time) {
            $attendance->end_time = $revisionRequest->new_end_time;
        }

        if ($revisionRequest->break_modifications) {
            $attendance->breakTimes()->delete();

            $breakModifications = json_decode($revisionRequest->break_modifications, true);
            if (is_array($breakModifications)) {
                foreach ($breakModifications as $breakData) {
                    if (!empty($breakData['start_time']) && !empty($breakData['end_time'])) {
                        BreakTime::create([
                            'attendance_id' => $attendance->id,
                            'start_time' => $breakData['start_time'],
                            'end_time' => $breakData['end_time']
                        ]);
                    }
                }
            }
        }

        $attendance->save();

        $revisionRequest->status = 'approved';
        $revisionRequest->save();

        return redirect()->route('admin.correction-requests.index')
            ->with('success', '修正申請を承認しました');
    }
}

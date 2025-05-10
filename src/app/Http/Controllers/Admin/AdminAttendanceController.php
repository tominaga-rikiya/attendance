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
        // 検索日付（指定がなければ今日）
        $searchDate = $request->input('date', Carbon::now()->toDateString());

        // 検索用ユーザーID（指定があれば特定ユーザーのみ表示）
        $searchUserId = $request->input('user_id');

        // 全てのユーザーの指定日の勤怠データを取得
        $query = Attendance::with(['user', 'breakTimes'])
            ->where('date', $searchDate);

        // 特定ユーザーが指定されている場合はフィルタリング
        if ($searchUserId) {
            $query->where('user_id', $searchUserId);
        }

        // データ取得
        $attendances = $query->get();

        // 日付の前日/翌日を計算
        $prevDate = Carbon::parse($searchDate)->subDay()->toDateString();
        $nextDate = Carbon::parse($searchDate)->addDay()->toDateString();

        // 日付のフォーマット（「2023年6月1日の勤怠」の形式）
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
        // バリデーションはAttendanceRevisionRequestで処理済み

        $attendance = Attendance::findOrFail($id);

        // 管理者権限チェック
        if (!auth()->user()->isAdmin()) {
            return redirect()->back()->with('error', '権限がありません');
        }

        // 開始時間と終了時間を更新
        $attendance->start_time = $request->start_time;
        $attendance->end_time = $request->end_time;
        // note は別テーブルで管理するため、ここでは保存しない
        $attendance->save();

        // 既存の休憩時間を削除
        $attendance->breakTimes()->delete();

        // 新しい休憩時間を保存
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

        // 備考は RevisionRequest テーブルに保存
        $revisionRequest = new \App\Models\RevisionRequest();
        $revisionRequest->user_id = auth()->id();
        $revisionRequest->attendance_id = $attendance->id;
        $revisionRequest->old_start_time = null; // 管理者による直接更新のため、old値は不要
        $revisionRequest->new_start_time = $request->start_time;
        $revisionRequest->old_end_time = null; // 管理者による直接更新のため、old値は不要
        $revisionRequest->new_end_time = $request->end_time;
        $revisionRequest->note = $request->note;
        $revisionRequest->status = 'approved'; // 管理者による更新なので自動承認
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
        // 管理者権限チェック - ヘルパー関数を使用
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', '権限がありません');
        }

        // 承認待ちの申請を取得（全ユーザー分）
        // テーブル名を明示してstatus列を指定
        $pendingRequests = RevisionRequest::where('revision_requests.status', 'pending')
            ->with(['attendance', 'user'])
            ->join('attendances', 'revision_requests.attendance_id', '=', 'attendances.id')
            ->orderBy('attendances.date', 'asc')
            ->select('revision_requests.*')
            ->get();

        // 承認済みの申請を取得（全ユーザー分）
        // テーブル名を明示してstatus列を指定
        $approvedRequests = RevisionRequest::where('revision_requests.status', 'approved')
            ->with(['attendance', 'user'])
            ->join('attendances', 'revision_requests.attendance_id', '=', 'attendances.id')
            ->orderBy('attendances.date', 'asc')
            ->select('revision_requests.*')
            ->get();

        // viewを返す - 既存のビューファイルを使用
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
    /**
     * 修正申請を承認する
     */
    public function approveCorrectionRequest($id)
    {
        // IDから修正申請を検索
        $revisionRequest = RevisionRequest::findOrFail($id);

        // 既に承認済みの場合はエラー
        if ($revisionRequest->status === 'approved') {
            return redirect()->route('admin.correction-requests.index')
                ->with('error', 'この申請は既に承認されています');
        }

        // 関連する勤怠情報を取得
        $attendance = $revisionRequest->attendance;

        // 修正申請の内容を勤怠情報に反映
        if ($revisionRequest->new_start_time) {
            $attendance->start_time = $revisionRequest->new_start_time;
        }

        if ($revisionRequest->new_end_time) {
            $attendance->end_time = $revisionRequest->new_end_time;
        }

        // 休憩時間の修正があれば適用
        if ($revisionRequest->break_modifications) {
            // 既存の休憩時間を削除
            $attendance->breakTimes()->delete();

            // JSON形式で保存されている休憩時間情報を解析して新たに作成
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

        // 勤怠情報を保存
        $attendance->save();

        // 申請ステータスを承認済みに更新（approved_atは使用しない）
        $revisionRequest->status = 'approved';
        // approved_atの記録は削除
        $revisionRequest->save();

        return redirect()->route('admin.correction-requests.index')
            ->with('success', '修正申請を承認しました');
    }
    
    












}

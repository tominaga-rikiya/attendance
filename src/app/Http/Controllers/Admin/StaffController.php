<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class StaffController extends Controller
{

    
    /**
     * スタッフ一覧を表示
     */
    public function index()
    {
        // 管理者権限チェック
        if (!Auth::user()->isAdmin()) {
            return redirect()->route('home')->with('error', '権限がありません');
        }

        // 一般ユーザーのみを取得
        $users = User::where('role', '!=', 'admin')
            ->orderBy('name')
            ->get();

        return view('admin.staff_index', compact('users'));
    }

    /**
     * ユーザーの月次勤怠一覧を表示
     */
    // 1. StaffController.php の monthlyAttendance メソッドをこれで置き換える

    public function monthlyAttendance($userId, Request $request)
    {
        // 管理者権限チェック
        if (!Auth::user()->isAdmin()) {
            return redirect()->route('home')->with('error', '権限がありません');
        }

        // ユーザー情報取得
        $user = User::findOrFail($userId);

        // 月のパラメータを取得
        $searchMonth = $request->query('month', date('Y-m'));

        // 日付範囲の準備
        $startMonth = Carbon::createFromFormat('Y-m', $searchMonth)->startOfMonth();
        $endMonth = Carbon::createFromFormat('Y-m', $searchMonth)->endOfMonth();
        $today = Carbon::now();
        $displayEndDate = $endMonth->copy()->min($today);

        // 勤怠データを先に取得
        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startMonth->format('Y-m-d'), $displayEndDate->format('Y-m-d')])
            ->orderBy('date', 'asc')
            ->get();

        // 手動でbreakTimesをロード
        $attendances->load('breakTimes');

        // 日付配列を準備（シンプルに）
        $datesArray = [];
        for ($date = $startMonth->copy(); $date->lte($displayEndDate); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $datesArray[$dateString] = null;
        }

        // マージもシンプルに
        foreach ($attendances as $attendance) {
            // キャストにより、dateは常にCarbon
            // format('Y-m-d')で文字列に変換
            $dateKey = $attendance->date->format('Y-m-d');

            // シンプルなチェック
            if (array_key_exists($dateKey, $datesArray)) {
                $datesArray[$dateKey] = $attendance;
            }
        }

        // 前月・翌月の年月を計算
        $prevMonth = Carbon::createFromFormat('Y-m', $searchMonth)->subMonth()->format('Y-m');
        $nextMonth = Carbon::createFromFormat('Y-m', $searchMonth)->addMonth()->format('Y-m');

        // ビューに渡すデータ
        return view('admin.staff_attendance', [
            'user' => $user,
            'datesArray' => $datesArray,
            'searchMonth' => $searchMonth,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'formattedMonth' => Carbon::createFromFormat('Y-m', $searchMonth)->format('Y年n月'),
            'startDay' => $startMonth->format('Y/m/d'),
            'endDay' => $endMonth->format('Y/m/d')
        ]);
    }
    public function exportAttendanceCsv($userId, Request $request)
    {
        // (1) 管理者かどうかチェック
        if (!Auth::user()->isAdmin()) {
            return redirect()->route('home')->with('error', '権限がありません');
        }

        // (2) 必要な情報を準備
        $user = User::findOrFail($userId);  // どの人の記録か
        $searchMonth = $request->query('month', date('Y-m'));  // 何月の記録か

        // (3) その月の日にちの範囲を計算
        $startMonth = Carbon::createFromFormat('Y-m', $searchMonth)->startOfMonth();
        $endMonth = Carbon::createFromFormat('Y-m', $searchMonth)->endOfMonth();

        // (4) 勤怠データを取得
        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startMonth->format('Y-m-d'), $endMonth->format('Y-m-d')])
            ->orderBy('date', 'asc')
            ->get();

        // breakTimesリレーションをロード
        $attendances->load('breakTimes');

        // (5)(6)(7) CSVファイルの作成とダウンロード処理
        return response()->streamDownload(function () use ($attendances, $startMonth, $endMonth, $user) {
            $file = fopen('php://output', 'w');

            // 文字化けしないためのBOM
            fputs($file, "\xEF\xBB\xBF");

            // 1行目（タイトル行）
            fputcsv($file, [
                '氏名',
                '日付',
                '曜日',
                '出勤時間',
                '退勤時間',
                '休憩時間',
                '勤務時間'
            ]);

            // 日付ごとにデータを書き出す
            for ($date = $startMonth->copy(); $date->lte($endMonth); $date->addDay()) {
                // 日付と曜日の準備
                $dateString = $date->format('Y-m-d');
                $weekday = ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek];

                // その日の勤怠記録を探す
                $attendance = $attendances->first(function ($item) use ($dateString) {
                    return $item->date->format('Y-m-d') === $dateString;
                });

                // CSVの1行を書き出す
                if ($attendance) {
                    // 記録があれば書き出す
                    fputcsv($file, [
                        $user->name,
                        $date->format('Y/m/d'),
                        $weekday,
                        $attendance->time_only_start_time ?? '',
                        $attendance->time_only_end_time ?? '',
                        $attendance->formatted_break_time ?? '',
                        $attendance->formatted_work_time ?? ''
                    ]);
                } else {
                    // 記録がない日も空欄で書き出す
                    fputcsv($file, [
                        $user->name,
                        $date->format('Y/m/d'),
                        $weekday,
                        '',
                        '',
                        '',
                        ''
                    ]);
                }
            }

            fclose($file);
        }, $user->name . '_勤怠_' . $searchMonth . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
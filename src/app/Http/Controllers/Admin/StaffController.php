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
        $users = User::where('role', '!=', 'admin')
            ->orderBy('name')
            ->get();

        return view('admin.staff_index', compact('users'));
    }

    /**
     * ユーザーの月次勤怠一覧を表示
     */
    public function monthlyAttendance($userId, Request $request)
    {
       
        $user = User::findOrFail($userId);

        $searchMonth = $request->query('month', date('Y-m'));

        $startMonth = Carbon::createFromFormat('Y-m', $searchMonth)->startOfMonth();
        $endMonth = Carbon::createFromFormat('Y-m', $searchMonth)->endOfMonth();
        $today = Carbon::now();
        $displayEndDate = $endMonth->copy()->min($today);

        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startMonth->format('Y-m-d'), $displayEndDate->format('Y-m-d')])
            ->orderBy('date', 'asc')
            ->get();

        $attendances->load('breakTimes');

        $datesArray = [];
        for ($date = $startMonth->copy(); $date->lte($displayEndDate); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $datesArray[$dateString] = null;
        }

        foreach ($attendances as $attendance) {
         
            $dateKey = $attendance->date->format('Y-m-d');

            if (array_key_exists($dateKey, $datesArray)) {
                $datesArray[$dateKey] = $attendance;
            }
        }

     
        $prevMonth = Carbon::createFromFormat('Y-m', $searchMonth)->subMonth()->format('Y-m');
        $nextMonth = Carbon::createFromFormat('Y-m', $searchMonth)->addMonth()->format('Y-m');

      
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
        
        $user = User::findOrFail($userId);  
        $searchMonth = $request->query('month', date('Y-m')); 

        $startMonth = Carbon::createFromFormat('Y-m', $searchMonth)->startOfMonth();
        $endMonth = Carbon::createFromFormat('Y-m', $searchMonth)->endOfMonth();

        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startMonth->format('Y-m-d'), $endMonth->format('Y-m-d')])
            ->orderBy('date', 'asc')
            ->get();

        $attendances->load('breakTimes');

        return response()->streamDownload(function () use ($attendances, $startMonth, $endMonth, $user) {
            $file = fopen('php://output', 'w');

            // 文字化けしないためのBOM
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                '氏名',
                '日付',
                '曜日',
                '出勤時間',
                '退勤時間',
                '休憩時間',
                '勤務時間'
            ]);

            
            for ($date = $startMonth->copy(); $date->lte($endMonth); $date->addDay()) {
                
                $dateString = $date->format('Y-m-d');
                $weekday = ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek];

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
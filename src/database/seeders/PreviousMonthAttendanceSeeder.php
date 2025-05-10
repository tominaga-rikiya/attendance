<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\RevisionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PreviousMonthAttendanceSeeder extends Seeder
{
    /**
     * 勤怠データと修正申請を整合性を持って生成
     * 各ユーザーに対して確実に20件のデータを生成する
     */
    public function run()
    {
        // 対象ユーザーの取得（一般ユーザーのみ）
        $users = User::where('role', 'user')->get();
        $today = Carbon::now();

        // 特定の日付とユーザーの組み合わせを定義（承認待ちデータ用）
        $pendingRevisions = [
            [
                'user_name' => '増田 一世',
                'date' => '2025-05-03',
                'start_time' => '10:30',
                'end_time' => '15:45',
                'new_start_time' => '10:30',
                'new_end_time' => '15:45',
                'break_time' => [['start_time' => '12:00', 'end_time' => '13:00']],
                'note' => '実際の退勤時間に合わせて修正',
                'status' => 'pending'
            ],
            [
                'user_name' => '秋田 朋美',
                'date' => '2025-05-03',
                'start_time' => '09:30',
                'end_time' => '18:15',
                'new_start_time' => '09:30',
                'new_end_time' => '18:15',
                'break_time' => [['start_time' => '12:00', 'end_time' => '13:00']],
                'note' => '実際の退勤時間に合わせて修正',
                'status' => 'pending'
            ],
            [
                'user_name' => '中西 春夫',
                'date' => '2025-05-02',
                'start_time' => '08:30',
                'end_time' => '17:30',
                'new_start_time' => '08:30',
                'new_end_time' => '17:30',
                'break_time' => [['start_time' => '12:00', 'end_time' => '13:00']],
                'note' => '実際の退勤時間に合わせて修正',
                'status' => 'pending'
            ],
            [
                'user_name' => '増田 一世',
                'date' => '2025-05-02',
                'start_time' => '09:00',
                'end_time' => '18:00',
                'new_start_time' => '09:00',
                'new_end_time' => '18:00',
                'break_time' => [['start_time' => '12:00', 'end_time' => '13:00']],
                'note' => '打刻忘れがあったため',
                'status' => 'pending'
            ],
            [
                'user_name' => '山田 太郎',
                'date' => '2025-05-02',
                'start_time' => '09:30',
                'end_time' => '18:30',
                'new_start_time' => '09:30',
                'new_end_time' => '18:30',
                'break_time' => [['start_time' => '12:00', 'end_time' => '13:00']],
                'note' => '打刻忘れがあったため',
                'status' => 'pending'
            ],
        ];

        // 特定の修正申請データを作成
        foreach ($pendingRevisions as $revisionData) {
            // ユーザーを名前で検索
            $user = User::where('name', $revisionData['user_name'])->first();

            if (!$user) {
                $this->command->info("ユーザー「{$revisionData['user_name']}」が見つかりません。");
                continue;
            }

            // 対象日の勤怠データを作成/取得
            $date = Carbon::parse($revisionData['date']);
            $attendance = Attendance::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $date->toDateString()
                ],
                [
                    'start_time' => $date->copy()->setTimeFromTimeString($revisionData['start_time']),
                    'end_time' => $date->copy()->setTimeFromTimeString($revisionData['end_time']),
                    'status' => Attendance::STATUS_FINISHED
                ]
            );

            // 休憩時間の作成
            foreach ($revisionData['break_time'] as $breakData) {
                BreakTime::firstOrCreate(
                    [
                        'attendance_id' => $attendance->id,
                        'start_time' => $date->copy()->setTimeFromTimeString($breakData['start_time']),
                        'end_time' => $date->copy()->setTimeFromTimeString($breakData['end_time'])
                    ]
                );
            }

            // 修正申請の作成
            RevisionRequest::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'attendance_id' => $attendance->id
                ],
                [
                    'old_start_time' => $attendance->start_time,
                    'new_start_time' => $date->copy()->setTimeFromTimeString($revisionData['new_start_time']),
                    'old_end_time' => $attendance->end_time,
                    'new_end_time' => $date->copy()->setTimeFromTimeString($revisionData['new_end_time']),
                    'break_modifications' => json_encode($revisionData['break_time']),
                    'note' => $revisionData['note'],
                    'status' => $revisionData['status']
                ]
            );
        }

        // 各ユーザーごとに20件のデータを確実に作成する
        foreach ($users as $user) {
            $this->generateAttendanceForUser($user, $today);
        }

        $this->command->info('勤怠データと修正申請データを整合性を持って生成しました！');
    }

    /**
     * 指定ユーザーに対して20件の勤怠データを生成する
     * 
     * @param User $user
     * @param Carbon $today
     */
    private function generateAttendanceForUser(User $user, Carbon $today)
    {
        // 既存の勤怠データカウント（特定の修正申請で既に作成されたデータも含む）
        $existingCount = Attendance::where('user_id', $user->id)->count();
        
        // 必要な追加データ数を計算（合計20件になるように）
        $requiredCount = 20 - $existingCount;
        
        // 20件未満の場合は追加データを生成
        if ($requiredCount > 0) {
            // 開始日を計算（過去の日付から）
            $startDate = $today->copy()->subDays(30); // 十分に過去の日付から開始
            
            // 必要な件数分のデータを生成
            $generatedCount = 0;
            $currentDate = $startDate->copy();
            
            // 必要な件数を生成するまでループ
            while ($generatedCount < $requiredCount && $currentDate->isBefore($today)) {
                // 既に存在するデータを除外
                if (!Attendance::where('user_id', $user->id)
                    ->where('date', $currentDate->toDateString())
                    ->exists()) {
                    
                    // 土日かどうかチェック
                    if ($currentDate->isWeekend()) {
                        // 土日は特殊な勤務時間
                        $startHour = rand(10, 12);
                        $endHour = rand(15, 17);
                    } else {
                        // 平日は通常勤務
                        $startHour = rand(8, 10);
                        $endHour = rand(17, 19);
                    }
                    
                    // 15分単位の時間を設定
                    $startMin = [0, 15, 30, 45][rand(0, 3)];
                    $endMin = [0, 15, 30, 45][rand(0, 3)];
                    
                    $startTime = $currentDate->copy()->setHour($startHour)->setMinute($startMin)->setSecond(0);
                    $endTime = $currentDate->copy()->setHour($endHour)->setMinute($endMin)->setSecond(0);
                    
                    // 勤怠データを作成
                    $attendance = Attendance::create([
                        'user_id' => $user->id,
                        'date' => $currentDate->toDateString(),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => Attendance::STATUS_FINISHED,
                    ]);
                    
                    // 休憩データを作成（昼休み）
                    $breakStart = $currentDate->copy()->setHour(12)->setMinute(0)->setSecond(0);
                    $breakEnd = $currentDate->copy()->setHour(13)->setMinute(0)->setSecond(0);
                    
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => $breakStart,
                        'end_time' => $breakEnd
                    ]);
                    
                    // 20%の確率で修正申請も作成
                    if (rand(0, 4) === 0) {
                        // 修正申請の種類
                        $notes = [
                            '遅刻のため修正申請します',
                            '実際の退勤時間に合わせて修正',
                            '打刻忘れがあったため',
                            '残業時間の修正',
                            '出勤・退勤時間の修正'
                        ];
                        
                        // 承認状態（50%の確率で承認済み）
                        $status = (rand(0, 1) === 0) ? 'approved' : 'pending';
                        
                        // 新しい時間（少し調整）
                        $newStartTime = $startTime->copy()->addMinutes(rand(-15, 15));
                        $newEndTime = $endTime->copy()->addMinutes(rand(15, 30));
                        
                        // 休憩時間の修正データ
                        $breakModifications = [
                            [
                                'start_time' => '12:00',
                                'end_time' => '13:00'
                            ]
                        ];
                        
                        // 修正申請を作成
                        RevisionRequest::create([
                            'user_id' => $user->id,
                            'attendance_id' => $attendance->id,
                            'old_start_time' => $startTime,
                            'new_start_time' => $newStartTime,
                            'old_end_time' => $endTime,
                            'new_end_time' => $newEndTime,
                            'break_modifications' => json_encode($breakModifications),
                            'note' => $notes[array_rand($notes)],
                            'status' => $status,
                        ]);
                    }
                    
                    $generatedCount++;
                }
                
                // 次の日に進む
                $currentDate->addDay();
            }
            
            // エラーを解消するための修正
            $total = $existingCount + $generatedCount;
            $this->command->info("ユーザー「{$user->name}」に {$generatedCount} 件の追加データを生成しました。合計: {$total} 件");
        } else {
            $this->command->info("ユーザー「{$user->name}」は既に {$existingCount} 件のデータがあります。追加は不要です。");
        }
    }
}
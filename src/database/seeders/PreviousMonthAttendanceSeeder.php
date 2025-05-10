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
    public function run()
    {
        $users = User::where('role', 'user')->get();
        $today = Carbon::now();

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

     
        foreach ($pendingRevisions as $revisionData) {
            
            $user = User::where('name', $revisionData['user_name'])->first();

            if (!$user) {
                $this->command->info("ユーザー「{$revisionData['user_name']}」が見つかりません。");
                continue;
            }

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

            foreach ($revisionData['break_time'] as $breakData) {
                BreakTime::firstOrCreate(
                    [
                        'attendance_id' => $attendance->id,
                        'start_time' => $date->copy()->setTimeFromTimeString($breakData['start_time']),
                        'end_time' => $date->copy()->setTimeFromTimeString($breakData['end_time'])
                    ]
                );
            }

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

        foreach ($users as $user) {
            $this->generateAttendanceForUser($user, $today);
        }

        $this->command->info('勤怠データと修正申請データを整合性を持って生成しました！');
    }

    private function generateAttendanceForUser(User $user, Carbon $today)
    {
        $existingCount = Attendance::where('user_id', $user->id)->count();
        
        $requiredCount = 20 - $existingCount;
        
        if ($requiredCount > 0) {
           
            $startDate = $today->copy()->subDays(30); 
            
            $generatedCount = 0;
            $currentDate = $startDate->copy();
            

            while ($generatedCount < $requiredCount && $currentDate->isBefore($today)) {

                if (!Attendance::where('user_id', $user->id)
                    ->where('date', $currentDate->toDateString())
                    ->exists()) {
                    

                    if ($currentDate->isWeekend()) {
                 
                        $startHour = rand(10, 12);
                        $endHour = rand(15, 17);
                    } else {
                     
                        $startHour = rand(8, 10);
                        $endHour = rand(17, 19);
                    }
                    
                    $startMin = [0, 15, 30, 45][rand(0, 3)];
                    $endMin = [0, 15, 30, 45][rand(0, 3)];
                    
                    $startTime = $currentDate->copy()->setHour($startHour)->setMinute($startMin)->setSecond(0);
                    $endTime = $currentDate->copy()->setHour($endHour)->setMinute($endMin)->setSecond(0);
                    
                    $attendance = Attendance::create([
                        'user_id' => $user->id,
                        'date' => $currentDate->toDateString(),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => Attendance::STATUS_FINISHED,
                    ]);
                    
                    $breakStart = $currentDate->copy()->setHour(12)->setMinute(0)->setSecond(0);
                    $breakEnd = $currentDate->copy()->setHour(13)->setMinute(0)->setSecond(0);
                    
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => $breakStart,
                        'end_time' => $breakEnd
                    ]);
                    
                    if (rand(0, 4) === 0) {
                        $notes = [
                            '遅刻のため修正申請します',
                            '実際の退勤時間に合わせて修正',
                            '打刻忘れがあったため',
                            '残業時間の修正',
                            '出勤・退勤時間の修正'
                        ];
                        
                        $status = (rand(0, 1) === 0) ? 'approved' : 'pending';
                        
                        $newStartTime = $startTime->copy()->addMinutes(rand(-15, 15));
                        $newEndTime = $endTime->copy()->addMinutes(rand(15, 30));
                        
                        $breakModifications = [
                            [
                                'start_time' => '12:00',
                                'end_time' => '13:00'
                            ]
                        ];
                    
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
                
                $currentDate->addDay();
            }
            
            $total = $existingCount + $generatedCount;
            $this->command->info("ユーザー「{$user->name}」に {$generatedCount} 件の追加データを生成しました。合計: {$total} 件");
        } else {
            $this->command->info("ユーザー「{$user->name}」は既に {$existingCount} 件のデータがあります。追加は不要です。");
        }
    }
}
<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $attendances = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // テストユーザーを作成
        $this->user = User::factory()->create([
            'name' => 'test_user',
            'email_verified_at' => now(), 
        ]);

        // 現在月の勤怠データを作成
        $currentMonth = Carbon::now();
        $this->attendances[] = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $currentMonth->format('Y-m-d'), 
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 'finished' 
        ]);

        // 前月の勤怠データも作成
        $prevMonth = $currentMonth->copy()->subMonth();
        $this->attendances[] = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $prevMonth->format('Y-m-d'), 
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 'finished'
        ]);

        // 翌月の勤怠データも作成
        $nextMonth = $currentMonth->copy()->addMonth();
        $this->attendances[] = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $nextMonth->format('Y-m-d'), 
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 'finished'
        ]);

        // 休憩時間データを作成
        $this->attendances[0]->breakTimes()->create([
            'start_time' => '12:00',
            'end_time' => '13:00'
        ]);

        // 全テストでこのユーザーとしてログイン状態にする
        $this->actingAs($this->user);
    }

    //自分が行った勤怠情報が全て表示されている
        public function test_user_can_see_all_attendance_records()
    {
        $response = $this->get('/attendances');
        $response->assertStatus(200);
        $response->assertSee('勤怠一覧');

        // 日付が表示されていることを確認
        $response->assertSee('日付');
        $response->assertSee('出勤');
        $response->assertSee('退勤');
    }

    //勤怠一覧画面に遷移した際に現在の月が表示される
    public function test_current_month_is_displayed_in_attendance_list()
    {
        $response = $this->get('/attendances');
        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->format('Y/m'), false);
    }

    //「前月」を押下した時に表示月の前月の情報が表示される
    public function test_previous_month_button_shows_previous_month_data()
    {
        // 現在の月と前月を取得
        $currentMonth = Carbon::now();
        $prevMonth = $currentMonth->copy()->subMonth();
        
        // 前月パラメータ付きで勤怠一覧ページにアクセス
        $response = $this->get('/attendances?month=' . $prevMonth->format('Y-m')); 
        $response->assertStatus(200);
        $response->assertSee($prevMonth->format('Y/m'), false);
    }

    //「翌月」を押下した時に表示月の翌月の情報が表示される
    public function test_next_month_button_shows_next_month_data()
    {
        // 現在の月と翌月を取得
        $currentMonth = Carbon::now();
        $nextMonth = $currentMonth->copy()->addMonth();
        
        // 翌月パラメータ付きで勤怠一覧ページにアクセス
        $response = $this->get('/attendances?month=' . $nextMonth->format('Y-m'));
        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'), false);
    }

    //「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_detail_button_redirects_to_attendance_detail()
    {
        // テスト用の勤怠データを取得
        $attendance = $this->attendances[0];
        $response = $this->get('/attendances/' . $attendance->id);
        $response->assertStatus(200);
    }

    //勤怠詳細画面の「名前」がログインユーザーの氏名になっている
    public function test_attendance_detail_shows_correct_user_name()
    {
        // テスト用の勤怠データを取得
        $attendance = $this->attendances[0];
        
        $response = $this->get('/attendances/' . $attendance->id);
        $response->assertStatus(200);
        
        // ログインユーザーの名前が表示されているか確認
        $response->assertSee($this->user->name);
    }

    //勤怠詳細画面の「日付」が選択した日付になっている
    public function test_attendance_detail_shows_correct_date()
    {
        // テスト用の勤怠データを取得
        $attendance = $this->attendances[0];
        
        $response = $this->get('/attendances/' . $attendance->id);
        $response->assertStatus(200);
    
        $date = Carbon::parse($attendance->date);
        $response->assertSee($date->format('Y年'), false);
        
        $monthDay = $date->format('n月j日');
        $monthDayZeroPadded = $date->format('m月d日');

        $containsMonthDay = str_contains($response->getContent(), $monthDay) || 
                          str_contains($response->getContent(), $monthDayZeroPadded);
        
        $this->assertTrue($containsMonthDay, '月日の表示が見つかりません');
    }

    //「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
    public function test_attendance_detail_shows_correct_work_times()
    {
        // テスト用の勤怠データを取得
        $attendance = $this->attendances[0];
        
        $response = $this->get('/attendances/' . $attendance->id);
        $response->assertStatus(200);
        
        // 出勤時間と退勤時間が正しく表示されているか確認
        $response->assertSee($attendance->start_time); 
        $response->assertSee($attendance->end_time);   
    }

    //「休憩」にて記されている時間がログインユーザーの打刻と一致している
    public function test_attendance_detail_shows_correct_break_times()
    {
        // テスト用の勤怠データを取得
        $attendance = $this->attendances[0];
        
        $response = $this->get('/attendances/' . $attendance->id);
        $response->assertStatus(200);
        
        // 休憩開始時間と終了時間が正しく表示されているか確認
        $response->assertSee('12:00'); 
        $response->assertSee('13:00'); 
    }
}
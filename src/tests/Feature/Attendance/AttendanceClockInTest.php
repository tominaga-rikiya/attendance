<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceClockInTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // ユーザーを作成してログイン（初期状態は勤務外）
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($this->user);
    }

    /**
     * 出勤ボタンが正しく機能するかテスト
     */
    public function test_clock_in_button_works_correctly()
    {
        //画面に出勤ボタンが表示されていることを確認する
        $response = $this->get('/attendance');
        $response->assertSee('出勤');
        $response->assertSee('勤務外');

        //出勤の処理を行う
        $clockInResponse = $this->post('/attendance/clock-in');
        $clockInResponse->assertRedirect('/attendance');

        // 処理後の画面表示確認
        $afterResponse = $this->get('/attendance');
        $afterResponse->assertSee('出勤中');      
        $afterResponse->assertDontSee('出勤');    
        $afterResponse->assertSee('退勤');        
    }

    /**
     * 出勤は一日一回のみできることをテスト
     */
    public function test_can_clock_in_only_once_per_day()
    {
        //ステータスが退勤済であるユーザーにログインする
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => Carbon::now()->subHours(8),
            'end_time' => Carbon::now()->subHour(),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        //勤務ボタンが表示されないことを確認する
        $response = $this->get('/attendance');
        $response->assertDontSee('出勤');   
        $response->assertSee('退勤済');      
    }

    /**
     * 出勤時刻が管理画面で確認できることをテスト
     */
    public function test_clock_in_time_can_be_confirmed_in_admin_panel()
    {
        // テスト時間を固定
        $testTime = Carbon::create(2025, 5, 13, 9, 0, 0);
        Carbon::setTestNow($testTime);

        //出勤の処理を行う
        $this->post('/attendance/clock-in');

        // 管理者ユーザーを作成してログイン
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 管理画面にアクセス
        $response = $this->get('/admin/attendances');

        // 管理画面に出勤時刻が正確に記録されていることを確認
        $response->assertStatus(200);
        $response->assertSee($this->user->name);   
        $response->assertSee('09:00');    

        Carbon::setTestNow();
    }
}

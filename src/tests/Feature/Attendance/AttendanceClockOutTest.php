<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceClockOutTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $attendance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // ユーザーを作成してログイン（初期状態は勤務外）
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'admin user',
        ]);
        $this->actingAs($this->user);

        // ユーザーを出勤中状態に変更
        $this->attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => Carbon::now()->subHours(8),
            'status' => Attendance::STATUS_WORKING, 
        ]);
    }

    /**
     * 退勤ボタンが正しく機能するかテスト
     */
    public function test_clock_out_button_works_correctly()
    {
        // 退勤ボタンが表示されることを確認
        $response = $this->get('/attendance');
        $response->assertSee('退勤');

        // 退勤処理を実行
        $clockOutResponse = $this->post('/attendance/clock-out');
        $clockOutResponse->assertRedirect('/attendance'); 

        // データベースが更新されていることを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $this->attendance->id,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        // 退勤時刻が記録されていることを確認
        $updatedAttendance = Attendance::find($this->attendance->id);
        $this->assertNotNull($updatedAttendance->end_time);

        // 画面表示が変わっていることを確認
        $afterResponse = $this->get('/attendance');
        $afterResponse->assertSee('退勤済'); 
        $afterResponse->assertSee('お疲れ様でした');
    }

    /**
     * 退勤時刻が管理画面で確認できることをテスト
     */
    public function test_clock_out_time_can_be_confirmed_in_admin_panel()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        // 時刻を固定
        $now = Carbon::create(2025, 5, 13, 11, 23, 0);
        Carbon::setTestNow($now);

        // 出勤時刻を設定
        $this->attendance->update([
            'start_time' => $now,
        ]);

        // 退勤処理を実行
        $this->post('/attendance/clock-out');

        // 時刻固定を解除
        Carbon::setTestNow();

        // 管理者としてログインし直す
        $this->actingAs($admin);

        // 管理画面にアクセス
        $response = $this->get('/admin/attendances');

        // 管理画面で確認
        $response->assertStatus(200); 
        $response->assertSee('test_user');
        $response->assertSee('11:23'); 
    }
}

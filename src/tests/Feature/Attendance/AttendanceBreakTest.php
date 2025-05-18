<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceBreakTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $attendance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // ステータスが勤務外のユーザーを作成してログイン
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($this->user);

        // 出勤中状態の勤怠レコードを作成
        $this->attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => Carbon::now()->subHours(2),
            'status' => Attendance::STATUS_WORKING,
        ]);
    }

    
    //休憩ボタンが正しく機能するかテスト
    public function test_break_start_button_works_correctly()
    {
        //画面に休憩入ボタンが表示されていることを確認する
        $beforeResponse = $this->get('/attendance');
        $beforeResponse->assertSee('出勤中');
        $beforeResponse->assertSee('休憩入');

        //休憩の処理を行う
        $breakStartResponse = $this->post('/attendance/break-start');
        $breakStartResponse->assertRedirect('/attendance');

        $afterResponse = $this->get('/attendance');
        $afterResponse->assertSee('休憩中');
        $afterResponse->assertSee('休憩戻');
    }

    //休憩は一日に何回でもできることを確認するテスト
    public function test_can_take_multiple_breaks_per_day()
    {

        //休憩入と休憩戻の処理を行う
        $this->post('/attendance/break-start');
        $this->post('/attendance/break-end');

        //休憩入ボタンが表示されることを確認する
        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
        $response->assertSee('休憩入');

        // 再度休憩に入れることを確認
        $this->post('/attendance/break-start');
        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    
    //休憩戻ボタンが正しく機能するかテスト
    public function test_break_end_button_works_correctly()
    {
        //休憩入の処理を行う
        $this->post('/attendance/break-start');

        // 休憩中の状態を確認
        $breakResponse = $this->get('/attendance');
        $breakResponse->assertSee('休憩中');
        $breakResponse->assertSee('休憩戻');

        $this->post('/attendance/break-end');
        $afterResponse = $this->get('/attendance');
        $afterResponse->assertSee('出勤中');
    }

    
    //休憩戻は一日に何回でもできることを確認するテスト
    public function test_can_end_multiple_breaks_per_day()
    {
        //休憩入と休憩戻の処理を行い、再度休憩入の処理を行う
        $this->post('/attendance/break-start');
        $this->post('/attendance/break-end');
        $this->post('/attendance/break-start');

        //休憩戻ボタンが表示されることを確認する
        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
        $response->assertSee('休憩戻');
    }

    //休憩時刻が勤怠一覧画面で確認できることを確認するテスト
    public function test_break_times_can_be_confirmed_in_attendance_list()
    {
        //休憩入と休憩戻の処理を行う
        $this->post('/attendance/break-start');

        //30分の休憩時間をシミュレート
        Carbon::setTestNow(Carbon::now()->addMinutes(30));

        $this->post('/attendance/break-end');

        // 退勤処理
        $this->post('/attendance/clock-out');

        Carbon::setTestNow();

        //勤怠一覧画面から休憩の日付を確認する
        $response = $this->get('/attendances');
        $response->assertStatus(200);

        // 休憩時間が表示されていることを確認
        $this->assertMatchesRegularExpression('/\d{2}:\d{2}/', $response->getContent());
    }
}

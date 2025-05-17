<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // ステータスが勤務外のユーザーを作成してログイン
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($this->user);
    }

    /**
     * 勤務外の場合、勤怠ステータスが正しく表示されるテスト
     */
    public function test_status_shows_correctly_when_not_working()
    {
        //勤怠打刻画面を開く
        $response = $this->get('/attendance');

        //画面に表示されているステータスを確認する
        $response->assertSee('勤務外'); 
        $response->assertSee('出勤');  
    }

    /**
     * 出勤中の場合、勤怠ステータスが正しく表示されるテスト
     */
    public function test_status_shows_correctly_when_clocked_in()
    {
        // ステータスが出勤中のユーザーにログインする
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => Carbon::now()->subHours(1),
            'status' => Attendance::STATUS_WORKING,
        ]);

        //勤怠打刻画面を開く
        $response = $this->get('/attendance');

        //画面に表示されているステータスを確認する
        $response->assertSee('出勤中'); 
        $response->assertSee('休憩入'); 
        $response->assertSee('退勤');  
    }

    /**
     * 休憩中の場合、勤怠ステータスが正しく表示されるテスト
     */
    public function test_status_shows_correctly_when_on_break()
    {
        //ステータスが休憩中のユーザーにログインする
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => Carbon::now()->subHours(2),
            'status' => Attendance::STATUS_ON_BREAK,
        ]);

        // 休憩記録を作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::now()->subHour(),
            'end_time' => null
        ]);

        //勤怠打刻画面を開く
        $response = $this->get('/attendance');

        //画面に表示されているステータスを確認する
        $response->assertSee('休憩中'); 
        $response->assertSee('休憩戻');
    }

    /**
     * 退勤済の場合、勤怠ステータスが正しく表示されるテスト
     */
    public function test_status_shows_correctly_when_clocked_out()
    {
        //ステータスが退勤済のユーザーにログインする
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => Carbon::now()->subHours(8),
            'end_time' => Carbon::now()->subHour(),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        //勤怠打刻画面を開く
        $response = $this->get('/attendance');

        //画面に表示されているステータスを確認する
        $response->assertSee('退勤済');       
        $response->assertSee('お疲れ様でした'); 
        $response->assertDontSee('出勤');  
    }
}

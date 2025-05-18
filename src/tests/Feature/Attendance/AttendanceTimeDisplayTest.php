<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // ユーザーを作成してログイン
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($this->user);
    }

    //現在の日時情報がUIと同じ形式で表示されるかテスト
    public function test_current_date_time_is_displayed()
    {
        // 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // 現在の日付と時刻
        $now = Carbon::now();

        // 日付と時刻が表示されているか確認
        $response->assertSee($now->format('Y年'), false);
        $response->assertSee($now->format('月'), false);
        $response->assertSee($now->format('日'), false);

        // 時刻形式（HH:MM）が表示されているか確認
        $this->assertMatchesRegularExpression('/\d{1,2}:\d{2}/', $response->getContent());
    }
}

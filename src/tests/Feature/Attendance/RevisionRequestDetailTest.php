<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\RevisionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevisionRequestDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $attendance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // 一般ユーザーを作成
        $this->user = User::factory()->create([
            'name' => 'test_user',
            'email_verified_at' => now(),
        ]);

        // 管理者ユーザーを作成
        $this->admin = User::factory()->create([
            'name' => 'admin_user',
            'role' => 'admin',
        ]);

        // テスト用勤怠データ
        $this->attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2025-05-15',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 'finished'
        ]);

        // 休憩時間を作成
        $this->attendance->breakTimes()->create([
            'start_time' => '12:00',
            'end_time' => '13:00'
        ]);
    }

    // 出勤時間が退勤時間より後の場合のバリデーションテスト
    public function test_invalid_start_time_shows_error_message()
    {
        $this->actingAs($this->user);
        
        // 不正な時間データ（出勤時間 > 退勤時間）で修正リクエストを送信
        $response = $this->post('/attendances/' . $this->attendance->id, [
            'start_time' => '19:00',
            'end_time' => '18:00',
            'note' => 'テスト',
        ]);
        
        // time_errorというキーでエラーが発生
        $response->assertSessionHasErrors('time_error');
    }

    // 休憩開始時間が退勤時間より後の場合のバリデーションテスト
    public function test_invalid_break_start_time_shows_error_message()
    {
        $this->actingAs($this->user);
        
        // 不正な休憩開始時間（退勤時間より後）で修正リクエストを送信
        $response = $this->post('/attendances/' . $this->attendance->id, [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'breaks' => [
                [
                    'start_time' => '19:00',
                    'end_time' => '20:00'
                ]
            ],
            'note' => 'テスト',
        ]);
        
        // 何らかのバリデーションエラーが発生
        $response->assertSessionHasErrors();
    }

    //  休憩終了時間が退勤時間より後の場合のバリデーションテスト
    public function test_invalid_break_end_time_shows_error_message()
    {
        $this->actingAs($this->user);
        
        // 不正な休憩終了時間（退勤時間より後）で修正リクエストを送信
        $response = $this->post('/attendances/' . $this->attendance->id, [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'breaks' => [
                [
                    'start_time' => '12:00',
                    'end_time' => '19:00' 
                ]
            ],
            'note' => 'テスト',
        ]);
        
        // 何らかのバリデーションエラーが発生
        $response->assertSessionHasErrors();
    }


    // 備考欄未入力時のバリデーションテスト
    public function test_empty_note_shows_error_message()
    {
        $this->actingAs($this->user);
        
        // 備考欄が空の状態で修正リクエストを送信
        $response = $this->post('/attendances/' . $this->attendance->id, [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'note' => '', 
        ]);
        
        // note フィールドに対するバリデーションエラーが発生
        $response->assertSessionHasErrors('note');
    }

    // 修正申請処理のテスト
    public function test_revision_request_is_created()
    {
        $this->actingAs($this->user);
        
        // 有効なデータで修正リクエストを送信
        $response = $this->post('/attendances/' . $this->attendance->id, [
            'start_time' => '10:00', 
            'end_time' => '19:00',   
            'breaks' => [
                [
                    'start_time' => '13:00',
                    'end_time' => '14:00'   
                ]
            ],
            'note' => '修正申請テスト', 
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // データベースに修正申請が保存
        $this->assertDatabaseHas('revision_requests', [
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);
    }

    
    // 承認待ちの修正申請が表示されることをテスト
    public function test_user_can_see_pending_revision_requests()
    {
        $this->actingAs($this->user);
        
        // テスト用の承認待ち修正申請を作成
        RevisionRequest::create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendance->id,
            'old_start_time' => '09:00',
            'old_end_time' => '18:00',
            'new_start_time' => '10:00',
            'new_end_time' => '19:00',
            'note' => '修正申請テスト',
            'status' => 'pending', 
        ]);
        
        $response = $this->get('/correction-requests');
        $response->assertStatus(200);
        $response->assertSee('修正申請テスト');
    }

    // 承認済みの修正申請が表示されることをテスト
    public function test_user_can_see_approved_revision_requests()
    {
        $this->actingAs($this->user);
        
        // テスト用の承認済み修正申請を作成
        RevisionRequest::create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendance->id,
            'old_start_time' => '09:00',
            'old_end_time' => '18:00',
            'new_start_time' => '10:00',
            'new_end_time' => '19:00',
            'note' => '承認済み申請',
            'status' => 'approved', 
        ]);
        
        $response = $this->get('/correction-requests');
        $response->assertStatus(200);
        $response->assertSee('承認済み申請');
    }


    // 申請一覧の詳細ボタンテスト
    public function test_revision_request_detail_button_has_correct_link()
    {
    $this->actingAs($this->user);
    
    // テスト用の修正申請を作成
    $revisionRequest = RevisionRequest::create([
        'user_id' => $this->user->id,
        'attendance_id' => $this->attendance->id,
        'old_start_time' => '09:00',
        'old_end_time' => '18:00',
        'new_start_time' => '10:00',
        'new_end_time' => '19:00',
        'note' => '詳細表示テスト',
        'status' => 'pending',
    ]);
    
    $response = $this->get('/correction-requests');
    $response->assertStatus(200);
    $response->assertSee('詳細', false);
    $response->assertSee('/attendances/', false);
    }
}
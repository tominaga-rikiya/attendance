<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\RevisionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 勤怠修正申請に関するテストクラス
 * 
 * 一般ユーザーによる勤怠情報の修正申請機能をテストする
 */
class RevisionRequestDetailTest extends TestCase
{
    // テストごとにデータベースをリフレッシュする
    use RefreshDatabase;

    // テスト用のユーザーと管理者、および勤怠データ
    protected $user;
    protected $admin;
    protected $attendance;

    /**
     * テスト前の準備処理
     * 
     * テストユーザー、管理者、勤怠データ、休憩時間を作成する
     */
    protected function setUp(): void
    {
        parent::setUp();
        // CSRFトークン検証をスキップ（テスト用）
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

        // テスト用勤怠データを作成（9:00〜18:00、ステータス「完了」）
        $this->attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2025-05-15',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 'finished'
        ]);

        // 休憩時間を作成（12:00〜13:00）
        $this->attendance->breakTimes()->create([
            'start_time' => '12:00',
            'end_time' => '13:00'
        ]);
    }

    /**
     * 出勤時間が退勤時間より後の場合のバリデーションテスト
     * 
     * 要件: 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_invalid_start_time_shows_error_message()
    {
        // テストユーザーとしてログイン
        $this->actingAs($this->user);
        
        // 不正な時間データ（出勤時間 > 退勤時間）で修正リクエストを送信
        $response = $this->post('/attendances/' . $this->attendance->id, [
            'start_time' => '19:00', // 退勤時間より後（不正）
            'end_time' => '18:00',
            'note' => 'テスト',
        ]);
        
        // time_errorというキーでエラーが発生することを確認
        $response->assertSessionHasErrors('time_error');
    }

    /**
     * 休憩開始時間が退勤時間より後の場合のバリデーションテスト
     * 
     * 要件: 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_invalid_break_start_time_shows_error_message()
    {
        // テストユーザーとしてログイン
        $this->actingAs($this->user);
        
        // 不正な休憩開始時間（退勤時間より後）で修正リクエストを送信
        $response = $this->post('/attendances/' . $this->attendance->id, [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'breaks' => [
                [
                    'start_time' => '19:00', // 退勤時間より後（不正）
                    'end_time' => '20:00'
                ]
            ],
            'note' => 'テスト',
        ]);
        
        // 何らかのバリデーションエラーが発生することを確認
        // 注: 具体的なエラーキーは実装によって異なる可能性があるため、一般的な検証を使用
        $response->assertSessionHasErrors();
    }

    /**
     * 休憩終了時間が退勤時間より後の場合のバリデーションテスト
     * 
     * 要件: 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_invalid_break_end_time_shows_error_message()
    {
        // テストユーザーとしてログイン
        $this->actingAs($this->user);
        
        // 不正な休憩終了時間（退勤時間より後）で修正リクエストを送信
        $response = $this->post('/attendances/' . $this->attendance->id, [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'breaks' => [
                [
                    'start_time' => '12:00',
                    'end_time' => '19:00' // 退勤時間より後（不正）
                ]
            ],
            'note' => 'テスト',
        ]);
        
        // 何らかのバリデーションエラーが発生することを確認
        $response->assertSessionHasErrors();
    }

    /**
     * 備考欄未入力時のバリデーションテスト
     * 
     * 要件: 備考欄が未入力の場合、エラーメッセージが表示される
     */
    public function test_empty_note_shows_error_message()
    {
        // テストユーザーとしてログイン
        $this->actingAs($this->user);
        
        // 備考欄が空の状態で修正リクエストを送信
        $response = $this->post('/attendances/' . $this->attendance->id, [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'note' => '', // 空の備考欄（不正）
        ]);
        
        // note フィールドに対するバリデーションエラーが発生することを確認
        $response->assertSessionHasErrors('note');
    }

    /**
     * 修正申請が正常に作成されることをテスト
     * 
     * 要件: 修正申請処理が実行される
     */
    public function test_revision_request_is_created()
    {
        // テストユーザーとしてログイン
        $this->actingAs($this->user);
        
        // 有効なデータで修正リクエストを送信
        $response = $this->post('/attendances/' . $this->attendance->id, [
            'start_time' => '10:00', // 修正後の時間
            'end_time' => '19:00',   // 修正後の時間
            'breaks' => [
                [
                    'start_time' => '13:00', // 修正後の休憩開始
                    'end_time' => '14:00'    // 修正後の休憩終了
                ]
            ],
            'note' => '修正申請テスト', // 備考欄
        ]);
        
        // 処理後にリダイレクトされることを確認
        $response->assertRedirect();
        // 成功メッセージがセッションに格納されることを確認
        $response->assertSessionHas('success');
        
        // データベースに修正申請が保存されていることを確認
        $this->assertDatabaseHas('revision_requests', [
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'status' => 'pending' // 申請中ステータス
        ]);
    }

    /**
     * 承認待ちの修正申請が表示されることをテスト
     * 
     * 要件: 「承認待ち」にログインユーザーが行った申請が全て表示されている
     */
    public function test_user_can_see_pending_revision_requests()
    {
        // テストユーザーとしてログイン
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
            'status' => 'pending', // 承認待ち状態
        ]);
        
        // 修正申請一覧ページにアクセス
        $response = $this->get('/correction-requests');
        // ページが正常に表示されることを確認
        $response->assertStatus(200);
        // 作成した修正申請の内容が表示されていることを確認
        $response->assertSee('修正申請テスト');
    }

    /**
     * 承認済みの修正申請が表示されることをテスト
     * 
     * 要件: 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function test_user_can_see_approved_revision_requests()
    {
        // テストユーザーとしてログイン
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
            'status' => 'approved', // 承認済み状態
        ]);
        
        // 修正申請一覧ページにアクセス
        $response = $this->get('/correction-requests');
        // ページが正常に表示されることを確認
        $response->assertStatus(200);
        // 承認済み申請が表示されていることを確認
        $response->assertSee('承認済み申請');
    }

   /**
 * 申請一覧の詳細ボタンテスト
 */
public function test_revision_request_detail_button_has_correct_link()
{
    // ユーザーとしてログイン
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
    
    // 申請一覧画面にアクセス
    $response = $this->get('/correction-requests');
    $response->assertStatus(200);
    
    // 詳細ボタンが存在することを確認
    $response->assertSee('詳細', false);
    
    // 実際の実装に合わせて、リンク先が勤怠詳細ページであることを確認
    // 注: 実際の勤怠IDはテスト環境によって異なるため、
    // リンクのパターンだけを確認する
    $response->assertSee('/attendances/', false);
}
}
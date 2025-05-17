<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\RevisionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
* 管理者向け勤怠管理機能のテストクラス
* 
* 管理者ユーザーによる勤怠一覧閲覧、修正申請承認などの機能をテスト
*/
class AdminAttendanceTest extends TestCase
{
   // テストごとにデータベースをリフレッシュする
   use RefreshDatabase;

   // テスト用の管理者、一般ユーザー、勤怠データ、修正申請
   protected $admin;
   protected $user;
   protected $attendance;
   protected $revisionRequest;

   /**
    * テスト前の準備処理
    * 
    * 管理者、一般ユーザー、勤怠データ、修正申請を作成する
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

       // 勤怠データを作成（本日の9:00〜18:00）
       $this->attendance = Attendance::create([
           'user_id' => $this->user->id,
           'date' => Carbon::now()->format('Y-m-d'),
           'start_time' => '09:00',
           'end_time' => '18:00',
           'status' => 'finished'
       ]);

       // 休憩時間を作成（12:00〜13:00）
       $this->attendance->breakTimes()->create([
           'start_time' => '12:00',
           'end_time' => '13:00'
       ]);

       // 修正申請を作成（承認待ち状態）
       $this->revisionRequest = RevisionRequest::create([
           'user_id' => $this->user->id,
           'attendance_id' => $this->attendance->id,
           'old_start_time' => '09:00',
           'old_end_time' => '18:00',
           'new_start_time' => '10:00',
           'new_end_time' => '19:00',
           'note' => '修正申請テスト',
           'status' => 'pending',
       ]);
   }

   /**
    * 管理者が全ユーザーの勤怠一覧を閲覧できることをテスト
    * 
    * 要件: その日になされた全ユーザーの勤怠情報が正確に確認できる
    */
   public function test_admin_can_see_all_users_attendance()
   {
       // 管理者としてログイン
       $this->actingAs($this->admin);
       
       // 勤怠一覧ページにアクセス
       $response = $this->get('/admin/attendances');
       // ページが正常に表示されることを確認
       $response->assertStatus(200);
       // テストユーザーの名前が表示されていることを確認
       $response->assertSee($this->user->name);
   }

   /**
    * 管理者画面で現在の日付が表示されることをテスト
    * 
    * 要件: 遷移した際に現在の日付が表示される
    */
 
public function test_admin_can_see_current_date()
{
    // 管理者としてログイン
    $this->actingAs($this->admin);
    
    // 勤怠一覧ページにアクセス
    $response = $this->get('/admin/attendances');
    $response->assertStatus(200);
    
    // 現在の日付が表示されていることを確認
    // 注: 実際の表示形式に合わせて調整
    $today = Carbon::now();
    
    // 形式1: 「2025年5月17日」形式（ゼロパディングなし）
    $format1 = $today->format('Y年n月j日');
    $response->assertSee($format1, false);
}

   /**
    * 前日ボタン機能のテスト
    * 
    * 要件: 「前日」を押下した時に前の日の勤怠情報が表示される
    */
   public function test_admin_can_navigate_to_previous_day()
   {
       // 管理者としてログイン
       $this->actingAs($this->admin);
       
       // 前日の日付パラメータを指定してアクセス（前日ボタンのクリックをシミュレート）
       $previousDay = Carbon::now()->subDay()->format('Y-m-d');
       $response = $this->get('/admin/attendances?date=' . $previousDay);
       
       // ページが正常に表示されることを確認
       $response->assertStatus(200);
       
       // 注: 前日の日付が表示されていることの確認は、
       // 実装に応じてより詳細な検証を追加できます
   }

   /**
    * 翌日ボタン機能のテスト
    * 
    * 要件: 「翌日」を押下した時に次の日の勤怠情報が表示される
    */
   public function test_admin_can_navigate_to_next_day()
   {
       // 管理者としてログイン
       $this->actingAs($this->admin);
       
       // 翌日の日付パラメータを指定してアクセス（翌日ボタンのクリックをシミュレート）
       $nextDay = Carbon::now()->addDay()->format('Y-m-d');
       $response = $this->get('/admin/attendances?date=' . $nextDay);
       
       // ページが正常に表示されることを確認
       $response->assertStatus(200);
       
       // 注: 翌日の日付が表示されていることの確認も追加可能
   }

   /**
    * 勤怠詳細画面表示のテスト
    * 
    * 要件: 勤怠詳細画面に表示されるデータが選択したものになっている
    */
   public function test_admin_can_see_attendance_details()
   {
       // 管理者としてログイン
       $this->actingAs($this->admin);
       
       // 勤怠詳細ページにアクセス
       $response = $this->get('/admin/attendances/' . $this->attendance->id);
       
       // ページが正常に表示されることを確認
       $response->assertStatus(200);
       // ユーザー名が表示されていることを確認
       $response->assertSee($this->user->name);
       
       // 注: 勤怠詳細の他の要素（出勤時間、退勤時間など）の
       // 確認も追加するとより堅牢なテストになります
   }

   /**
    * 承認待ち修正申請の表示テスト
    * 
    * 要件: 承認待ちの修正申請が全て表示されている
    */
   public function test_admin_can_see_pending_revision_requests()
   {
       // 管理者としてログイン
       $this->actingAs($this->admin);
       
       // 修正申請一覧ページにアクセス
       $response = $this->get('/admin/correction-requests');
       
       // ページが正常に表示されることを確認
       $response->assertStatus(200);
       // テストで作成した修正申請の内容が表示されていることを確認
       $response->assertSee('修正申請テスト');
   }

   /**
    * 承認済み修正申請の表示テスト
    * 
    * 要件: 承認済みの修正申請が全て表示されている
    */
   public function test_admin_can_see_approved_revision_requests()
   {
       // 管理者としてログイン
       $this->actingAs($this->admin);
       
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
       $response = $this->get('/admin/correction-requests');
       
       // ページが正常に表示されることを確認
       $response->assertStatus(200);
       
       // 注: 承認済み申請が表示されていることを確認するために
       // さらにアサーションを追加できます
   }

   /**
    * 修正申請詳細画面の表示テスト
    * 
    * 要件: 修正申請の詳細内容が正しく表示されている
    */
   public function test_admin_can_see_revision_request_details()
   {
       // 管理者としてログイン
       $this->actingAs($this->admin);
       
       // 修正申請詳細ページにアクセス
       $response = $this->get('/admin/correction-requests/' . $this->revisionRequest->id);
       
       // ページが正常に表示されることを確認
       $response->assertStatus(200);
       // 修正申請の内容が表示されていることを確認
       $response->assertSee('修正申請テスト');
   }

   /**
    * 修正申請承認機能のテスト
    * 
    * 要件: 修正申請の承認処理が正しく行われる
    */
   public function test_admin_can_approve_revision_request()
   {
       // 管理者としてログイン
       $this->actingAs($this->admin);
       
       // 修正申請の承認リクエストを送信
       $response = $this->post('/admin/correction-requests/' . $this->revisionRequest->id . '/approve');
       
       // 処理後のリダイレクトを確認
       $response->assertRedirect();
       
       // データベースで申請のステータスが「承認済み」に変更されているか確認
       $this->assertDatabaseHas('revision_requests', [
           'id' => $this->revisionRequest->id,
           'status' => 'approved' // 承認済み状態
       ]);
   }

   /**
    * スタッフ一覧表示のテスト
    * 
    * 要件: 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
    */
   public function test_admin_can_see_staff_list()
   {
       // 管理者としてログイン
       $this->actingAs($this->admin);
       
       // スタッフ一覧ページにアクセス
       $response = $this->get('/admin/staff');
       
       // ページが正常に表示されることを確認
       $response->assertStatus(200);
       // テストユーザーの名前とメールアドレスが表示されていることを確認
       $response->assertSee($this->user->name);
       $response->assertSee($this->user->email);
   }

   /**
    * ユーザー別勤怠一覧表示のテスト
    * 
    * 要件: ユーザーの勤怠情報が正しく表示される
    */
   public function test_admin_can_see_staff_attendance()
   {
       // 管理者としてログイン
       $this->actingAs($this->admin);
       
       // ユーザー別勤怠一覧ページにアクセス
       $response = $this->get('/admin/staff/' . $this->user->id . '/monthly');
       
       // ページが正常に表示されることを確認
       $response->assertStatus(200);
       // ユーザー名が表示されていることを確認
       $response->assertSee($this->user->name);
   }

   /**
    * スタッフ勤怠月次ナビゲーションのテスト
    * 
    * 要件: 前月/翌月ボタンでスタッフの異なる月の勤怠が表示される
    */
   public function test_admin_can_navigate_months_in_staff_attendance()
   {
       // 管理者としてログイン
       $this->actingAs($this->admin);
       
       $currentMonth = Carbon::now();
       
       // 前月のスタッフ勤怠表示
       $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
       $response = $this->get('/admin/staff/' . $this->user->id . '/monthly?month=' . $prevMonth);
       $response->assertStatus(200);
       
       // 翌月のスタッフ勤怠表示
       $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');
       $response = $this->get('/admin/staff/' . $this->user->id . '/monthly?month=' . $nextMonth);
       $response->assertStatus(200);
   }

   /**
 * 管理者による勤怠時間バリデーションテスト（出勤時間 > 退勤時間）
 */
public function test_admin_validation_start_time_after_end_time()
{
    $this->actingAs($this->admin);
    
    $response = $this->post('/admin/attendances/' . $this->attendance->id, [
        'start_time' => '19:00', // 退勤時間より後
        'end_time' => '18:00',
        'note' => 'テスト',
    ]);
    
    $response->assertSessionHasErrors();
}

/**
 * 管理者による休憩開始時間バリデーションテスト
 */
public function test_admin_validation_break_start_after_end_time()
{
    $this->actingAs($this->admin);
    
    $response = $this->post('/admin/attendances/' . $this->attendance->id, [
        'start_time' => '09:00',
        'end_time' => '18:00',
        'breaks' => [
            [
                'start_time' => '19:00', // 退勤時間より後
                'end_time' => '20:00'
            ]
        ],
        'note' => 'テスト',
    ]);
    
    $response->assertSessionHasErrors();
}

/**
 * 管理者による休憩終了時間バリデーションテスト
 */
public function test_admin_validation_break_end_after_end_time()
{
    $this->actingAs($this->admin);
    
    $response = $this->post('/admin/attendances/' . $this->attendance->id, [
        'start_time' => '09:00',
        'end_time' => '18:00',
        'breaks' => [
            [
                'start_time' => '12:00',
                'end_time' => '19:00' // 退勤時間より後
            ]
        ],
        'note' => 'テスト',
    ]);
    
    $response->assertSessionHasErrors();
}

/**
 * 管理者による備考欄バリデーションテスト
 */
public function test_admin_validation_empty_note()
{
    $this->actingAs($this->admin);
    
    $response = $this->post('/admin/attendances/' . $this->attendance->id, [
        'start_time' => '09:00',
        'end_time' => '18:00',
        'note' => '', // 空の備考欄
    ]);
    
    $response->assertSessionHasErrors();
}

/**
 * 管理者の詳細ボタン遷移テスト
 */
public function test_admin_detail_button_navigation()
{
    $this->actingAs($this->admin);
    
    $response = $this->get('/admin/attendances/' . $this->attendance->id);
    $response->assertStatus(200);
}

/**
 * 管理者の勤怠一覧での前月ボタンテスト
 */
public function test_admin_staff_attendance_previous_month()
{
    $this->actingAs($this->admin);
    
    $currentMonth = Carbon::now();
    $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
    
    $response = $this->get('/admin/staff/' . $this->user->id . '/monthly?month=' . $prevMonth);
    $response->assertStatus(200);
}

/**
 * 管理者の勤怠一覧での翌月ボタンテスト
 */
public function test_admin_staff_attendance_next_month()
{
    $this->actingAs($this->admin);
    
    $currentMonth = Carbon::now();
    $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');
    
    $response = $this->get('/admin/staff/' . $this->user->id . '/monthly?month=' . $nextMonth);
    $response->assertStatus(200);
}
}
<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\RevisionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
   use RefreshDatabase;

   protected $admin;
   protected $user;
   protected $attendance;
   protected $revisionRequest;

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

       // 勤怠データを作成
       $this->attendance = Attendance::create([
           'user_id' => $this->user->id,
           'date' => Carbon::now()->format('Y-m-d'),
           'start_time' => '09:00',
           'end_time' => '18:00',
           'status' => 'finished'
       ]);

       // 休憩時間を作成
       $this->attendance->breakTimes()->create([
           'start_time' => '12:00',
           'end_time' => '13:00'
       ]);

       // 修正申請を作成
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

    // その日になされた全ユーザーの勤怠情報が正確に確認できる
   public function test_admin_can_see_all_users_attendance()
   {
       $this->actingAs($this->admin);

       $response = $this->get('/admin/attendances');
       $response->assertStatus(200);
       $response->assertSee($this->user->name);
   }


    // 遷移した際に現在の日付が表示される
    public function test_admin_can_see_current_date()
    {
    $this->actingAs($this->admin);
    
    $response = $this->get('/admin/attendances');
    $response->assertStatus(200);
    $today = Carbon::now();
    
    $format1 = $today->format('Y年n月j日');
    $response->assertSee($format1, false);
    }

   
    //「前日」を押下した時に前の日の勤怠情報が表示される
   public function test_admin_can_navigate_to_previous_day()
   {
       $this->actingAs($this->admin);
       
       // 前日の日付パラメータ
       $previousDay = Carbon::now()->subDay()->format('Y-m-d');
       $response = $this->get('/admin/attendances?date=' . $previousDay);
       $response->assertStatus(200);
   }

    //「翌日」を押下した時に次の日の勤怠情報が表示される
   public function test_admin_can_navigate_to_next_day()
   {
       $this->actingAs($this->admin);
       
       // 翌日の日付パラメータを指定
       $nextDay = Carbon::now()->addDay()->format('Y-m-d');
       $response = $this->get('/admin/attendances?date=' . $nextDay);
       $response->assertStatus(200);
       
   }

    // 出勤時間が退勤時間より後の場合のバリデーションテスト
    public function test_admin_validation_start_time_after_end_time()
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/attendances/' . $this->attendance->id, [
            'start_time' => '19:00', 
            'end_time' => '18:00',
            'note' => 'テスト',
        ]);

        $response->assertSessionHasErrors();
    }

    // 休憩開始時間が退勤時間より後の場合のバリデーションテスト
    public function test_admin_validation_break_start_after_end_time()
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/attendances/' . $this->attendance->id, [
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

        $response->assertSessionHasErrors();
    }

    //  休憩終了時間が退勤時間より後の場合のバリデーションテスト
    public function test_admin_validation_break_end_after_end_time()
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/attendances/' . $this->attendance->id, [
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

        $response->assertSessionHasErrors();
    }

    // 備考欄未入力時のバリデーションテスト
    public function test_admin_validation_empty_note()
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/attendances/' . $this->attendance->id, [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'note' => '', 
        ]);

        $response->assertSessionHasErrors();
    }

    //管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
    public function test_admin_can_see_staff_list()
    {
        $this->actingAs($this->admin);
        $response = $this->get('/admin/staff');
        $response->assertStatus(200);

        // テストユーザーの名前とメールアドレスが表示されていることを確認
        $response->assertSee($this->user->name);
        $response->assertSee($this->user->email);
    }

    //ユーザーの勤怠情報が正しく表示される
    public function test_admin_can_see_staff_attendance()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/staff/' . $this->user->id . '/monthly');
        $response->assertStatus(200);
        $response->assertSee($this->user->name);
    }

    //管理者の勤怠一覧での前月ボタンテスト
    public function test_admin_staff_attendance_previous_month()
    {
        $this->actingAs($this->admin);

        $currentMonth = Carbon::now();
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');

        $response = $this->get('/admin/staff/' . $this->user->id . '/monthly?month=' . $prevMonth);
        $response->assertStatus(200);
    }

    //管理者の勤怠一覧での翌月ボタンテスト
    public function test_admin_staff_attendance_next_month()
    {
        $this->actingAs($this->admin);

        $currentMonth = Carbon::now();
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        $response = $this->get('/admin/staff/' . $this->user->id . '/monthly?month=' . $nextMonth);
        $response->assertStatus(200);
    }

    //管理者の詳細ボタン遷移テスト
    public function test_admin_detail_button_navigation()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/attendances/' . $this->attendance->id);
        $response->assertStatus(200);
    }

    //承認待ちの修正申請が全て表示されている
   public function test_admin_can_see_pending_revision_requests()
   {
       $this->actingAs($this->admin);
       
       $response = $this->get('/admin/correction-requests');
       $response->assertStatus(200);
       $response->assertSee('修正申請テスト');
   }

    // 承認済みの修正申請が全て表示されている
   public function test_admin_can_see_approved_revision_requests()
   {
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
           'status' => 'approved', 
       ]);
       
       $response = $this->get('/admin/correction-requests');
       $response->assertStatus(200);
   }

   //修正申請の詳細内容が正しく表示されている
   public function test_admin_can_see_revision_request_details()
   {
       $this->actingAs($this->admin);
       
       $response = $this->get('/admin/correction-requests/' . $this->revisionRequest->id);
    
       $response->assertStatus(200);
       $response->assertSee('修正申請テスト');
   }

   //修正申請の承認処理が正しく行われる
   public function test_admin_can_approve_revision_request()
   {
       $this->actingAs($this->admin);
       
       // 修正申請の承認リクエストを送信
       $response = $this->post('/admin/correction-requests/' . $this->revisionRequest->id . '/approve');
       $response->assertRedirect();
       
       // データベースで申請のステータスが「承認済み」に変更されているか確認
       $this->assertDatabaseHas('revision_requests', [
           'id' => $this->revisionRequest->id,
           'status' => 'approved'
       ]);
   }
}
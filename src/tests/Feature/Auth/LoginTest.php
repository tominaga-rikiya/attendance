<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{
   
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    /**
     * テスト用の一般ユーザーを作成
     */
    private function createUser()
    {
        return User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(), 
        ]);
    }

    /**
     * メールアドレスが未入力の場合のバリデーションテスト
     */
    public function test_email_is_required_for_login()
    {
        
        $this->createUser();

        
        $response = $this->post('/login', [
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(session()->hasOldInput('password'));
        $response->assertStatus(302);
        $this->assertEquals('メールアドレスを入力してください', session('errors')->first('email'));
    }

    /**
     * パスワードが未入力の場合のバリデーションテスト
     */
    public function test_password_is_required_for_login()
    {
        $this->createUser();

        $response = $this->post('/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $response->assertStatus(302);
        $this->assertEquals('パスワードを入力してください', session('errors')->first('password'));
    }

    /**
     * 登録内容と一致しない場合のバリデーションテスト
     */
    public function test_credentials_must_match()
    {
        $this->createUser();

        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $response->assertStatus(302);
        $this->assertEquals('ログイン情報が登録されていません', session('errors')->first('email'));
    }


    /**
     * テスト用の管理者ユーザーを作成
     */
    private function createAdminUser()
    {
        return User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);
    }

    /**
     * 管理者：メールアドレスが未入力の場合のバリデーションテスト
     */
    public function test_admin_email_is_required_for_login()
    {
        $this->createAdminUser();

        $response = $this->post('/admin/login', [
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(session()->hasOldInput('password'));
        $response->assertStatus(302);
        $this->assertEquals('メールアドレスを入力してください', session('errors')->first('email'));
    }

    /**
     * 管理者：パスワードが未入力の場合のバリデーションテスト
     */
    public function test_admin_password_is_required_for_login()
    {
        $this->createAdminUser();

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $response->assertStatus(302);
        $this->assertEquals('パスワードを入力してください', session('errors')->first('password'));
    }

    /**
     * 管理者：登録内容と一致しない場合のバリデーションテスト
     */
    public function test_admin_credentials_must_match()
    {
        $this->createAdminUser();

        $response = $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
            'admin' => '1',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $response->assertStatus(302);
        $this->assertEquals('ログイン情報が登録されていません', session('errors')->first('email'));
    }

    /**
     * 一般ユーザーが管理者ログインを試みた場合のテスト
     */
    public function test_normal_user_cannot_login_as_admin()
    {
        $this->createUser();

        $response = $this->post('/admin/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'admin' => '1', // 
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $response->assertStatus(302);
        $this->assertEquals('ログイン情報が登録されていません', session('errors')->first('email'));
    }

}
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    
    //会員登録後に認証メールが送信されるかテスト
    public function test_verification_email_is_sent_after_registration()
    {
        Mail::fake();
        Event::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        Event::assertDispatched(Registered::class);

        $response->assertRedirect('/email/verify');

        $this->assertTrue(session()->has('unauthenticated_user'));
    }

    
    //メール認証誘導画面の「認証はこちらから」ボタン機能テスト
    public function test_verification_redirect_button_works()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        session()->put('unauthenticated_user', $user);

        $response = $this->get('/email/verify');
        $response->assertStatus(200);
        $response->assertSee('認証はこちらから');

        $redirectResponse = $this->post('/email/verify-redirect');
        $redirectResponse->assertRedirect('http://localhost:8025');
    }

     //メール認証完了後に勤怠画面に遷移するかテスト
    public function test_verified_user_redirects_to_attendance_page()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verifyUrl = "/email/verify/{$user->id}/" . sha1($user->email);
        $response = $this->get($verifyUrl);

        $response->assertRedirect('/attendance');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $this->assertTrue(auth()->check());
    }
}

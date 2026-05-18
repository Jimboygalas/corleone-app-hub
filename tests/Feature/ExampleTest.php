<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Corleone App Hub');
        $response->assertSee('Phone OTP');
        $response->assertSee(route('otp.phone'));
        $response->assertSee('Sign In');
        $response->assertSee('Create Account');
        $response->assertSee(route('login'));
        $response->assertSee(route('register'));
    }

    public function test_prototype_routes_render_successfully(): void
    {
        $routes = [
            '/login' => 'Sign in',
            '/register' => 'Create account',
            '/otp/phone' => 'Send OTP to Phone',
            '/otp/email' => 'Send OTP to Email',
            '/otp/verify' => 'Validate OTP',
            '/ai-chatbot' => 'Corleone AI Assistant',
        ];

        foreach ($routes as $uri => $text) {
            $this->get($uri)
                ->assertStatus(200)
                ->assertSee($text);
        }

        $this->get('/mailbox')->assertRedirect('/login');
    }

    public function test_legacy_static_html_paths_redirect_to_laravel_routes(): void
    {
        $this->get('/index.html')->assertRedirect('/');
        $this->get('/otp-phone.html')->assertRedirect('/otp/phone');
        $this->get('/otp-email.html')->assertRedirect('/otp/email');
        $this->get('/validate-otp.html')->assertRedirect('/otp/verify');
        $this->get('/mailbox.html')->assertRedirect('/mailbox');
        $this->get('/ai-chatbot.html')->assertRedirect('/ai-chatbot');
    }

    public function test_register_validation_requires_name_email_and_password(): void
    {
        $this->post('/register', [])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_register_validation_requires_unique_email(): void
    {
        User::factory()->create([
            'email' => 'person@example.com',
        ]);

        $this->post('/register', [
            'name' => 'Student User',
            'email' => 'person@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors(['email']);
    }

    public function test_register_saves_user_and_sends_user_to_otp_selection(): void
    {
        Http::fake();

        $response = $this->post('/register', [
            'name' => 'Student User',
            'email' => 'student@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/otp');
        $response->assertSessionMissing('otp.code');
        $response->assertSessionHas('otp.target', 'student@example.com');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Student User',
            'email' => 'student@example.com',
        ]);
        Http::assertNothingSent();
    }

    public function test_otp_selection_requires_login_and_shows_phone_and_email_choices(): void
    {
        $this->get('/otp')->assertRedirect('/login');

        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/otp')
            ->assertStatus(200)
            ->assertSee('Choose OTP Method')
            ->assertSee('Phone OTP')
            ->assertSee('Email OTP')
            ->assertSee(route('otp.phone'))
            ->assertSee(route('otp.email'));
    }

    public function test_sms_otp_fails_without_exposing_code_when_token_is_missing(): void
    {
        config(['services.repohive_sms.token' => null]);

        $response = $this->post('/otp/phone', [
            'phone' => '+639000000000',
        ]);

        $response->assertSessionHasErrors([
            'phone' => 'OTP sending failed. Please check API configuration.',
        ]);
        $response->assertSessionMissing('otp');
    }

    public function test_sms_otp_calls_repohive_when_token_is_configured(): void
    {
        config([
            'services.repohive_sms.token' => 'test-token',
            'services.repohive_sms.base_url' => 'https://repohive.com/api',
        ]);

        Http::fake([
            'repohive.com/api/messages' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->post('/otp/phone', [
            'phone' => '+639000000000',
        ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('otp.code');
        $response->assertSessionHas('otp.target', '+639000000000');

        Http::assertSent(fn ($request) => $request->url() === 'https://repohive.com/api/messages'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['to'] === '+639000000000');
    }

    public function test_email_otp_sends_laravel_mail_without_repohive_api(): void
    {
        config(['services.repohive_email.token' => null]);
        Http::fake();
        Mail::fake();

        $response = $this->post('/otp/email', [
            'email' => 'person@example.com',
        ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('otp.code');
        $response->assertSessionHas('otp.target', 'person@example.com');

        Mail::assertSent(OtpMail::class, fn (OtpMail $mail) => $mail->hasTo('person@example.com')
            && $mail->otp === session('otp.code')
            && preg_match('/^\d{6}$/', $mail->otp) === 1);
        Http::assertNothingSent();
    }

    public function test_email_otp_fails_without_exposing_code_when_smtp_fails(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->with('person@example.com')
            ->andThrow(new RuntimeException('SMTP unavailable'));

        $response = $this->post('/otp/email', [
            'email' => 'person@example.com',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Email OTP could not be sent. Please check Gmail SMTP configuration.',
        ]);
        $response->assertSessionMissing('otp');
    }

    public function test_email_otp_does_not_require_repohive_email_token(): void
    {
        config(['services.repohive_email.token' => null]);
        Mail::fake();

        $response = $this->post('/otp/email', [
            'email' => 'person@example.com',
        ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('otp.code');
        Mail::assertSent(OtpMail::class);
    }

    public function test_otp_verify_page_does_not_display_stored_code(): void
    {
        $this
            ->withSession(['otp' => [
                'code' => '123456',
                'target' => 'person@example.com',
                'type' => 'email',
            ]])
            ->get('/otp/verify')
            ->assertStatus(200)
            ->assertDontSee('123456');
    }

    public function test_otp_validation_redirects_to_mailbox_for_correct_code(): void
    {
        User::factory()->create([
            'email' => 'demo@corleone.test',
        ]);

        $response = $this
            ->withSession(['otp' => [
                'code' => '123456',
                'target' => 'person@example.com',
                'type' => 'email',
            ]])
            ->post('/otp/verify', [
                'otp_digits' => ['1', '2', '3', '4', '5', '6'],
            ]);

        $response->assertRedirect('/mailbox');
        $response->assertSessionHas('otp_verified', true);
        $response->assertSessionMissing('otp');
        $this->assertAuthenticated();
    }

    public function test_otp_validation_creates_demo_user_when_needed(): void
    {
        $response = $this
            ->withSession(['otp' => [
                'code' => '123456',
                'target' => '+639000000000',
                'type' => 'phone',
            ]])
            ->post('/otp/verify', [
                'otp_digits' => ['1', '2', '3', '4', '5', '6'],
            ]);

        $response->assertRedirect('/mailbox');
        $response->assertSessionHas('otp_verified', true);
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'demo@corleone.test',
        ]);
    }

    public function test_otp_validation_keeps_session_and_shows_error_for_wrong_code(): void
    {
        $response = $this
            ->withSession(['otp' => [
                'code' => '123456',
                'target' => 'person@example.com',
                'type' => 'email',
            ]])
            ->post('/otp/verify', [
                'otp_digits' => ['6', '5', '4', '3', '2', '1'],
            ]);

        $response->assertSessionHasErrors([
            'otp' => 'Wrong OTP. Please try again.',
        ]);
        $response->assertSessionHas('otp.code', '123456');
        $response->assertSessionMissing('otp_verified');
        $this->assertGuest();
    }

    public function test_otp_validation_requires_requested_otp(): void
    {
        $this->post('/otp/verify', [
            'otp_digits' => ['1', '2', '3', '4', '5', '6'],
        ])->assertSessionHasErrors([
            'otp' => 'Please request an OTP first',
        ]);
    }

    public function test_database_login_sends_user_to_otp_selection_without_email_api_call(): void
    {
        User::factory()->create([
            'email' => 'demo@corleone.test',
            'password' => 'password',
        ]);

        Http::fake();

        $response = $this->post('/login', [
            'email' => 'demo@corleone.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/otp');
        $response->assertSessionMissing('otp.code');
        $response->assertSessionHas('otp.target', 'demo@corleone.test');
        $this->assertAuthenticated();
        Http::assertNothingSent();
    }

    public function test_database_seeder_creates_default_demo_login_account(): void
    {
        $this->seed();

        $user = User::where('email', 'demo@corleone.test')->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertFalse(Hash::check('password123', $user->password));
    }

    public function test_database_login_rejects_wrong_credentials(): void
    {
        User::factory()->create([
            'email' => 'demo@corleone.test',
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => 'demo@corleone.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Invalid email or password.',
        ]);
        $this->assertGuest();
    }

    public function test_mailbox_requires_otp_verification_after_login(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/mailbox')
            ->assertRedirect('/otp/verify')
            ->assertSessionHasErrors([
                'otp' => 'Please complete OTP verification first.',
            ]);
    }

    public function test_mailbox_opens_when_user_is_logged_in_and_otp_verified(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->withSession(['otp_verified' => true])
            ->get('/mailbox')
            ->assertStatus(200)
            ->assertSee('Inbox');
    }

    public function test_logout_clears_authentication_and_session(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['otp_verified' => true])
            ->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}

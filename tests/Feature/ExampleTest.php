<?php

namespace Tests\Feature;

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
            '/ai-chatbot' => 'Corleone AI Assistant',
        ];

        foreach ($routes as $uri => $text) {
            $this->get($uri)
                ->assertStatus(200)
                ->assertSee($text);
        }

        $this->get('/mailbox')->assertRedirect('/login');
        $this->get('/otp/phone')->assertRedirect('/login');
        $this->get('/otp/email')->assertRedirect('/login');
        $this->get('/otp/verify')->assertRedirect('/login');
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

    public function test_register_clears_stale_otp_before_method_selection(): void
    {
        $response = $this
            ->withSession(['otp' => [
                'code' => '123456',
                'target' => '+639000000000',
                'type' => 'phone',
            ]])
            ->post('/register', [
                'name' => 'Jimboy T. Galas',
                'email' => 'jimboygalas41@gmail.com',
                'password' => 'password123',
            ]);

        $response->assertRedirect('/otp');
        $response->assertSessionMissing('otp.code');
        $response->assertSessionHas('otp.target', 'jimboygalas41@gmail.com');
        $response->assertSessionHas('otp.user_name', 'Jimboy T. Galas');
        $this->assertAuthenticated();
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

        $this
            ->actingAs($user)
            ->get('/otp/phone')
            ->assertStatus(200)
            ->assertSee('Send OTP to Phone');

        $this
            ->actingAs($user)
            ->get('/otp/email')
            ->assertStatus(200)
            ->assertSee('Send OTP to Email');
    }

    public function test_sms_otp_fails_without_exposing_code_when_token_is_missing(): void
    {
        config(['services.repohive_sms.token' => null]);

        $response = $this
            ->actingAs(User::factory()->create())
            ->post('/otp/phone', [
                'phone' => '+639000000000',
            ]);

        $response->assertSessionHasErrors([
            'phone' => 'OTP sending failed. Please check API configuration.',
        ]);
        $response->assertSessionMissing('otp');
    }

    public function test_sms_otp_fails_clearly_when_otp_sms_fails(): void
    {
        config([
            'services.repohive_sms.token' => 'test-token',
            'services.repohive_sms.base_url' => 'https://repohive.com/api',
        ]);

        Http::fake([
            'repohive.com/api/messages' => Http::response(['error' => 'SMS failed'], 500),
        ]);

        $response = $this
            ->actingAs(User::factory()->create())
            ->post('/otp/phone', [
                'phone' => '+639000000000',
            ]);

        $response->assertSessionHasErrors([
            'phone' => 'Repohive could not send the OTP SMS. Please check the API token, phone number, and Repohive account.',
        ]);
        $response->assertSessionMissing('otp');
        Http::assertSentCount(1);
    }

    public function test_sms_otp_redirects_when_reminder_or_welcome_sms_fails(): void
    {
        config([
            'services.repohive_sms.token' => 'test-token',
            'services.repohive_sms.base_url' => 'https://repohive.com/api',
        ]);

        $attempts = 0;

        Http::fake([
            'repohive.com/api/messages' => function () use (&$attempts) {
                $attempts++;

                return $attempts === 1
                    ? Http::response(['ok' => true], 200)
                    : Http::response(['error' => 'Supplemental SMS failed'], 500);
            },
        ]);

        $response = $this
            ->actingAs(User::factory()->create(['name' => 'Jimboy T. Galas']))
            ->post('/otp/phone', [
                'phone' => '+639000000000',
            ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('otp.code');
        Http::assertSentCount(3);
    }

    public function test_sms_otp_calls_repohive_when_token_is_configured(): void
    {
        config([
            'services.repohive_sms.token' => 'test-token',
            'services.repohive_sms.base_url' => 'https://repohive.com/api',
        ]);

        $user = User::factory()->create([
            'name' => 'Jimboy T. Galas',
        ]);

        Http::fake([
            'repohive.com/api/messages' => Http::response(['ok' => true], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/otp/phone', [
                'phone' => '+639000000000',
            ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('otp.code');
        $response->assertSessionHas('otp.target', '+639000000000');

        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => $request->url() === 'https://repohive.com/api/messages'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['to'] === '+639000000000'
            && $request['message'] === 'Your verification code is '.session('otp.code').'. Do not share this with anyone.'
            && $request['body'] === $request['message']);
        Http::assertSent(fn ($request) => $request->url() === 'https://repohive.com/api/messages'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['to'] === '+639000000000'
            && $request['message'] === 'Reminder for Jimboy T. Galas: Please complete your Corleone App Hub verification today. This is automated. Please do not reply.'
            && $request['body'] === $request['message']);
        Http::assertSent(fn ($request) => $request->url() === 'https://repohive.com/api/messages'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['to'] === '+639000000000'
            && $request['message'] === 'Welcome Jimboy T. Galas to Corleone App Hub. This is an automated SMS notification. Please do not reply.'
            && $request['body'] === $request['message']
            && ! str_contains($request['message'], 'Welcome +639000000000'));
    }

    public function test_sms_otp_uses_session_email_user_name_before_phone_fallback(): void
    {
        config([
            'services.repohive_sms.token' => 'test-token',
            'services.repohive_sms.base_url' => 'https://repohive.com/api',
        ]);

        User::factory()->create([
            'name' => 'Jimboy T. Galas',
            'email' => 'jimboygalas41@gmail.com',
        ]);

        Http::fake([
            'repohive.com/api/messages' => Http::response(['ok' => true], 200),
        ]);

        $response = $this
            ->actingAs(User::factory()->create(['name' => '']))
            ->withSession([
                'otp.target' => 'jimboygalas41@gmail.com',
                'otp.type' => 'login',
            ])
            ->post('/otp/phone', [
                'phone' => '09108954769',
            ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('otp.code');

        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => $request['message'] === 'Reminder for Jimboy T. Galas: Please complete your Corleone App Hub verification today. This is automated. Please do not reply.'
            && $request['to'] === '09108954769');
        Http::assertSent(fn ($request) => $request['message'] === 'Welcome Jimboy T. Galas to Corleone App Hub. This is an automated SMS notification. Please do not reply.'
            && ! str_contains($request['message'], 'Welcome 09108954769'));
    }

    public function test_sms_otp_uses_logged_in_database_name_after_login_redirect(): void
    {
        config([
            'services.repohive_sms.token' => 'test-token',
            'services.repohive_sms.base_url' => 'https://repohive.com/api',
        ]);

        User::factory()->create([
            'name' => 'Jimboy T. Galas',
            'email' => 'jimboygalas41@gmail.com',
            'password' => 'password',
        ]);

        Http::fake([
            'repohive.com/api/messages' => Http::response(['ok' => true], 200),
        ]);

        $this->post('/login', [
            'email' => 'jimboygalas41@gmail.com',
            'password' => 'password',
        ])->assertRedirect('/otp');

        $response = $this->post('/otp/phone', [
            'phone' => '09108954769',
        ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('otp.code');

        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => $request['message'] === 'Reminder for Jimboy T. Galas: Please complete your Corleone App Hub verification today. This is automated. Please do not reply.'
            && ! str_contains($request['message'], '09108954769'));
        Http::assertSent(fn ($request) => $request['message'] === 'Welcome Jimboy T. Galas to Corleone App Hub. This is an automated SMS notification. Please do not reply.'
            && ! str_contains($request['message'], 'Welcome 09108954769')
            && ! str_contains($request['message'], 'Welcome User'));
    }

    public function test_sms_otp_never_uses_phone_number_as_welcome_name_for_registered_user(): void
    {
        config([
            'services.repohive_sms.token' => 'test-token',
            'services.repohive_sms.base_url' => 'https://repohive.com/api',
        ]);

        Http::fake([
            'repohive.com/api/messages' => Http::response(['ok' => true], 200),
        ]);

        $response = $this
            ->actingAs(User::factory()->create(['name' => 'Jimboy T. Galas']))
            ->post('/otp/phone', [
                'phone' => '09108954769',
            ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('otp.code');

        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => $request['message'] === 'Welcome Jimboy T. Galas to Corleone App Hub. This is an automated SMS notification. Please do not reply.'
            && ! str_contains($request['message'], 'Welcome 09108954769'));
    }

    public function test_sms_otp_can_use_submitted_email_user_name_when_auth_name_is_empty(): void
    {
        config([
            'services.repohive_sms.token' => 'test-token',
            'services.repohive_sms.base_url' => 'https://repohive.com/api',
        ]);

        User::factory()->create([
            'name' => 'Jimboy T. Galas',
            'email' => 'jimboygalas41@gmail.com',
        ]);

        Http::fake([
            'repohive.com/api/messages' => Http::response(['ok' => true], 200),
        ]);

        $response = $this
            ->actingAs(User::factory()->create(['name' => '']))
            ->post('/otp/phone', [
                'phone' => '09108954769',
                'email' => 'jimboygalas41@gmail.com',
            ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('otp.code');

        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => $request['message'] === 'Reminder for Jimboy T. Galas: Please complete your Corleone App Hub verification today. This is automated. Please do not reply.');
        Http::assertSent(fn ($request) => $request['message'] === 'Welcome Jimboy T. Galas to Corleone App Hub. This is an automated SMS notification. Please do not reply.'
            && ! str_contains($request['message'], 'Welcome 09108954769'));
    }

    public function test_email_otp_sends_through_repohive_email_api_first(): void
    {
        config([
            'services.repohive_email.token' => 'email-token',
            'services.repohive_email.base_url' => 'https://repohive.com/api',
            'services.repohive_email.path' => '/email/send',
            'services.repohive_email.from' => 'noreply@repohive.com',
            'services.repohive_email.from_name' => 'RepoHive IT Solution',
        ]);

        $user = User::factory()->create([
            'name' => 'Michael Corleone',
        ]);

        Http::fake([
            'repohive.com/api/email/send' => Http::response(['ok' => true], 200),
        ]);
        Mail::fake();

        $response = $this
            ->actingAs($user)
            ->post('/otp/email', [
                'email' => 'person@example.com',
            ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('otp.code');
        $response->assertSessionHas('otp.target', 'person@example.com');

        Http::assertSent(fn ($request) => $request->url() === 'https://repohive.com/api/email/send'
            && $request->hasHeader('Authorization', 'Bearer email-token')
            && $request['from'] === 'noreply@repohive.com'
            && $request['from_name'] === 'RepoHive IT Solution'
            && $request['to'] === 'person@example.com'
            && $request['subject'] === 'Michael Corleone'
            && str_contains($request['html'], 'Your Corleone App Hub verification code is')
            && str_contains($request['html'], 'Do not share this code.')
            && str_contains($request['html'], session('otp.code'))
            && str_contains($request['text'], 'Your Corleone App Hub verification code is '.session('otp.code').'.')
            && str_contains($request['text'], 'Do not share this code.')
            && str_contains($request['text'], session('otp.code'))
            && count($request->data()) === 6
            && array_keys($request->data()) === ['from', 'from_name', 'to', 'subject', 'text', 'html']);
        Mail::assertNothingSent();
    }

    public function test_email_otp_prioritizes_authenticated_database_full_name(): void
    {
        config([
            'services.repohive_email.token' => 'email-token',
            'services.repohive_email.base_url' => 'https://repohive.com/api',
            'services.repohive_email.path' => '/email/send',
        ]);

        $user = User::factory()->create([
            'name' => 'Jimboy T. Galas',
        ]);

        Http::fake([
            'repohive.com/api/email/send' => Http::response(['ok' => true], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/otp/email', [
                'email' => 'jimboygalas41@example.com',
            ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('otp.code');

        Http::assertSent(fn ($request) => $request['subject'] === 'Jimboy T. Galas'
            && ! str_contains($request['html'], 'Jimboy T. Galas')
            && ! str_contains($request['text'], 'Jimboy T. Galas'));
    }

    public function test_email_otp_uses_authenticated_user_name_not_email_username(): void
    {
        config([
            'services.repohive_email.token' => 'email-token',
            'services.repohive_email.base_url' => 'https://repohive.com/api',
            'services.repohive_email.path' => '/email/send',
        ]);

        $user = User::factory()->create([
            'name' => 'Jimboy Galas',
            'email' => 'jimboygalas41@gmail.com',
        ]);

        Http::fake([
            'repohive.com/api/email/send' => Http::response(['ok' => true], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/otp/email', [
                'email' => 'jimboygalas41@gmail.com',
            ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('otp.code');

        Http::assertSent(fn ($request) => $request['subject'] === 'Jimboy Galas'
            && ! str_contains($request['html'], 'Jimboy Galas')
            && ! str_contains($request['text'], 'Jimboy Galas')
            && ! str_contains($request['html'], 'Jimboygalas41')
            && ! str_contains($request['text'], 'Jimboygalas41'));
    }

    public function test_email_otp_uses_database_name_for_matching_email_before_username_fallback(): void
    {
        config([
            'services.repohive_email.token' => 'email-token',
            'services.repohive_email.base_url' => 'https://repohive.com/api',
            'services.repohive_email.path' => '/email/send',
        ]);

        User::factory()->create([
            'name' => 'Jimboy T. Galas',
            'email' => 'jimboygalas41@gmail.com',
        ]);

        Http::fake([
            'repohive.com/api/email/send' => Http::response(['ok' => true], 200),
        ]);

        $response = $this
            ->actingAs(User::factory()->create(['name' => '']))
            ->post('/otp/email', [
                'email' => 'jimboygalas41@gmail.com',
            ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('otp.code');

        Http::assertSent(fn ($request) => $request['subject'] === 'Jimboy T. Galas'
            && ! str_contains($request['html'], 'Jimboy T. Galas')
            && ! str_contains($request['text'], 'Jimboy T. Galas')
            && ! str_contains($request['html'], 'Jimboygalas41')
            && ! str_contains($request['text'], 'Jimboygalas41'));
    }

    public function test_email_otp_uses_email_username_when_user_name_is_empty(): void
    {
        config([
            'services.repohive_email.token' => 'email-token',
            'services.repohive_email.base_url' => 'https://repohive.com/api',
            'services.repohive_email.path' => '/email/send',
        ]);

        $user = User::factory()->create([
            'name' => '',
        ]);

        Http::fake([
            'repohive.com/api/email/send' => Http::response(['ok' => true], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/otp/email', [
                'email' => 'jimboygalas41@example.com',
            ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('otp.code');

        Http::assertSent(fn ($request) => $request['subject'] === 'Jimboygalas41'
            && ! str_contains($request['html'], 'Jimboygalas41')
            && ! str_contains($request['text'], 'Jimboygalas41'));
    }

    public function test_email_otp_uses_gmail_smtp_fallback_when_repohive_email_token_is_missing(): void
    {
        config(['services.repohive_email.token' => null]);
        Http::fake();
        Mail::fake();

        $response = $this
            ->actingAs(User::factory()->create(['name' => '']))
            ->post('/otp/email', [
                'email' => 'person@example.com',
        ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('success', 'Email OTP sent. Please enter the 6-digit code.');
        $response->assertSessionMissing('warning');
        $response->assertSessionHas('otp.code');
        $response->assertSessionHas('otp.target', 'person@example.com');
        Http::assertNothingSent();
        Mail::assertSent(OtpMail::class, fn (OtpMail $mail) => $mail->hasTo('person@example.com')
            && $mail->otp === session('otp.code')
            && preg_match('/^\d{6}$/', $mail->otp) === 1);
    }

    public function test_email_otp_uses_gmail_smtp_fallback_when_repohive_returns_502(): void
    {
        config([
            'services.repohive_email.token' => 'email-token',
            'services.repohive_email.base_url' => 'https://repohive.com/api',
            'services.repohive_email.path' => '/email/send',
        ]);

        Http::fake([
            'repohive.com/api/email/send' => Http::response(['error' => 'Bad Gateway'], 502),
        ]);
        Mail::fake();

        $response = $this
            ->actingAs(User::factory()->create(['name' => '']))
            ->post('/otp/email', [
                'email' => 'person@example.com',
        ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('success', 'Email OTP sent. Please enter the 6-digit code.');
        $response->assertSessionMissing('warning');
        $response->assertSessionHas('otp.code');
        $response->assertSessionHas('otp.target', 'person@example.com');

        Mail::assertSent(OtpMail::class, fn (OtpMail $mail) => $mail->hasTo('person@example.com')
            && $mail->otp === session('otp.code')
            && $mail->otpSubject === 'Person');
    }

    public function test_email_otp_uses_gmail_smtp_fallback_when_repohive_returns_api_error(): void
    {
        config([
            'services.repohive_email.token' => 'email-token',
            'services.repohive_email.base_url' => 'https://repohive.com/api',
            'services.repohive_email.path' => '/email/send',
        ]);

        Http::fake([
            'repohive.com/api/email/send' => Http::response(['message' => 'Invalid recipient'], 422),
        ]);
        Mail::fake();

        $response = $this
            ->actingAs(User::factory()->create(['name' => '']))
            ->post('/otp/email', [
                'email' => 'person@example.com',
        ]);

        $response->assertRedirect('/otp/verify');
        $response->assertSessionHas('success', 'Email OTP sent. Please enter the 6-digit code.');
        $response->assertSessionMissing('warning');
        $response->assertSessionHas('otp.code');
        $response->assertSessionHas('otp.target', 'person@example.com');

        Mail::assertSent(OtpMail::class, fn (OtpMail $mail) => $mail->hasTo('person@example.com')
            && $mail->otp === session('otp.code'));
    }

    public function test_email_otp_shows_error_when_repohive_and_gmail_smtp_both_fail(): void
    {
        config([
            'services.repohive_email.token' => 'email-token',
            'services.repohive_email.base_url' => 'https://repohive.com/api',
            'services.repohive_email.path' => '/email/send',
        ]);

        Http::fake([
            'repohive.com/api/email/send' => Http::response(['error' => 'Bad Gateway'], 502),
        ]);
        Mail::shouldReceive('to')
            ->once()
            ->with('person@example.com')
            ->andThrow(new RuntimeException('SMTP unavailable'));

        $response = $this
            ->actingAs(User::factory()->create(['name' => '']))
            ->post('/otp/email', [
                'email' => 'person@example.com',
            ]);

        $response->assertSessionHasErrors([
            'email' => 'Email OTP could not be sent. Please try again or use Phone OTP.',
        ]);
        $response->assertSessionMissing('otp');
    }

    public function test_otp_verify_page_does_not_display_stored_code(): void
    {
        $this
            ->actingAs(User::factory()->create())
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
            ->actingAs(User::factory()->create())
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

    public function test_otp_validation_uses_authenticated_registered_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Jimboy T. Galas',
            'email' => 'jimboygalas41@gmail.com',
        ]);

        $response = $this
            ->actingAs($user)
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
            'email' => 'jimboygalas41@gmail.com',
            'name' => 'Jimboy T. Galas',
        ]);
    }

    public function test_otp_validation_keeps_session_and_shows_error_for_wrong_code(): void
    {
        $response = $this
            ->actingAs(User::factory()->create())
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
        $this->assertAuthenticated();
    }

    public function test_otp_validation_requires_requested_otp(): void
    {
        $this
            ->actingAs(User::factory()->create())
            ->post('/otp/verify', [
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
        $response->assertSessionHas('otp.user_email', 'demo@corleone.test');
        $this->assertAuthenticated();
        Http::assertNothingSent();
    }

    public function test_database_seeder_creates_default_demo_login_account(): void
    {
        $this->seed();

        $user = User::where('email', 'demo@corleone.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('System User', $user->name);
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

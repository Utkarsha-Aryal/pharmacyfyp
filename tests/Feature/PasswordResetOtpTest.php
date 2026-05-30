<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_has_forgot_password_link(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee(route('password.request'), false);
    }

    public function test_user_can_request_password_reset_otp_by_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $response = $this->post(route('password.otp.send'), [
            'email' => 'admin@example.com',
        ]);

        $response->assertRedirect(route('password.reset.form', ['email' => 'admin@example.com']));
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'admin@example.com',
        ]);

        $otp = null;
        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use ($user, &$otp) {
            $otp = $mail->otp;

            return $mail->hasTo($user->email);
        });

        $resetRow = DB::table('password_reset_tokens')->where('email', $user->email)->first();

        $this->assertNotNull($otp);
        $this->assertTrue(Hash::check($otp, $resetRow->token));
    }

    public function test_user_can_reset_password_with_valid_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'old-password',
        ]);

        $otp = $this->sendOtpAndReturnCode($user);

        $response = $this->post(route('password.reset.update'), [
            'email' => $user->email,
            'otp' => $otp,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_expired_otp_cannot_reset_password(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'old-password',
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('123456'),
            'created_at' => now()->subMinutes(16),
        ]);

        $response = $this->from(route('password.reset.form', ['email' => $user->email]))
            ->post(route('password.reset.update'), [
                'email' => $user->email,
                'otp' => '123456',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

        $response->assertRedirect(route('password.reset.form', ['email' => $user->email]));
        $response->assertSessionHasErrors(['otp']);
        $this->assertFalse(Hash::check('new-password-123', $user->fresh()->password));
    }

    private function sendOtpAndReturnCode(User $user): string
    {
        $otp = null;

        $this->post(route('password.otp.send'), [
            'email' => $user->email,
        ])->assertRedirect(route('password.reset.form', ['email' => $user->email]));

        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$otp) {
            $otp = $mail->otp;

            return true;
        });

        $this->assertNotNull($otp);

        return $otp;
    }
}

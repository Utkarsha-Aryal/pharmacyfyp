<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    // Show the admin login page.
    public function login()
    {
        return view('auth.login');
    }

    // Try to login the admin-side user and block inactive or unauthorized accounts.
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email or password not match.',
            ]);
        }

        $request->session()->regenerate();

        if (!Auth::user()->hasAnyRole(['admin', 'staff', 'procurement'])) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Only admin, staff or procurement users can login here.',
            ]);
        }

        if (!Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'This account is inactive right now.',
            ]);
        }

        return redirect()->intended(route('admin.dashboard'))->with('success', 'Login done.');
    }

    public function forgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordResetOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('is_active', true)
            ->first();

        if ($user) {
            $otp = (string) random_int(100000, 999999);

            try {
                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $user->email],
                    [
                        'token' => Hash::make($otp),
                        'created_at' => now(),
                    ]
                );

                Mail::to($user->email)->send(new PasswordResetOtpMail($user, $otp));
            } catch (Throwable $exception) {
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();

                return back()
                    ->withInput($request->only('email'))
                    ->with('error', 'Could not send OTP right now. Please check SMTP settings and try again.');
            }
        }

        return redirect()
            ->route('password.reset.form', ['email' => $email])
            ->with('success', 'If this email exists, a password reset OTP has been sent.');
    }

    public function resetPasswordForm(Request $request)
    {
        return view('auth.reset-password', [
            'email' => $request->query('email'),
        ]);
    }

    public function resetPasswordWithOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $email = strtolower(trim($validated['email']));
        $resetRow = DB::table('password_reset_tokens')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        $otpExpired = $resetRow && $resetRow->created_at
            ? Carbon::parse($resetRow->created_at)->lt(now()->subMinutes(15))
            : true;

        if (!$resetRow || $otpExpired || !Hash::check($validated['otp'], $resetRow->token)) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid or expired OTP.',
            ]);
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('is_active', true)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => 'This account cannot be reset right now.',
            ]);
        }

        $user->forceFill([
            'password' => $validated['password'],
        ])->save();

        DB::table('password_reset_tokens')->where('email', $resetRow->email)->delete();

        return redirect()->route('login')->with('success', 'Password reset done. Please login with your new password.');
    }

    // Logout user and clear the current session.
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logout done.');
    }
}

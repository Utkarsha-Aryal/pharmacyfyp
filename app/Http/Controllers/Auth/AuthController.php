<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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

    // Logout user and clear the current session.
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logout done.');
    }
}

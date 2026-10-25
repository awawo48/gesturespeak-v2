<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Option 1 on the main page: child login with Student Code + 4-digit PIN.
 */
class StudentAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('student')->check()) {
            return redirect()->route('learn.hub');
        }

        return view('auth.student-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_code' => ['required', 'string', 'max:12'],
            'pin' => ['required', 'digits:4'],
        ]);

        $code = Str::upper(trim($data['student_code']));
        $throttleKey = 'student-login:'.$code.'|'.$request->ip();

        // 4-digit PINs are short, so limit guesses
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->withErrors(['pin' => 'Too many tries. Please wait a minute and ask a grown-up for help.'])->onlyInput('student_code');
        }

        if (! Auth::guard('student')->attempt(['student_code' => $code, 'password' => $data['pin']])) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['pin' => 'Oops! The code or PIN is not right. Try again.'])->onlyInput('student_code');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->route('learn.hub');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('student')->logout();

        // If a parent/teacher started this session, return them to their dashboard
        if (Auth::guard('web')->check()) {
            return redirect()->route('guardian.dashboard');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}

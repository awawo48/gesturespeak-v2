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
        $request->merge([
            'student_code' => Str::upper(trim((string) $request->input('student_code'))),
            'pin' => trim((string) $request->input('pin')),
        ]);

        $data = $request->validate([
            'student_code' => ['required', 'string', 'max:12'],
            'pin' => ['required', 'digits:4'],
        ], [
            'student_code.required' => 'Sila masukkan Kod Murid anda.',
            'pin.required' => 'Sila masukkan nombor PIN 4-digit.',
            'pin.digits' => 'Nombor PIN mestilah tepat 4 angka (contoh: 1234).',
        ]);

        $code = $data['student_code'];
        $throttleKey = 'student-login:'.$code.'|'.$request->ip();

        // 4-digit PINs are short, so limit guesses
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()
                ->withErrors(['pin' => 'Terlalu banyak percubaan. Sila tunggu 1 minit dan minta bantuan ibu bapa atau guru.'])
                ->onlyInput('student_code');
        }

        if (! Auth::guard('student')->attempt(['student_code' => $code, 'password' => $data['pin']])) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withErrors(['pin' => 'Kod Murid atau PIN 4-digit tidak tepat. Sila cuba lagi.'])
                ->onlyInput('student_code');
        }

        RateLimiter::clear($throttleKey);
        Auth::guard('web')->logout();
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        return redirect()->route('learn.hub');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('student')->logout();

        // If a parent/teacher started this session, return them to their dashboard
        if ($webUser = Auth::guard('web')->user()) {
            return redirect()->route($webUser->homeRoute());
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}

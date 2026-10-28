<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Option 2 on the main page: Parent / Teacher (and hidden Admin) login with email + password.
 */
class AdultAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if ($user = Auth::guard('web')->user()) {
            Auth::guard('student')->logout();

            return redirect()->route($user->homeRoute());
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Emel atau kata laluan tidak tepat. Sila semak semula.'])
                ->onlyInput('email');
        }

        // Ensure any leftover student guard session does not hijack the navbar or routes
        Auth::guard('student')->logout();
        $request->session()->regenerate();

        $user = Auth::guard('web')->user();
        $intended = $request->session()->pull('url.intended');
        if (is_string($intended) && $intended !== '') {
            $path = '/'.ltrim((string) parse_url($intended, PHP_URL_PATH), '/');
            $allowedPrefix = $user->isAdmin() ? '/admin' : '/guardian';
            if (str_starts_with($path, $allowedPrefix)) {
                return redirect()->to($intended);
            }
        }

        // Admin is detected by role - no separate admin button on the main page
        return redirect()->route($user->homeRoute());
    }

    public function showRegister(): View|RedirectResponse
    {
        if ($user = Auth::guard('web')->user()) {
            Auth::guard('student')->logout();

            return redirect()->route($user->homeRoute());
        }

        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            // Admin accounts cannot be created through public sign-up
            'role' => ['required', Rule::in([User::ROLE_PARENT, User::ROLE_TEACHER])],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create($data);

        Auth::guard('student')->logout();
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        return redirect()->route('guardian.dashboard')
            ->with('success', 'Selamat datang ke GestureSpeak! Mulakan dengan mendaftarkan profil murid pertama anda.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        Auth::guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}

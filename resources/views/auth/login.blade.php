@extends('layouts.app', ['title' => 'Log Masuk Dewasa - GestureSpeak'])

@section('content')
<div style="max-width: 480px; margin: 1.5rem auto;">
    <div class="card" style="border: 2px solid var(--border-light); padding: 2.5rem 2rem;">

        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="font-size: 2.8rem; margin-bottom: 0.5rem;">👨‍👩‍👧‍👦 👩‍🏫</div>
            <h1 style="font-size: 1.8rem; margin-bottom: 0.3rem;">Portal Ibu Bapa & Guru</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Log masuk untuk pantau perkembangan anak anda
            </p>
        </div>

        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fa-solid fa-envelope" style="color: var(--primary);"></i> Alamat Emel:
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input"
                    value="{{ old('email') }}"
                    placeholder="nama@contoh.com"
                    required
                    autofocus
                >
                @error('email')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fa-solid fa-lock" style="color: var(--primary);"></i> Kata Laluan:
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    placeholder="••••••••"
                    required
                >
                @error('password')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; font-size: 0.9rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span>Ingat saya</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                <i class="fa-solid fa-right-to-bracket"></i> Log Masuk
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.95rem;">
            Belum mempunyai akaun?
            <a href="{{ route('register') }}" style="color: var(--primary); font-weight: 800;">Daftar di sini</a>
        </div>

        {{-- QUICK TEST CREDENTIALS BOX --}}
        <div style="margin-top: 2rem; padding: 1rem; background: #f8fafc; border-radius: var(--radius-md); border: 1px dashed rgba(108, 92, 231, 0.25); font-size: 0.82rem;">
            <strong style="color: var(--primary);"><i class="fa-solid fa-flask"></i> Akaun Ujian / Demo (Kata Laluan: <code>password</code>):</strong>
            <ul style="margin: 0.5rem 0 0 1.2rem; line-height: 1.5;">
                <li><strong>Admin</strong>: <code>admin@gesturespeak.com</code> (Automatik ke Panel Pentadbir)</li>
                <li><strong>Ibu Bapa</strong>: <code>parent@gesturespeak.com</code></li>
                <li><strong>Guru</strong>: <code>teacher@gesturespeak.com</code></li>
            </ul>
        </div>

        <div style="margin-top: 1.5rem; text-align: center;">
            <a href="{{ route('home') }}" style="color: var(--text-muted); font-weight: 700; font-size: 0.9rem;">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Laman Utama
            </a>
        </div>
    </div>
</div>
@endsection

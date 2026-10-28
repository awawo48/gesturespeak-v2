@extends('layouts.app', ['title' => 'Log Masuk Murid - GestureSpeak'])

@section('content')
<div style="max-width: 480px; margin: 1.5rem auto;">
    <div class="card" style="border: 3px solid #ff7675; text-align: center; padding: 2.5rem 2rem; position: relative;">

        <div style="font-size: 3.5rem; margin-bottom: 0.5rem; animation: bounce 2s infinite;">
            🦁 ⭐ 🚀
        </div>

        <h1 style="font-size: 2rem; margin-bottom: 0.3rem;">Hai Kawan! 👋</h1>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">
            Masukkan <strong>Kod Murid</strong> dan <strong>PIN 4-digit</strong> anda:
        </p>

        <form action="{{ route('student.login') }}" method="POST">
            @csrf

            <div class="form-group" style="text-align: left;">
                <label for="student_code" class="form-label">
                    <i class="fa-solid fa-id-card-clip" style="color: #ff7675;"></i> Kod Murid Anda:
                </label>
                <input
                    type="text"
                    id="student_code"
                    name="student_code"
                    class="form-input"
                    value="{{ old('student_code') }}"
                    placeholder="Contoh: ADAM-1234"
                    style="text-transform: uppercase; font-size: 1.2rem; font-weight: 800; letter-spacing: 2px; text-align: center;"
                    required
                    autofocus
                >
                @error('student_code')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="text-align: left;">
                <label for="pin" class="form-label">
                    <i class="fa-solid fa-key" style="color: var(--accent-gold);"></i> Nombor PIN (4 Digit):
                </label>
                <input
                    type="password"
                    id="pin"
                    name="pin"
                    maxlength="4"
                    inputmode="numeric"
                    autocomplete="off"
                    class="form-input"
                    placeholder="••••"
                    style="font-size: 1.8rem; font-weight: 900; letter-spacing: 8px; text-align: center;"
                    required
                >
                @error('pin')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-accent btn-lg" style="width: 100%; margin-top: 1rem;">
                <i class="fa-solid fa-door-open"></i> Masuk Sekarang!
            </button>
        </form>

        {{-- DEMO HELPER PILL --}}
        <div style="margin-top: 2rem; padding: 0.8rem; background: #fff5f5; border-radius: var(--radius-md); border: 1px dashed #ff7675; font-size: 0.85rem; color: #c0392b;">
            💡 <strong>Akaun Ujian / Demo:</strong><br>
            Kod Murid: <strong>DEMO-1234</strong> &bull; PIN: <strong>1234</strong>
        </div>

        <div style="margin-top: 1.5rem;">
            <a href="{{ route('home') }}" style="color: var(--text-muted); font-weight: 700; font-size: 0.9rem;">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Laman Utama
            </a>
        </div>
    </div>
</div>
@endsection

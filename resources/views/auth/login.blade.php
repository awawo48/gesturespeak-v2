@extends('layouts.app', ['title' => 'Log Masuk Dewasa - Sign Cilik'])

@section('content')
<div class="auth-split">
    {{-- LEFT COLUMN: ADULT LOGIN CARD --}}
    <div class="auth-card">
        <div class="auth-kicker">
            <x-sc-icon name="keluarga-guru" :size="18" />
            <span>Portal dewasa</span>
        </div>

        <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Log masuk Ibu Bapa &amp; Guru</h1>
        <p style="color: var(--text-muted); margin-bottom: 1.25rem; font-size: 0.96rem;">
            Pantau kemajuan anak, urus kelas dan bina latihan mingguan
        </p>

        {{-- ROLE MODE SWITCHER --}}
        <nav class="role-switcher" aria-label="Pilih portal log masuk">
            <a href="{{ route('student.login') }}" class="role-tab">
                <x-sc-icon name="kod-pin" :size="18" />
                <span>Kod + PIN Pelajar</span>
            </a>
            <span class="role-tab is-active" aria-current="page">
                <x-sc-icon name="keluarga-guru" :size="18" />
                <span>Ibu Bapa &amp; Guru</span>
            </span>
        </nav>

        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Alamat emel</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input"
                    value="{{ old('email') }}"
                    placeholder="nama@contoh.com"
                    inputmode="email"
                    autocomplete="username"
                    autocorrect="off"
                    autocapitalize="none"
                    spellcheck="false"
                    enterkeyhint="next"
                    required
                    autofocus
                >
                @error('email')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Kata laluan</label>
                <div class="password-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        enterkeyhint="done"
                        required
                    >
                    <button
                        type="button"
                        id="toggle-password"
                        class="password-toggle-btn"
                        aria-pressed="false"
                        aria-controls="password"
                    >
                        Tunjuk
                    </button>
                </div>
                @error('password')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.35rem; font-size: 0.92rem;">
                <label style="display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 700;">
                    <input type="checkbox" name="remember" style="accent-color: var(--primary); width: 18px; height: 18px;" {{ old('remember') ? 'checked' : '' }}>
                    <span>Ingat saya</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-kid btn-block">
                Log masuk
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.25rem; font-size: 0.94rem;">
            Belum mempunyai akaun?
            <a href="{{ route('register') }}" style="color: var(--secondary-dark); font-weight: 800;">Daftar di sini</a>
        </div>

        {{-- QUICK TEST CREDENTIALS BOX --}}
        <div class="demo-box" style="margin-top: 1.25rem; text-align: left;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.5rem;">
                <span style="font-size: 0.84rem; font-weight: 700; color: var(--primary);">Akaun ujian</span>
                <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Kata laluan: <code class="pixel-code">password</code></span>
            </div>
            <ul style="list-style: none; display: grid; gap: 0.25rem; font-size: 0.86rem; color: #334155;">
                <li><strong>Ibu bapa:</strong> <code>parent@gesturespeak.com</code></li>
                <li><strong>Guru:</strong> <code>teacher@gesturespeak.com</code></li>
                <li><strong>Admin:</strong> <code>admin@gesturespeak.com</code></li>
            </ul>
        </div>

        <div style="margin-top: 1.15rem; text-align: center;">
            <a href="{{ route('home') }}" style="font-weight: 700; font-size: 0.9rem; color: var(--text-muted);">
                &larr; Kembali ke laman utama
            </a>
        </div>
    </div>

    {{-- RIGHT COLUMN: HOME SCHOOL PORTAL SHOWCASE --}}
    <aside class="auth-showcase" aria-label="Maklumat portal ibu bapa dan guru">
        <div>
            <h2 style="font-size: 1.45rem; margin: 0 0 0.25rem;">Kerjasama rumah dan sekolah</h2>
            <p style="font-size: 0.95rem; color: var(--text-muted); margin: 0;">
                Pantau perkembangan pelajar dari rumah dan urus modul kelas dalam satu portal
            </p>
        </div>

        <div class="step-mini-card" style="background: #fff;">
            <x-sc-icon name="ibu-bapa" :size="44" />
            <div>
                <h3 style="font-size: 1.08rem; margin: 0 0 0.35rem;">Untuk Ibu Bapa</h3>
                <ul class="role-list" style="margin-bottom: 0;">
                    <li>Cipta profil anak dan kongsi Kod Pelajar</li>
                    <li>Pantau markah bintang serta isyarat dikuasai</li>
                    <li>Semak ulasan dan tugasan daripada cikgu</li>
                </ul>
            </div>
        </div>

        <div class="step-mini-card" style="background: #fff;">
            <x-sc-icon name="guru" :size="44" />
            <div>
                <h3 style="font-size: 1.08rem; margin: 0 0 0.35rem;">Untuk Cikgu</h3>
                <ul class="role-list" style="margin-bottom: 0;">
                    <li>Pautkan Kod Pelajar untuk pantau prestasi kelas</li>
                    <li>Tulis ulasan kemajuan dan modul mingguan</li>
                    <li>Bina set latihan dan kuiz khas mengikut topik</li>
                </ul>
            </div>
        </div>

        <div class="notice-strip">
            <x-sc-icon name="mascot" :size="42" />
            <div>
                <div style="font-family: var(--font-display); font-weight: 700; font-size: 1rem;">
                    Pelajar tidak perlukan alamat emel
                </div>
                <div style="font-size: 0.88rem; color: #cbd5e1;">
                    Murid terus log masuk menggunakan Kod Pelajar dan PIN 4 digit
                </div>
            </div>
        </div>
    </aside>
</div>

@push('scripts')
<script>
    (() => {
        const pwd = document.getElementById('password');
        const btn = document.getElementById('toggle-password');
        btn?.addEventListener('click', () => {
            const visible = pwd.type === 'text';
            pwd.type = visible ? 'password' : 'text';
            btn.setAttribute('aria-pressed', String(!visible));
            btn.textContent = visible ? 'Tunjuk' : 'Sorok';
        });
    })();
</script>
@endpush
@endsection

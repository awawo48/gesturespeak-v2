@extends('layouts.app', ['title' => 'Log Masuk Murid - Sign Cilik'])

@section('content')
<div class="auth-split">
    {{-- LEFT COLUMN: STUDENT LOGIN CARD --}}
    <div class="auth-card">
        <div class="auth-kicker">
            <x-sc-icon name="pelajar" :size="18" />
            <span>Akses pelajar</span>
        </div>

        <h1 style="font-size: 1.85rem; margin-bottom: 0.35rem;">Log masuk pelajar</h1>
        <p style="color: var(--text-muted); margin-bottom: 1.25rem; font-size: 0.96rem;">
            Masukkan Kod Pelajar dan PIN 4 digit untuk mula belajar
        </p>

        {{-- ROLE MODE SWITCHER --}}
        <nav class="role-switcher" aria-label="Pilih portal log masuk">
            <span class="role-tab is-active" aria-current="page">
                <x-sc-icon name="kod-pin" :size="18" />
                <span>Kod + PIN Pelajar</span>
            </span>
            <a href="{{ route('login') }}" class="role-tab">
                <x-sc-icon name="keluarga-guru" :size="18" />
                <span>Ibu Bapa &amp; Guru</span>
            </a>
        </nav>

        <form action="{{ route('student.login') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="student_code" class="form-label">Kod Pelajar</label>
                <div class="input-icon-wrap">
                    <span class="input-leading-icon">
                        <x-sc-icon name="kod-pin" :size="22" />
                    </span>
                    <input
                        type="text"
                        id="student_code"
                        name="student_code"
                        class="form-input form-input--with-icon"
                        value="{{ old('student_code') }}"
                        placeholder="Contoh: ADAM-1234"
                        autocomplete="username"
                        autocorrect="off"
                        autocapitalize="characters"
                        spellcheck="false"
                        enterkeyhint="next"
                        required
                        autofocus
                    >
                </div>
                @error('student_code')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label for="pin" class="form-label" style="margin-bottom: 0;">Nombor PIN</label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span id="pin-help" style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">4 digit angka</span>
                        <button type="button" id="toggle-pin" class="pin-toggle-btn" aria-pressed="false" aria-controls="pin">
                            Tunjuk
                        </button>
                    </div>
                </div>

                {{-- Single accessible input backed visually by 4 FigCraft PIN boxes --}}
                <div class="pin-field-wrap" id="pin-field-wrap">
                    <input
                        type="password"
                        id="pin"
                        name="pin"
                        maxlength="4"
                        minlength="4"
                        pattern="\d{4}"
                        inputmode="numeric"
                        autocomplete="current-password"
                        enterkeyhint="done"
                        aria-describedby="pin-help"
                        class="pin-native-input"
                        required
                    >
                    <div class="pin-boxes" aria-hidden="true">
                        <div class="pin-box is-active" data-pin-slot="0">|</div>
                        <div class="pin-box" data-pin-slot="1">-</div>
                        <div class="pin-box" data-pin-slot="2">-</div>
                        <div class="pin-box" data-pin-slot="3">-</div>
                    </div>
                </div>
                @error('pin')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-kid btn-block" style="margin-top: 0.5rem;">
                <x-sc-icon name="mascot" :size="24" />
                <span>Masuk sekarang</span>
            </button>
        </form>

        <div class="demo-box" style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; text-align: left;">
            <div>
                <div style="font-size: 0.82rem; color: var(--text-muted); font-weight: 700;">Akaun demo pelajar</div>
                <div style="margin-top: 0.15rem;">
                    <code class="pixel-code">DEMO-1234</code> &bull; PIN <code class="pixel-code">1234</code>
                </div>
            </div>
            <button type="button" id="btn-fill-demo" class="btn btn-soft btn-sm">
                Isi automatik
            </button>
        </div>

        <div style="margin-top: 1.25rem; text-align: center;">
            <a href="{{ route('home') }}" style="font-weight: 700; font-size: 0.92rem; color: var(--text-muted);">
                &larr; Kembali ke laman utama
            </a>
        </div>
    </div>

    {{-- RIGHT COLUMN: STUDENT WORLD PREVIEW PANEL --}}
    <aside class="auth-showcase" aria-label="Pratonton dunia belajar pelajar">
        <div class="student-pass">
            <div style="display: flex; align-items: center; gap: 0.95rem;">
                <x-sc-icon name="mascot" :size="56" />
                <div>
                    <div style="font-size: 0.78rem; color: #cbd5e1; font-weight: 600;">Contoh kad pelajar Sign Cilik</div>
                    <div style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700;">Adik Adam</div>
                    <div class="pixel-hud" style="color: #cbd5e1; margin-top: 0.25rem; font-size: 0.95rem;">Kod pelajar: ADAM-1234</div>
                </div>
            </div>
            <div class="star-pill" style="background: #fff; border-color: #cbd5e1;">
                <x-sc-icon name="bintang-kuiz" :size="20" />
                <span>120 bintang</span>
            </div>
        </div>

        <div class="step-mini-card">
            <x-sc-icon name="buku" :size="38" />
            <div>
                <h2 style="font-size: 1.05rem; margin: 0 0 0.2rem;">Pilih dunia modul bergambar</h2>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin: 0;">
                    Abjad A–Z, nombor ceria dan ucapan harian bersama guru
                </p>
            </div>
        </div>

        <div class="step-mini-card">
            <x-sc-icon name="kamera-ai" :size="38" />
            <div>
                <h2 style="font-size: 1.05rem; margin: 0 0 0.2rem;">Tunjuk isyarat di hadapan kamera AI</h2>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin: 0;">
                    Semakan ketepatan gerakan tangan secara masa nyata terus pada skrin
                </p>
            </div>
        </div>

        <div class="step-mini-card">
            <x-sc-icon name="suara-bm" :size="38" />
            <div>
                <h2 style="font-size: 1.05rem; margin: 0 0 0.2rem;">Dengar sebutan Bahasa Melayu yang jelas</h2>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin: 0;">
                    Audio sebutan membantu ingatan perkataan serta kuiz bintang mingguan
                </p>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.65rem; font-size: 0.88rem; color: var(--text-muted); font-weight: 700; padding-top: 0.25rem;">
            <x-sc-icon name="guru" :size="26" />
            <span>Kod Pelajar dan PIN dijana oleh ibu bapa atau cikgu di portal pengurusan</span>
        </div>
    </aside>
</div>

@push('scripts')
<script>
    (() => {
        const codeInput = document.getElementById('student_code');
        const pinInput = document.getElementById('pin');
        const toggleBtn = document.getElementById('toggle-pin');
        const fillBtn = document.getElementById('btn-fill-demo');
        const slots = Array.from(document.querySelectorAll('[data-pin-slot]'));
        let revealPin = false;

        function renderPinSlots() {
            const val = (pinInput?.value || '').replace(/\D/g, '').slice(0, 4);
            if (pinInput && pinInput.value !== val) pinInput.value = val;

            slots.forEach((slot, idx) => {
                const char = val[idx];
                slot.classList.toggle('is-active', idx === Math.min(val.length, 3));
                if (char) {
                    slot.textContent = revealPin ? char : '*';
                } else {
                    slot.textContent = idx === val.length ? '|' : '-';
                }
            });
        }

        pinInput?.addEventListener('input', renderPinSlots);
        pinInput?.addEventListener('focus', renderPinSlots);

        toggleBtn?.addEventListener('click', () => {
            revealPin = !revealPin;
            pinInput.type = revealPin ? 'text' : 'password';
            toggleBtn.setAttribute('aria-pressed', String(revealPin));
            toggleBtn.textContent = revealPin ? 'Sorok' : 'Tunjuk';
            renderPinSlots();
        });

        fillBtn?.addEventListener('click', () => {
            if (codeInput) codeInput.value = 'DEMO-1234';
            if (pinInput) {
                pinInput.value = '1234';
                renderPinSlots();
                pinInput.focus();
            }
        });

        renderPinSlots();
    })();
</script>
@endpush
@endsection

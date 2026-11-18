@extends('layouts.app')

@section('content')
{{-- 1. HERO SECTION --}}
<section class="hero-split">
    <div>
        <div class="hero-kicker">
            <span>Bahasa Isyarat Malaysia &middot; Tahap Kanak-Kanak</span>
        </div>

        <h1 class="hero-headline">
            Belajar Bahasa Isyarat Malaysia sambil <span class="hero-highlight">bermain</span>
        </h1>
        <p class="hero-lead">
            Tunjukkan isyarat tangan di hadapan kamera, dengar sebutan Bahasa Melayu yang jelas, dan kumpul bintang setiap hari bersama maskot Sign Cilik.
        </p>

        <div class="hero-cta-row">
            @if(Auth::guard('student')->check())
                <a href="{{ route('learn.hub') }}" class="btn btn-primary btn-kid">
                    <x-sc-icon name="mascot" :size="24" />
                    <span>Teruskan Belajar ({{ Auth::guard('student')->user()->name }})</span>
                </a>
                <a href="{{ route('quiz.index') }}" class="btn btn-soft btn-kid">
                    <x-sc-icon name="trofi" :size="22" />
                    <span>Buka Kuiz Bintang</span>
                </a>
            @elseif($adultUser = Auth::guard('web')->user())
                <a href="{{ route($adultUser->homeRoute()) }}" class="btn btn-primary btn-kid">
                    <x-sc-icon name="mascot" :size="24" />
                    <span>Buka Dashboard ({{ $adultUser->name }})</span>
                </a>
                <a href="{{ route('student.login') }}" class="btn btn-soft btn-kid">
                    <x-sc-icon name="kod-pin" :size="22" />
                    <span>Masuk Kod + PIN</span>
                </a>
            @else
                <a href="{{ route('student.login') }}" class="btn btn-primary btn-kid">
                    <x-sc-icon name="mascot" :size="24" />
                    <span>Mula belajar sekarang</span>
                </a>
                <a href="#modul" class="btn btn-soft btn-kid">
                    <span>Lihat modul BIM</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Toy Camera Console Preview --}}
    <div class="hero-console">
        <div class="console-hud">
            <div class="console-pill">
                <x-sc-icon name="ucapan" :size="20" />
                <span>Tahap 1: Ucapan Harian</span>
            </div>
            <div class="console-pill">
                <x-sc-icon name="bintang-kuiz" :size="20" />
                <span>120 bintang</span>
            </div>
        </div>

        <div class="console-screen">
            <div class="console-screen-top">
                <div class="console-match-pill">
                    <x-sc-icon name="trofi" :size="18" />
                    <span>Padanan tepat | 96%</span>
                </div>
                <div class="console-bubble">Cuba buat isyarat ini</div>
                <x-sc-icon name="mascot" :size="52" fetchpriority="high" />
            </div>

            <div class="console-hand-wrap" aria-hidden="true">
                <span class="pixel-corner pixel-corner--tl"></span>
                <span class="pixel-corner pixel-corner--tr"></span>
                <span class="pixel-corner pixel-corner--bl"></span>
                <span class="pixel-corner pixel-corner--br"></span>

                <svg viewBox="0 0 176 140" width="176" height="140" fill="none" aria-hidden="true">
                    <g stroke="#58caf3" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" opacity="0.85">
                        <polyline points="88,116 88,92 40,68 28,54" />
                        <polyline points="88,92 64,54 56,28" />
                        <polyline points="88,92 86,44 86,18" />
                        <polyline points="88,92 108,48 114,24" />
                        <polyline points="88,92 126,64 136,44" />
                        <line x1="64" y1="54" x2="126" y2="64" />
                    </g>
                    <g stroke="#0f2238" stroke-width="2">
                        <circle cx="88" cy="116" r="6" fill="#ffe942" />
                        <circle cx="88" cy="92" r="5" fill="#58caf3" />
                        <circle cx="28" cy="54" r="5" fill="#ffe942" />
                        <circle cx="56" cy="28" r="5" fill="#58caf3" />
                        <circle cx="86" cy="18" r="5.5" fill="#ffe942" />
                        <circle cx="114" cy="24" r="5" fill="#58caf3" />
                        <circle cx="136" cy="44" r="5" fill="#58caf3" />
                    </g>
                </svg>
            </div>

            <div class="pixel-cam-hud" style="position: static; margin-top: auto;">
                <span class="pixel-bars" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
                <span class="pixel-hud-label">AI BIM | 30 FPS</span>
            </div>
        </div>

        <div class="console-answer-bar">
            <div>
                <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600;">Isyarat tangan dikesan</div>
                <div style="display: flex; align-items: center; gap: 0.5rem; font-family: var(--font-display); font-size: 1.25rem; font-weight: 600; color: var(--primary);">
                    <x-sc-icon name="ucapan" :size="24" />
                    <span>Terima kasih</span>
                </div>
            </div>
            <button type="button" id="btn-hero-speak" class="btn btn-soft btn-sm">
                <x-sc-icon name="suara-bm" :size="20" />
                <span>Dengar sebutan BM</span>
            </button>
        </div>
    </div>
</section>

{{-- 2. LEARNING WORLDS SECTION --}}
<section id="modul" class="section-block">
    <div class="section-header">
        <div class="section-eyebrow">Hab pembelajaran kanak-kanak &middot; {{ $signCount }}+ Isyarat</div>
        <h2 class="section-title">Pilih modul permainan mengikut topik</h2>
    </div>

    <div class="worlds-grid">
        @foreach($categories as $category)
            @php
                $targetUrl = Auth::guard('student')->check()
                    ? route('learn.category', $category)
                    : route('student.login');
            @endphp
            <a href="{{ $targetUrl }}" class="world-card" style="border-top: 4px solid {{ $category->color }};">
                <div class="world-badge-box">
                    <x-sc-category-icon :category="$category" :size="50" />
                </div>
                <h3 style="font-size: 1.15rem; color: var(--primary); margin: 0;">{{ $category->name }}</h3>
                <div class="pixel-code">{{ $category->signs_count }} isyarat</div>
            </a>
        @endforeach
    </div>
</section>

{{-- 3. PLAYFUL FEATURES SECTION --}}
<section id="ciri" class="section-block">
    <div class="section-header">
        <div class="section-eyebrow">Ciri pembelajaran interaktif</div>
        <h2 class="section-title">Kenapa kanak-kanak seronok belajar di sini</h2>
    </div>

    <div class="features-grid-3">
        <article class="card pixel-card">
            <div class="world-badge-box" style="margin-bottom: 1rem;">
                <x-sc-icon name="kamera-ai" :size="44" />
            </div>
            <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;">Pengecaman Kamera AI</h3>
            <p style="font-size: 0.95rem; color: var(--text-muted);">
                Tunjukkan isyarat tangan di hadapan kamera untuk semakan ketepatan 21 sendi jari secara langsung dan maklum balas segera.
            </p>
        </article>

        <article class="card pixel-card">
            <div class="world-badge-box" style="margin-bottom: 1rem; background: #fefce8; border-color: rgba(234, 179, 8, 0.28);">
                <x-sc-icon name="suara-bm" :size="44" />
            </div>
            <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;">Suara Bahasa Melayu</h3>
            <p style="font-size: 0.95rem; color: var(--text-muted);">
                Setiap isyarat dilengkapi audio sebutan Bahasa Melayu yang jelas bagi membantu ingatan dan pertuturan kanak-kanak.
            </p>
        </article>

        <article class="card pixel-card">
            <div class="world-badge-box" style="margin-bottom: 1rem; background: #ecfdf5; border-color: rgba(16, 185, 129, 0.28);">
                <x-sc-icon name="bintang-kuiz" :size="44" />
            </div>
            <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;">Kuiz &amp; Misi Mingguan</h3>
            <p style="font-size: 0.95rem; color: var(--text-muted);">
                Jawab kuiz bergambar, kumpul bintang setiap hari, dan selesaikan latihan mingguan yang disediakan oleh guru.
            </p>
        </article>
    </div>
</section>

{{-- 4. PLAYFUL ROLES SECTION --}}
<section id="peranan" class="section-block">
    <div class="section-header">
        <div class="section-eyebrow">Bersama di rumah &amp; sekolah</div>
        <h2 class="section-title">Direka untuk pelajar, ibu bapa dan guru</h2>
    </div>

    <div class="roles-grid-3">
        <div class="card pixel-card" style="display: flex; flex-direction: column;">
            <div class="role-kicker-row">
                <x-sc-icon name="pelajar" :size="20" />
                <span>Adik-adik pelajar</span>
            </div>
            <h3 style="font-size: 1.28rem; margin-bottom: 0.75rem;">Saya Murid</h3>
            <ul class="role-list">
                <li>Log masuk mudah guna <strong>Kod Pelajar</strong> &amp; PIN</li>
                <li>Pilih kategori bergambar mengikut tahap</li>
                <li>Cuba kamera AI dan kumpul bintang kuiz</li>
            </ul>
            <a href="{{ route('student.login') }}" class="btn btn-accent btn-sm" style="margin-top: auto;">
                <x-sc-icon name="kod-pin" :size="18" />
                <span>Masuk Kod + PIN</span>
            </a>
        </div>

        <div class="card pixel-card" style="display: flex; flex-direction: column;">
            <div class="role-kicker-row">
                <x-sc-icon name="ibu-bapa" :size="20" />
                <span>Ibu bapa di rumah</span>
            </div>
            <h3 style="font-size: 1.28rem; margin-bottom: 0.75rem;">Pantau &amp; Bimbing Anak</h3>
            <ul class="role-list">
                <li>Cipta profil anak dan kongsi Kod Pelajar</li>
                <li>Pantau markah bintang dan kemajuan anak</li>
                <li>Semak ulasan serta tugasan daripada guru</li>
            </ul>
            <a href="{{ route('login') }}" class="btn btn-primary btn-sm" style="margin-top: auto;">
                Portal Ibu Bapa &amp; Guru
            </a>
        </div>

        <div class="card pixel-card" style="display: flex; flex-direction: column;">
            <div class="role-kicker-row">
                <x-sc-icon name="guru" :size="20" />
                <span>Cikgu di sekolah</span>
            </div>
            <h3 style="font-size: 1.28rem; margin-bottom: 0.75rem;">Urus Kelas &amp; Bina Latihan</h3>
            <ul class="role-list">
                <li>Pautkan Kod Pelajar untuk pantau kelas</li>
                <li>Tulis ulasan kemajuan dan modul mingguan</li>
                <li>Bina set latihan dan kuiz mengikut topik</li>
            </ul>
            <a href="{{ route('register') }}" class="btn btn-outline btn-sm" style="margin-top: auto;">
                Daftar Akaun Baru
            </a>
        </div>
    </div>
</section>

{{-- 5. CTA BANNER --}}
<section class="cta-banner">
    <div style="display: flex; align-items: center; gap: 1.25rem; flex: 1; min-width: 240px;">
        <x-sc-icon name="mascot" :size="84" />
        <div>
            <h2 style="font-size: 1.65rem; color: #fff; margin: 0 0 0.4rem;">Mulakan pembelajaran Bahasa Isyarat hari ini</h2>
            <p style="color: #cbd5e1; margin: 0; max-width: 56ch;">
                Log masuk menggunakan Kod Pelajar dan PIN, atau daftar akaun ibu bapa dan guru secara percuma.
            </p>
        </div>
    </div>
    <a href="{{ route('student.login') }}" class="btn btn-cta-yellow btn-kid">
        <x-sc-icon name="trofi" :size="22" />
        <span>Mula belajar sekarang</span>
    </a>
</section>

@push('scripts')
<script>
    (() => {
        const speakBtn = document.getElementById('btn-hero-speak');
        if (!speakBtn || !('speechSynthesis' in window)) return;
        speakBtn.addEventListener('click', () => {
            window.speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance('Terima kasih');
            utter.lang = 'ms-MY';
            utter.rate = 0.92;
            window.speechSynthesis.speak(utter);
        });
    })();
</script>
@endpush
@endsection

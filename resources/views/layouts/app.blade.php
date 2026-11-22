<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sign Cilik - Pembelajaran Bahasa Isyarat Interaktif' }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('media/brand/signcilik-icon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <!-- Custom Pixel Font (CutePixel) -->
    <link rel="preload" href="{{ asset('fonts/CutePixel.ttf') }}" as="font" type="font/ttf" crossorigin>

    <!-- Google Fonts: Fredoka & Nunito -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Core Design System -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <!-- Alpine.js (Lightweight reactive UI) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    @stack('styles')
</head>
<body>
    <a href="#main-content" class="skip-link">Langkau ke kandungan utama</a>

    <header class="site-header">
        <div class="header-container">
            <a href="{{ route('home') }}" class="brand-link">
                @if(file_exists(public_path('media/brand/signcilik-logo.svg')))
                    <img src="{{ asset('media/brand/signcilik-logo.svg') }}" alt="Sign Cilik Logo" class="brand-logo">
                @else
                    <span class="pixel-brand-fallback">Sign Cilik</span>
                @endif
            </a>

            <nav class="nav-links" aria-label="Utama">
                @php
                    $isAdultRoute = request()->routeIs('admin.*', 'guardian.*');
                    $hasStudent = Auth::guard('student')->check();
                    $hasAdult = Auth::guard('web')->check();
                @endphp

                @if($hasStudent && !($isAdultRoute && $hasAdult))
                    {{-- Child Learner Navbar --}}
                    @php $currentStudent = Auth::guard('student')->user(); @endphp
                    <a href="{{ route('learn.hub') }}" class="nav-item nav-item--kid {{ request()->routeIs('learn.*') ? 'active' : '' }}">
                        <x-sc-icon name="abjad-nombor" :size="22" /> <span>Belajar</span>
                    </a>
                    <a href="{{ route('quiz.index') }}" class="nav-item nav-item--kid {{ request()->routeIs('quiz.*') ? 'active' : '' }}">
                        <x-sc-icon name="trofi" :size="22" /> <span>Kuiz</span>
                    </a>

                    <div class="star-pill" style="min-height: var(--touch-min);" title="Jumlah Bintang Dikumpul">
                        <x-sc-icon name="bintang-kuiz" :size="20" />
                        <span id="nav-star-count">{{ $currentStudent->stars }}</span>
                    </div>

                    <div class="nav-item nav-item--kid" style="background: rgba(38, 176, 229, 0.1);">
                        <span style="font-size: 1.35rem;" aria-hidden="true">{{ $currentStudent->avatarEmoji() }}</span>
                        <span>{{ $currentStudent->name }}</span>
                    </div>

                    <form action="{{ route('student.logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm" title="{{ $hasAdult ? 'Kembali ke Dashboard Ibu Bapa / Guru' : 'Keluar / Selesai Belajar' }}">
                            <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i> {{ $hasAdult ? 'Tamat Mod Murid' : 'Keluar' }}
                        </button>
                    </form>

                @elseif($hasAdult)
                    {{-- Adult (Parent / Teacher / Admin) Navbar --}}
                    @php $currentUser = Auth::guard('web')->user(); @endphp

                    @if($currentUser->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <x-sc-icon name="buku" :size="20" /> Dashboard
                        </a>
                        <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                            <x-sc-icon name="ibu-bapa" :size="20" /> Pengguna
                        </a>
                        <a href="{{ route('admin.students') }}" class="nav-item {{ request()->routeIs('admin.students*') ? 'active' : '' }}">
                            <x-sc-icon name="pelajar" :size="20" /> Murid
                        </a>
                        <a href="{{ route('admin.signs') }}" class="nav-item {{ request()->routeIs('admin.signs') ? 'active' : '' }}">
                            <x-sc-icon name="ucapan" :size="20" /> Isyarat
                        </a>
                        <a href="{{ route('admin.ai.studio') }}" class="nav-item {{ request()->routeIs('admin.ai.*') ? 'active' : '' }}" @unless(request()->routeIs('admin.ai.*')) style="color: var(--secondary-dark); font-weight: 800;" @endunless>
                            <x-sc-icon name="robot-ai" :size="20" /> AI Studio
                        </a>
                    @else
                        @if($currentUser->isApproved())
                            <a href="{{ route('guardian.dashboard') }}" class="nav-item {{ request()->routeIs('guardian.*') ? 'active' : '' }}">
                                <x-sc-icon :name="$currentUser->isTeacher() ? 'guru' : 'ibu-bapa'" :size="20" />
                                {{ $currentUser->isTeacher() ? 'Portal Kelas' : 'Portal Ibu Bapa' }}
                            </a>
                        @else
                            <a href="{{ route('guardian.pending') }}" class="nav-item active">
                                <x-sc-icon name="buku" :size="20" /> Status Kelulusan
                            </a>
                        @endif
                    @endif

                    <div class="nav-item" style="background: #f1f5f9;">
                        <span class="role-badge {{ $currentUser->role }}">{{ $currentUser->role }}</span>
                        <span>{{ $currentUser->name }}</span>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm">
                            <i class="fa-solid fa-power-off" aria-hidden="true"></i> Log Keluar
                        </button>
                    </form>

                @else
                    {{-- Guest Navbar --}}
                    <a href="{{ route('home') }}#modul" class="nav-item">
                        <span>Dunia Belajar</span>
                    </a>
                    <a href="{{ route('home') }}#ciri" class="nav-item">
                        <span>Kuiz &amp; Bintang</span>
                    </a>
                    <a href="{{ route('home') }}#peranan" class="nav-item">
                        <span>Ibu Bapa &amp; Guru</span>
                    </a>
                    <a href="{{ route('student.login') }}" class="btn btn-soft btn-sm">
                        <x-sc-icon name="kod-pin" :size="18" /> <span>Kod + PIN Pelajar</span>
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                        Portal Cikgu &amp; Ibu Bapa
                    </a>
                @endif
            </nav>
        </div>
    </header>

    <main class="page-container" id="main-content" tabindex="-1">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success" role="alert">
                <i class="fa-solid fa-circle-check" style="font-size: 1.25rem;" aria-hidden="true"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="alert-dismiss" aria-label="Tutup makluman" onclick="this.closest('.alert').remove()">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error" role="alert">
                <i class="fa-solid fa-circle-exclamation" style="font-size: 1.25rem;" aria-hidden="true"></i>
                <span>{{ session('error') }}</span>
                <button type="button" class="alert-dismiss" aria-label="Tutup makluman" onclick="this.closest('.alert').remove()">&times;</button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error" role="alert">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.25rem;" aria-hidden="true"></i>
                <div>
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
                <button type="button" class="alert-dismiss" aria-label="Tutup makluman" onclick="this.closest('.alert').remove()">&times;</button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-grid">
                <div>
                    <img
                        src="{{ asset('media/brand/signcilik-logo-dark.svg') }}"
                        alt="Sign Cilik"
                        width="184"
                        height="62"
                        loading="lazy"
                        decoding="async"
                        style="height: 52px; width: auto;"
                    >
                    <p class="footer-brand-desc">
                        Dunia permainan &amp; pembelajaran Bahasa Isyarat Malaysia (BIM) yang ceria untuk kanak-kanak, ibu bapa, dan guru.
                    </p>
                </div>

                <nav aria-label="Dunia permainan">
                    <h3 class="footer-heading">Dunia permainan</h3>
                    <ul class="footer-links">
                        <li><a href="{{ route('home') }}#modul">Abjad, Nombor &amp; Ucapan Harian</a></li>
                        <li><a href="{{ route('home') }}#ciri">Kuiz Bintang &amp; Latihan Cikgu</a></li>
                        <li><a href="{{ route('home') }}#ciri">Kamera AI &amp; Audio Sebutan BM</a></li>
                    </ul>
                </nav>

                <nav aria-label="Pintu masuk">
                    <h3 class="footer-heading">Pintu masuk</h3>
                    <ul class="footer-links">
                        <li><a href="{{ route('student.login') }}">Masuk Pelajar (Kod + PIN)</a></li>
                        <li><a href="{{ route('login') }}">Portal Ibu Bapa &amp; Guru</a></li>
                        <li><a href="{{ route('register') }}">Daftar Akaun Ibu Bapa &amp; Cikgu</a></li>
                    </ul>
                </nav>
            </div>

            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} <strong style="color: #fff;">SignCilik</strong> &mdash; Platform pembelajaran Bahasa Isyarat Malaysia (BIM) untuk kanak-kanak, ibu bapa dan guru</span>
                <span>Dikuasakan oleh MediaPipe AI &amp; Laravel</span>
            </div>
        </div>
    </footer>

    @if(request()->routeIs('learn.*', 'quiz.*'))
        @include('partials.kiko-mascot')
    @endif

    @stack('scripts')
</body>
</html>

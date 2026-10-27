<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'GestureSpeak - Pembelajaran Bahasa Isyarat Interaktif' }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('media/brand/logo-square.svg') }}">

    <!-- Google Fonts: Nunito & Fredoka -->
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
    <header class="site-header">
        <div class="header-container">
            <a href="{{ route('home') }}" class="brand-link">
                @if(file_exists(public_path('media/brand/logo.png')))
                    <img src="{{ asset('media/brand/logo.png') }}" alt="GestureSpeak Logo" class="brand-logo">
                @else
                    <span>🤟 GestureSpeak</span>
                @endif
            </a>

            <nav class="nav-links">
                @if(Auth::guard('student')->check())
                    {{-- Child Learner Navbar --}}
                    @php $currentStudent = Auth::guard('student')->user(); @endphp
                    <a href="{{ route('learn.hub') }}" class="nav-item {{ request()->routeIs('learn.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-shapes"></i> Belajar
                    </a>
                    <a href="{{ route('quiz.index') }}" class="nav-item {{ request()->routeIs('quiz.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-trophy"></i> Kuiz
                    </a>

                    <div class="star-pill" title="Jumlah Bintang Dikumpul">
                        ⭐ <span id="nav-star-count">{{ $currentStudent->stars }}</span>
                    </div>

                    <div class="nav-item" style="background: rgba(108, 92, 231, 0.08);">
                        <span style="font-size: 1.3rem;">{{ $currentStudent->avatarEmoji() }}</span>
                        <span>{{ $currentStudent->name }}</span>
                    </div>

                    <form action="{{ route('student.logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm" title="Keluar / Selesai Belajar">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
                        </button>
                    </form>

                @elseif(Auth::guard('web')->check())
                    {{-- Adult (Parent / Teacher / Admin) Navbar --}}
                    @php $currentUser = Auth::guard('web')->user(); @endphp

                    @if($currentUser->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="fa-solid fa-gauge-high"></i> Dashboard
                        </a>
                        <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                            <i class="fa-solid fa-users"></i> Pengguna
                        </a>
                        <a href="{{ route('admin.students') }}" class="nav-item {{ request()->routeIs('admin.students') ? 'active' : '' }}">
                            <i class="fa-solid fa-children"></i> Murid
                        </a>
                        <a href="{{ route('admin.signs') }}" class="nav-item {{ request()->routeIs('admin.signs') ? 'active' : '' }}">
                            <i class="fa-solid fa-hands-asl-interpreting"></i> Isyarat
                        </a>
                        <a href="{{ route('admin.ai.studio') }}" class="nav-item {{ request()->routeIs('admin.ai.*') ? 'active' : '' }}" @unless(request()->routeIs('admin.ai.*')) style="color: #6c5ce7; font-weight: 800;" @endunless>
                            <i class="fa-solid fa-robot"></i> AI Studio
                        </a>
                    @else
                        <a href="{{ route('guardian.dashboard') }}" class="nav-item {{ request()->routeIs('guardian.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-chalkboard-user"></i> Dashboard
                        </a>
                    @endif

                    <div class="nav-item" style="background: #f1f2f6;">
                        <span class="role-badge {{ $currentUser->role }}">{{ $currentUser->role }}</span>
                        <span>{{ $currentUser->name }}</span>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm">
                            <i class="fa-solid fa-power-off"></i> Log Keluar
                        </button>
                    </form>

                @else
                    {{-- Guest Navbar --}}
                    <a href="{{ route('student.login') }}" class="btn btn-accent btn-sm">
                        <i class="fa-solid fa-child"></i> Masuk Murid
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-user-tie"></i> Ibu Bapa / Guru
                    </a>
                @endif
            </nav>
        </div>
    </header>

    <main class="page-container">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check" style="font-size: 1.2rem;"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation" style="font-size: 1.2rem;"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.2rem;"></i>
                <div>
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer">
        <p><strong>GestureSpeak V2</strong> &bull; Sistem Pembelajaran Bahasa Isyarat Malaysia (BIM) Interaktif Kanak-kanak</p>
        <p style="margin-top: 0.3rem; font-size: 0.8rem; color: #a4b0be;">Dikuasakan oleh MediaPipe AI & Laravel &bull; Hak Cipta Terpelihara</p>
    </footer>

    @stack('scripts')
</body>
</html>

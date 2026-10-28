@extends('layouts.app')

@section('content')
<div style="text-align: center; margin-bottom: 2.5rem;">
    <div style="display: inline-block; padding: 0.4rem 1.2rem; background: #e0f2fe; color: #0284c7; border-radius: var(--radius-full); font-weight: 800; font-size: 0.85rem; margin-bottom: 1rem;">
        🌟 Platform Pembelajaran BIM Pintar & Interaktif
    </div>
    <h1 class="title-large" style="margin-bottom: 0.8rem;">
        Belajar Bahasa Isyarat Jadi <span class="text-gradient">Mudah & Menyeronokkan!</span>
    </h1>
    <p style="font-size: 1.15rem; color: var(--text-muted); max-width: 650px; margin: 0 auto;">
        Bimbing anak anda menguasai Bahasa Isyarat Malaysia (BIM) melalui kamera kecerdasan buatan (AI), bimbingan video langkah-demi-langkah, dan kuiz beranimasi.
    </p>
</div>

{{-- 2 BIG OPTIONS FOR LOGIN --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; margin-bottom: 3.5rem;">

    {{-- OPTION 1: STUDENT / CHILD --}}
    <div class="card card-hover" style="border: 3px solid #ff7675; position: relative; overflow: hidden; background: linear-gradient(180deg, #fff 0%, #fff5f5 100%);">
        <div style="position: absolute; top: -15px; right: -15px; background: #ff7675; color: #fff; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: flex-end; justify-content: flex-start; padding: 12px; font-size: 1.5rem;">
            🚀
        </div>

        <div style="font-size: 3rem; margin-bottom: 0.5rem;">🧒👧</div>
        <div style="color: #ff7675; font-weight: 900; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px;">
            Pilihan 1: Untuk Murid
        </div>
        <h2 style="font-size: 1.8rem; margin: 0.3rem 0 0.8rem;">Saya Murid</h2>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem; min-height: 48px;">
            Masuk dengan <strong>Kod Murid</strong> & <strong>PIN 4-digit</strong> yang diberikan oleh ibu bapa atau guru untuk mula belajar dan kumpul bintang!
        </p>

        @if(Auth::guard('student')->check())
            <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                <a href="{{ route('learn.hub') }}" class="btn btn-accent btn-lg" style="flex: 1;">
                    <i class="fa-solid fa-gamepad"></i> Teruskan Belajar ({{ Auth::guard('student')->user()->name }})
                </a>
                <form action="{{ route('student.logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-lg" title="Log keluar sesi murid">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        @else
            <a href="{{ route('student.login') }}" class="btn btn-accent btn-lg" style="width: 100%;">
                <i class="fa-solid fa-gamepad"></i> Mula Belajar & Main Sekarang!
            </a>
        @endif
    </div>

    {{-- OPTION 2: PARENT & TEACHER (AND ADMIN) --}}
    <div class="card card-hover" style="border: 3px solid var(--primary); position: relative; overflow: hidden; background: linear-gradient(180deg, #fff 0%, #f7f5ff 100%);">
        <div style="position: absolute; top: -15px; right: -15px; background: var(--primary); color: #fff; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: flex-end; justify-content: flex-start; padding: 12px; font-size: 1.5rem;">
            👨‍👩‍👧
        </div>

        <div style="font-size: 3rem; margin-bottom: 0.5rem;">👨‍👩‍👦‍👦 👩‍🏫</div>
        <div style="color: var(--primary); font-weight: 900; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px;">
            Pilihan 2: Untuk Dewasa
        </div>
        <h2 style="font-size: 1.8rem; margin: 0.3rem 0 0.8rem;">Ibu Bapa & Guru</h2>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem; min-height: 48px;">
            Daftar akaun, cipta profil anak, kongsi kod murid bersama guru, dan pantau kemajuan serta pencapaian kuiz secara langsung.
        </p>

        @if($adultUser = Auth::guard('web')->user())
            <div style="display: flex; gap: 0.8rem; flex-wrap: wrap;">
                <a href="{{ route($adultUser->homeRoute()) }}" class="btn btn-primary" style="flex: 1; min-width: 160px;">
                    <i class="fa-solid fa-gauge-high"></i> Buka Dashboard ({{ $adultUser->name }})
                </a>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-outline" title="Log keluar akaun dewasa">
                        <i class="fa-solid fa-power-off"></i> Log Keluar
                    </button>
                </form>
            </div>
        @else
            <div style="display: flex; gap: 0.8rem; flex-wrap: wrap;">
                <a href="{{ route('login') }}" class="btn btn-primary" style="flex: 1; min-width: 140px;">
                    <i class="fa-solid fa-right-to-bracket"></i> Log Masuk
                </a>
                <a href="{{ route('register') }}" class="btn btn-outline" style="flex: 1; min-width: 140px;">
                    <i class="fa-solid fa-user-plus"></i> Daftar Baru
                </a>
            </div>
        @endif
    </div>

</div>

{{-- MODULE PREVIEW CAROUSEL/GRID --}}
<div style="margin-bottom: 3rem;">
    <div style="text-align: center; margin-bottom: 1.8rem;">
        <h2 style="font-size: 1.8rem;">Modul Pembelajaran Tersedia ({{ $signCount }}+ Isyarat)</h2>
        <p style="color: var(--text-muted);">Kurikulum Bahasa Isyarat Malaysia (BIM) tersusun untuk kanak-kanak:</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
        @foreach($categories as $category)
            <div class="card card-hover" style="text-align: center; padding: 1.2rem; border-top: 4px solid {{ $category->color }};">
                <div style="font-size: 2.2rem; margin-bottom: 0.3rem;">{{ $category->icon ?? '📖' }}</div>
                <h4 style="font-size: 1.1rem; margin-bottom: 0.2rem;">{{ $category->name }}</h4>
                <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $category->signs_count }} isyarat</div>
            </div>
        @endforeach
    </div>
</div>

{{-- THREE KEY FEATURES --}}
<div class="card" style="background: #ffffff; border: 2px dashed rgba(108, 92, 231, 0.25); padding: 2rem; margin-bottom: 2rem;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 2rem; text-align: center;">
        <div>
            <div style="font-size: 2.5rem; color: var(--secondary); margin-bottom: 0.5rem;">
                <i class="fa-solid fa-video"></i>
            </div>
            <h3 style="font-size: 1.2rem; margin-bottom: 0.4rem;">Pengecaman AI Kamera</h3>
            <p style="font-size: 0.95rem; color: var(--text-muted);">
                MediaPipe AI mengesan 21 titik sendi jari melalui webcam secara langsung tanpa peranti mahal.
            </p>
        </div>

        <div>
            <div style="font-size: 2.5rem; color: var(--accent-gold); margin-bottom: 0.5rem;">
                <i class="fa-solid fa-volume-high"></i>
            </div>
            <h3 style="font-size: 1.2rem; margin-bottom: 0.4rem;">Suara Digital (TTS)</h3>
            <p style="font-size: 0.95rem; color: var(--text-muted);">
                Sistem menuturkan sebutan isyarat secara audio untuk menghubungkan komunikasi anak pekak & bisu bersama keluarga.
            </p>
        </div>

        <div>
            <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 0.5rem;">
                <i class="fa-solid fa-handshake-angle"></i>
            </div>
            <h3 style="font-size: 1.2rem; margin-bottom: 0.4rem;">Kolaborasi Rumah & Sekolah</h3>
            <p style="font-size: 0.95rem; color: var(--text-muted);">
                Gunakan Kod Murid (cth: <code>ADAM-4821</code>) agar ibu bapa di rumah & guru di sekolah berkongsi laporan kemajuan anak yang sama.
            </p>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app', ['title' => 'Pusat Pembelajaran - GestureSpeak'])

@section('content')
{{-- HERO WELCOME CARD --}}
<div class="card" style="background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%); color: #fff; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem; box-shadow: var(--shadow-lg);">
    <div style="display: flex; align-items: center; gap: 1.2rem;">
        <span style="font-size: 4rem; background: rgba(255,255,255,0.25); width: 88px; height: 88px; border-radius: 50%; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
            {{ $student->avatarEmoji() }}
        </span>
        <div>
            <div style="font-size: 1rem; font-weight: 700; opacity: 0.9;">Selamat Belajar,</div>
            <h1 style="font-size: 2.2rem; color: #fff; margin: 0;">{{ $student->name }}! 🌟</h1>
            <div style="margin-top: 0.3rem; opacity: 0.95; font-size: 0.95rem;">
                Kod Murid: <strong>{{ $student->student_code }}</strong>
            </div>
        </div>
    </div>

    <div style="text-align: right; background: rgba(255,255,255,0.2); padding: 1rem 1.5rem; border-radius: var(--radius-md); backdrop-filter: blur(8px);">
        <div style="font-size: 0.85rem; font-weight: 800; text-transform: uppercase;">Bintang Terkumpul</div>
        <div style="font-size: 2.5rem; font-weight: 900; color: #ffeaa7; font-family: var(--font-display);">
            ⭐ {{ $student->stars }}
        </div>
    </div>
</div>

{{-- QUIZ CHALLENGE BANNER --}}
<div class="card card-hover" style="background: linear-gradient(135deg, #ffa502 0%, #ff7675 100%); color: #fff; margin-bottom: 2.5rem; padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div style="display: flex; align-items: center; gap: 1rem;">
        <div style="font-size: 3rem;">🏆</div>
        <div>
            <h2 style="font-size: 1.5rem; color: #fff; margin: 0;">Jom Main Cabaran Kuiz BIM!</h2>
            <p style="opacity: 0.95; font-size: 0.95rem; margin: 0;">Uji kemahiran isyarat anda dan kumpulkan bintang tambahan!</p>
        </div>
    </div>
    <a href="{{ route('quiz.index') }}" class="btn btn-lg" style="background: #fff; color: #d35400; font-weight: 900;">
        <i class="fa-solid fa-gamepad"></i> Buka Pilihan Kuiz 🚀
    </a>
</div>

{{-- CATEGORIES GRID --}}
<div style="margin-bottom: 2.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1.2rem;">
        <h2 style="font-size: 1.6rem;">Pilih Modul Pembelajaran</h2>
        <span style="color: var(--text-muted); font-size: 0.9rem;">7 Modul Lengkap</span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
        @foreach($categories as $cat)
            <div class="card card-hover" style="border-top: 6px solid {{ $cat->color }}; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.8rem;">
                    <div style="font-size: 2.8rem; background: #f8fafc; width: 60px; height: 60px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                        {{ $cat->icon ?? '📖' }}
                    </div>
                    <span style="font-size: 1rem; font-weight: 900; color: {{ $cat->color }}; font-family: var(--font-display);">
                        {{ $cat->percent }}%
                    </span>
                </div>

                <h3 style="font-size: 1.3rem; margin-bottom: 0.2rem;">{{ $cat->name }}</h3>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                    {{ $cat->name_en }} &bull; {{ $cat->signs_count }} Isyarat
                </div>

                <div class="progress-track" style="height: 10px; margin-bottom: 0.5rem;">
                    <div class="progress-fill" style="width: {{ $cat->percent }}%; background: {{ $cat->color }};"></div>
                </div>

                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.5rem;">
                    {{ $cat->done }} daripada {{ $cat->signs_count }} selesai
                </div>

                <a href="{{ route('learn.category', $cat) }}" class="btn btn-sm" style="margin-top: auto; width: 100%; background: {{ $cat->color }}; color: #fff;">
                    <i class="fa-solid fa-book-open-reader"></i> Mula Modul Ini
                </a>
            </div>
        @endforeach
    </div>
</div>

{{-- RECENT PRACTICE SHELF --}}
@if($recentProgress->isNotEmpty())
    <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 1rem;">
            <i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);"></i> Latihan Terkini Anda
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem;">
            @foreach($recentProgress as $p)
                <a href="{{ route('learn.sign', [$p->sign->category, $p->sign]) }}" class="card card-hover" style="padding: 1rem; text-align: center; border: 1px solid var(--border-light); background: #f8fafc;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.3rem;">
                        {{ $p->sign->is_static ? '✋' : '👋' }}
                    </div>
                    <div style="font-weight: 800; font-size: 1.1rem; color: var(--text-dark);">
                        {{ $p->sign->word }}
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">
                        {{ $p->sign->category->name }}
                    </div>
                    @if($p->mastered)
                        <span style="display: inline-block; margin-top: 0.4rem; font-size: 0.75rem; font-weight: 800; color: #16a34a; background: #dcfce7; padding: 0.2rem 0.5rem; border-radius: var(--radius-full);">
                            ⭐ Dikuasai
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
@endif
@endsection

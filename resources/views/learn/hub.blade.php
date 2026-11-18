@extends('layouts.app', ['title' => 'Pusat Pembelajaran - Sign Cilik'])

@section('content')
{{-- HERO WELCOME CARD --}}
<section class="card pixel-card student-banner">
    <div style="display: flex; align-items: center; gap: var(--space-4);">
        <span class="student-avatar-circle" aria-hidden="true">
            {{ $student->avatarEmoji() }}
        </span>
        <div>
            <div class="pixel-hud" style="color: #7dd3fc; margin-bottom: 0.25rem;">SELAMAT BELAJAR</div>
            <h1 style="font-size: 2.2rem; color: #fff; margin: 0;">{{ $student->name }}! 🌟</h1>
            <div style="margin-top: var(--space-2); font-size: 0.92rem; color: #cbd5e1; display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap;">
                <span>Kod Murid:</span> <span class="pixel-code pixel-code--dark">{{ $student->student_code }}</span>
            </div>
        </div>
    </div>

    <div style="display: flex; align-items: center; gap: var(--space-4); flex-wrap: wrap;">
        <button type="button" onclick="window.Kiko?.randomCheer(true)" class="btn" style="background: #ffffff; color: #16324f;">
            🐾 Semangat Kiko!
        </button>

        <div class="student-stars-box">
            <div class="pixel-hud" style="font-size: 0.82rem; color: #7dd3fc;">BINTANG TERKUMPUL</div>
            <div class="pixel-stat-num" style="font-size: 2.15rem; font-weight: 500; color: #ffe942; line-height: 1.15; margin-top: 0.2rem; display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
                <x-sc-icon name="bintang-kuiz" :size="30" />
                <span>{{ $student->stars }}</span>
            </div>
        </div>
    </div>
</section>

{{-- TEACHER HOMEWORK BANNER (IF ASSIGNED) --}}
@if($student->assignedCategory || $student->teacher_note)
<section class="card homework-banner" aria-label="Tugasan Cikgu">
    <div style="display: flex; align-items: center; gap: var(--space-4);">
        <x-sc-icon name="guru" :size="50" />
        <div>
            <div style="font-size: 0.82rem; font-weight: 900; text-transform: uppercase; opacity: 0.95;">
                Tugasan daripada {{ $student->teacherReviewer->name ?? 'Cikgu' }}
            </div>
            @if($student->assignedCategory)
                <h2 style="font-size: 1.4rem; color: #fff; margin: var(--space-1) 0;">
                    Jom Latih Modul: {{ $student->assignedCategory->name }}!
                </h2>
            @endif
            @if($student->teacher_note)
                <p style="opacity: 0.95; font-size: 0.95rem; margin: var(--space-1) 0 0;">“{{ $student->teacher_note }}”</p>
            @endif
        </div>
    </div>
    @if($student->assignedCategory)
        <a href="{{ route('learn.category', $student->assignedCategory) }}" class="btn btn-kid" style="background: #fff; color: #0369a1;">
            <x-sc-icon name="bintang-kuiz" :size="22" /> Buka Tugasan Cikgu
        </a>
    @endif
</section>
@endif

{{-- QUIZ CHALLENGE BANNER --}}
<section class="card card-hover quiz-banner" aria-label="Cabaran Kuiz BIM">
    <div style="display: flex; align-items: center; gap: var(--space-4);">
        <x-sc-icon name="trofi" :size="54" />
        <div>
            <h2 style="font-size: 1.55rem; color: #fff; margin: 0;">Jom Main Cabaran Kuiz &amp; Ejaan BIM!</h2>
            <p style="opacity: 0.95; font-size: 0.95rem; margin: 0;">Uji kemahiran isyarat anda dan kumpulkan bintang tambahan!</p>
        </div>
    </div>
    <a href="{{ route('quiz.index') }}" class="btn btn-kid" style="background: #fff; color: var(--primary);">
        <x-sc-icon name="bintang-kuiz" :size="22" /> Buka Pilihan Kuiz
    </a>
</section>

{{-- CATEGORIES GRID --}}
<section style="margin-bottom: var(--space-6);">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: var(--space-4); flex-wrap: wrap; gap: var(--space-2);">
        <h2 style="font-size: 1.65rem;">Pilih Modul Pembelajaran</h2>
        <span class="pixel-code">{{ $categories->count() }} Modul Lengkap</span>
    </div>

    <div class="module-grid">
        @foreach($categories as $cat)
            @php $isAssigned = (int) $student->assigned_category_id === (int) $cat->id; @endphp
            <div class="card card-hover module-card" style="border-top: 6px solid {{ $cat->color }}; {{ $isAssigned ? 'box-shadow: 0 0 0 3px var(--role-teacher), var(--shadow-md);' : '' }}">
                @if($isAssigned)
                    <div style="position: absolute; top: -12px; right: 14px; background: var(--role-teacher); color: #fff; font-size: 0.78rem; font-weight: 900; padding: 0.25rem 0.75rem; border-radius: var(--radius-full);">
                        Tugasan Cikgu!
                    </div>
                @endif

                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--space-3);">
                    <div class="module-icon-box" aria-hidden="true">
                        <x-sc-category-icon :category="$cat" :size="50" />
                    </div>
                    <div style="text-align: right;">
                        <span class="pixel-stat-num" style="font-size: 1.1rem; font-weight: 700; color: {{ $cat->color }}; display: block;">
                            {{ $cat->percent }}%
                        </span>
                        @if($cat->percent >= 100)
                            <span class="status-pill status-pill--done">Selesai!</span>
                        @endif
                    </div>
                </div>

                <h3 style="font-size: 1.35rem; margin-bottom: var(--space-1);">{{ $cat->name }}</h3>
                <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: var(--space-4); font-weight: 700;">
                    {{ $cat->name_en }} &bull; {{ $cat->signs_count }} Isyarat
                </div>

                <div class="progress-track" style="height: 12px; margin-bottom: var(--space-2);">
                    <div class="progress-fill" style="width: {{ $cat->percent }}%; background: {{ $cat->color }};"></div>
                </div>

                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: var(--space-5); font-weight: 700;">
                    {{ $cat->done }} daripada {{ $cat->signs_count }} selesai
                </div>

                <a href="{{ route('learn.category', $cat) }}" class="btn btn-block" style="margin-top: auto; background: {{ $cat->color }}; color: #fff;">
                    <i class="fa-solid fa-book-open-reader" aria-hidden="true"></i> Mula Modul Ini
                </a>
            </div>
        @endforeach
    </div>
</section>

{{-- RECENT PRACTICE SHELF --}}
@if($recentProgress->isNotEmpty())
    <section class="card">
        <h3 style="font-size: 1.25rem; margin-bottom: var(--space-4);">
            <i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);" aria-hidden="true"></i> Latihan Terkini Anda
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(185px, 1fr)); gap: var(--space-4);">
            @foreach($recentProgress as $p)
                <a href="{{ route('learn.sign', [$p->sign->category, $p->sign]) }}" class="card card-hover" style="padding: var(--space-4); text-align: center; border: 2px solid var(--border-light); background: #f8fafc;">
                    <div style="display: flex; justify-content: center; margin-bottom: var(--space-2);" aria-hidden="true">
                        <x-sc-icon :name="$p->sign->is_static ? 'kamera-ai' : 'robot-ai'" :size="36" />
                    </div>
                    <div style="font-weight: 800; font-size: 1.15rem; color: var(--text-dark);">
                        {{ $p->sign->word }}
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: var(--space-1);">
                        {{ $p->sign->category->name }}
                    </div>
                    <div style="margin-top: var(--space-2); display: flex; justify-content: center; gap: 0.3rem; flex-wrap: wrap;">
                        <span class="status-pill {{ $p->sign->is_static ? 'status-pill--ai' : 'status-pill--video' }}">
                            {{ $p->sign->is_static ? 'Statik' : 'Dinamik' }}
                        </span>
                        @if($p->mastered)
                            <span class="status-pill status-pill--done">Dikuasai</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif
@endsection

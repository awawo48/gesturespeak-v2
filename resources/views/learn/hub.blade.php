@extends('layouts.app', ['title' => 'Pusat Pembelajaran - Sign Cilik'])

@section('content')
{{-- UNIFIED HERO MASTER CONSOLE (FIGCRAFT FRAME #624 / #1136) --}}
<section class="card hero-master-console" aria-label="Konsol Utama Pelajar">
    {{-- Tier 1: Learner Identity & Telemetry Bar --}}
    <div class="hero-top-bar">
        <div class="learner-id-group">
            <span class="student-avatar-circle" aria-hidden="true">
                {{ $student->avatarEmoji() }}
            </span>
            <div class="learner-meta-col">
                <div class="hero-kicker-row">
                    <span class="pixel-hud" style="color: #7dd3fc;">SELAMAT BELAJAR</span>
                    <span class="hud-dot" aria-hidden="true">•</span>
                    <span class="status-pill status-pill--online">DALAM TALIAN</span>
                </div>
                <h1 class="hero-greeting">Hai, {{ $student->name }}! 🌟</h1>
                <div class="hero-meta-row">
                    <span class="pixel-code pixel-code--dark">KOD: {{ $student->student_code }}</span>
                    <span class="school-label">{{ $student->grade ?? 'Tahun 2 Pintar' }} &bull; {{ $student->school ?? 'SK Seri Cilik' }}</span>
                </div>
            </div>
        </div>

        <div class="hero-telemetry-group">
            <button type="button" onclick="window.Kiko?.randomCheer(true)" class="btn btn-kiko-cheer">
                <i class="fa-solid fa-robot" aria-hidden="true"></i> Semangat Kiko!
            </button>

            <div class="student-stars-box">
                <div class="pixel-hud" style="font-size: 0.78rem; color: #7dd3fc;">BINTANG TERKUMPUL</div>
                <div class="star-value-row">
                    <x-sc-icon name="bintang-kuiz" :size="28" />
                    <span class="pixel-stat-num" style="font-size: 1.85rem; font-weight: 500; color: #ffe942; line-height: 1;">
                        {{ $student->stars }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tier 2: Dual Mission Deck --}}
    <div class="hero-mission-deck">
        {{-- Mission Tile 1: Tugasan Cikgu --}}
        <div class="mission-tile mission-tile--homework">
            <div class="mission-left">
                <div class="mission-icon-box" aria-hidden="true">
                    <x-sc-icon name="guru" :size="36" />
                </div>
                <div class="mission-details">
                    <div class="mission-kicker-row">
                        <span class="mission-tag">TUGASAN {{ Str::upper($student->teacherReviewer->name ?? 'CIKGU') }}</span>
                        <span class="due-chip">Hantar: Khamis</span>
                    </div>
                    <h2 class="mission-title">
                        Modul: {{ $student->assignedCategory->name ?? 'Ucapan Harian' }}!
                    </h2>
                    <p class="mission-sub">
                        {{ $student->teacher_note ? '“' . $student->teacher_note . '”' : '5 isyarat salam · Kumpul 20 bintang bonus' }}
                    </p>
                </div>
            </div>
            <a href="{{ $student->assignedCategory ? route('learn.category', $student->assignedCategory) : '#modul-pembelajaran' }}" class="btn btn-mission-homework">
                Buka Tugasan &rarr;
            </a>
        </div>

        {{-- Mission Tile 2: Cabaran Kuiz BIM --}}
        <div class="mission-tile mission-tile--quiz">
            <div class="mission-left">
                <div class="mission-icon-box" aria-hidden="true">
                    <x-sc-icon name="trofi" :size="36" />
                </div>
                <div class="mission-details">
                    <div class="mission-kicker-row">
                        <span class="mission-tag mission-tag--amber">CABARAN MINGGUAN</span>
                        <span class="reward-chip">+50 BINTANG</span>
                    </div>
                    <h2 class="mission-title">Kuiz Pantas &amp; Ejaan BIM</h2>
                    <p class="mission-sub">Uji kepantasan isyarat bersama Kiko</p>
                </div>
            </div>
            <a href="{{ route('quiz.index') }}" class="btn btn-mission-quiz">
                Buka Pilihan Kuiz &rarr;
            </a>
        </div>
    </div>
</section>

{{-- CATEGORIES GRID (FIGCRAFT FRAME #624 LEARNING WORLDS) --}}
<section style="margin-bottom: var(--space-6);">
    <div id="modul-pembelajaran" style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: var(--space-4); flex-wrap: wrap; gap: var(--space-2);">
        <div>
            <h2 style="font-family: var(--font-display, 'Fredoka'); font-size: 1.6rem; color: var(--primary); margin: 0;">Pilih Dunia Pembelajaran</h2>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 2px;">Terokai dunia Bahasa Isyarat Malaysia bersama Kiko</p>
        </div>
        <span class="pixel-code">{{ $categories->count() }} Modul Lengkap</span>
    </div>

    <div class="module-grid">
        @foreach($categories as $cat)
            @php $isAssigned = (int) $student->assigned_category_id === (int) $cat->id; @endphp
            <div class="card module-card" style="border-top: 5px solid {{ $cat->color }}; {{ $isAssigned ? 'box-shadow: 0 0 0 2px var(--role-teacher), var(--shadow-md);' : '' }}">
                @if($isAssigned)
                    <div class="assigned-module-badge">
                        <x-sc-icon name="guru" :size="14" /> Tugasan Cikgu!
                    </div>
                @endif

                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--space-3);">
                    <div class="module-icon-box" aria-hidden="true">
                        <x-sc-category-icon :category="$cat" :size="44" />
                    </div>
                    <div style="text-align: right;">
                        <span class="pixel-stat-num" style="font-size: 1.15rem; font-weight: 700; color: {{ $cat->percent >= 100 ? 'var(--success-text, #047857)' : 'var(--secondary-text, #166891)' }}; display: block;">
                            {{ $cat->percent }}%
                        </span>
                        @if($cat->percent >= 100)
                            <span class="status-pill status-pill--done" style="font-size: 0.72rem; padding: 2px 7px;">Selesai! ★</span>
                        @endif
                    </div>
                </div>

                <h3 style="font-family: var(--font-display, 'Fredoka'); font-size: 1.3rem; margin-bottom: 2px; color: var(--primary);">{{ $cat->name }}</h3>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: var(--space-4); font-weight: 600;">
                    {{ $cat->name_en }} &bull; {{ $cat->signs_count }} Isyarat
                </div>

                <div class="progress-track" style="margin-bottom: var(--space-2); background: rgba(22, 50, 79, 0.08); height: 8px; border-radius: 9999px;">
                    <div class="progress-fill" style="width: {{ $cat->percent }}%; background: {{ $cat->percent >= 100 ? 'var(--pixel-green, #28c76f)' : 'var(--secondary-light, #58caf3)' }}; height: 100%; border-radius: 9999px;"></div>
                </div>

                <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: var(--space-5); font-weight: 600;">
                    {{ $cat->done }} daripada {{ $cat->signs_count }} selesai
                </div>

                <a href="{{ route('learn.category', $cat) }}" class="btn btn-block btn-module-cta" style="margin-top: auto; background: {{ $cat->color }}; color: #ffffff;">
                    <i class="fa-solid fa-book-open-reader" aria-hidden="true"></i> Mula Modul Ini &rarr; <span class="visually-hidden">{{ $cat->name }}</span>
                </a>
            </div>
        @endforeach
    </div>
</section>

{{-- RECENT PRACTICE SHELF (FIGCRAFT FRAME #624 RECENT SECTION) --}}
@if($recentProgress->isNotEmpty())
    <section class="card" style="padding: var(--space-5); border: 1px solid var(--border-quiet, #cbd9e8); border-radius: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4); flex-wrap: wrap; gap: var(--space-2);">
            <h3 style="font-family: var(--font-display, 'Fredoka'); font-size: 1.25rem; color: var(--primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);" aria-hidden="true"></i> Latihan Terkini Anda
            </h3>
            <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Sambung latihan isyarat bersama Kiko</span>
        </div>

        <div class="recent-shelf-grid">
            @foreach($recentProgress as $p)
                <a href="{{ route('learn.sign', [$p->sign->category, $p->sign]) }}" class="recent-shelf-card">
                    <div style="display: flex; justify-content: center; margin-bottom: var(--space-2);" aria-hidden="true">
                        <x-sc-icon :name="$p->sign->is_static ? 'kamera-ai' : 'robot-ai'" :size="36" />
                    </div>
                    <div style="font-family: var(--font-display, 'Fredoka'); font-weight: 600; font-size: 1.15rem; color: var(--text-dark);">
                        {{ $p->sign->word }}
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        {{ $p->sign->category->name }}
                    </div>
                    <div style="margin-top: var(--space-2); display: flex; justify-content: center; align-items: center; gap: 4px; flex-wrap: wrap;">
                        <span class="status-pill {{ $p->sign->is_static ? 'status-pill--ai' : 'status-pill--video' }}">
                            {{ $p->sign->is_static ? 'Statik' : 'Dinamik' }}
                        </span>
                        @if($p->best_confidence)
                            <span class="pixel-stat-chip">
                                Padanan: {{ round($p->best_confidence * 100) }}%
                            </span>
                        @endif
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

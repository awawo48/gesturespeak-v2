@extends('layouts.app', ['title' => 'Modul: '.$category->name])

@section('content')
<div style="margin-bottom: var(--space-4);">
    <a href="{{ route('learn.hub') }}" class="btn btn-outline">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Kembali ke Pusat Pembelajaran
    </a>
</div>

{{-- CATEGORY HEADER BANNER --}}
<section class="card pixel-card" style="background: linear-gradient(135deg, {{ $category->color }} 0%, #0f2238 185%); color: #fff; padding: var(--space-6); border-radius: var(--radius-lg); margin-bottom: var(--space-6); border: none;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4); margin-bottom: var(--space-4);">
        <div style="display: flex; align-items: center; gap: var(--space-4);">
            <span style="background: rgba(255, 255, 255, 0.92); width: 82px; height: 82px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid rgba(255, 255, 255, 0.5);" aria-hidden="true">
                <x-sc-category-icon :category="$category" :size="58" />
            </span>
            <div>
                <h1 style="font-size: 2.2rem; color: #fff; margin: 0;">Modul {{ $category->name }}</h1>
                <p style="opacity: 0.95; margin: var(--space-1) 0 0; font-size: 1rem;">
                    {{ $category->description ?? 'Pelajari isyarat tangan BIM untuk modul ini.' }}
                </p>
            </div>
        </div>

        <div style="text-align: right; background: rgba(0, 0, 0, 0.22); padding: var(--space-3) var(--space-5); border-radius: var(--radius-md);">
            <div style="font-size: 0.85rem; font-weight: 800;">Kemajuan Modul</div>
            <div class="pixel-stat-num" style="font-size: 2rem; font-weight: 700; line-height: 1.15;">
                {{ $percent }}%
            </div>
            <div style="font-size: 0.85rem; opacity: 0.9;">{{ $doneCount }} / {{ $signs->count() }} selesai</div>
        </div>
    </div>

    {{-- Visual Progress Bar (Immediate Feedback) --}}
    <div class="progress-track" style="height: 12px; background: rgba(255, 255, 255, 0.25);">
        <div class="progress-fill" style="width: {{ $percent }}%; background: #ffffff;"></div>
    </div>
</section>

{{-- SIGNS GRID --}}
<section class="sign-grid" aria-label="Senarai isyarat dalam modul {{ $category->name }}">
    @foreach($signs as $sign)
        <a href="{{ route('learn.sign', [$category, $sign]) }}" class="card card-hover sign-card {{ $sign->is_done ? 'sign-card--done' : '' }}">
            <div style="width: 100%; display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-2);">
                <span class="status-pill {{ $sign->is_static ? 'status-pill--ai' : 'status-pill--video' }}">
                    {{ $sign->is_static ? '✋ Statik' : '🎬 Dinamik' }}
                </span>

                @if($sign->is_done)
                    <span class="status-pill status-pill--done">✔ Selesai</span>
                @endif
            </div>

            <div>
                <div style="font-size: 2.3rem; font-weight: 900; font-family: var(--font-display); color: {{ $sign->is_done ? 'var(--success-dark)' : 'var(--text-dark)' }}; margin-bottom: var(--space-1); line-height: 1.15;">
                    {{ $sign->word }}
                </div>

                <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: var(--space-3); font-weight: 700;">
                    {{ $sign->meaning_en ?? $sign->word }}
                </div>
            </div>

            <div style="display: flex; justify-content: center; gap: 0.4rem; flex-wrap: wrap;">
                @if($sign->video)
                    <span class="status-pill status-pill--video">
                        <i class="fa-solid fa-play" aria-hidden="true"></i> Video
                    </span>
                @endif
                <span class="status-pill status-pill--ai">
                    <i class="fa-solid fa-camera" aria-hidden="true"></i> Kamera AI
                </span>
            </div>
        </a>
    @endforeach
</section>
@endsection

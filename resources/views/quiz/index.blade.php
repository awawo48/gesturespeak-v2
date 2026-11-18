@extends('layouts.app', ['title' => 'Pusat Kuiz BIM - Sign Cilik'])

@section('content')
<div style="margin-bottom: var(--space-4);">
    <a href="{{ route('learn.hub') }}" class="btn btn-outline">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Kembali ke Pusat Pembelajaran
    </a>
</div>

{{-- HERO BANNER --}}
<section class="card quiz-banner" style="margin-bottom: var(--space-6);">
    <div style="display: flex; align-items: center; gap: var(--space-4);">
        <x-sc-icon name="trofi" :size="58" />
        <div>
            <h1 style="font-size: 2.15rem; color: #fff; margin: 0;">Cabaran Kuiz &amp; Ejaan BIM</h1>
            <p style="opacity: 0.95; font-size: 1rem; margin: var(--space-1) 0 0;">
                Jawab soalan dengan betul untuk kumpul sehingga 3 bintang setiap kuiz!
            </p>
        </div>
    </div>

    <div class="star-pill" style="font-size: 1.05rem; padding: 0.6rem 1.25rem;">
        <x-sc-icon name="bintang-kuiz" :size="22" />
        <span>{{ $student->stars }} Bintang</span>
    </div>
</section>

{{-- QUIZ GRID --}}
<section aria-label="Senarai Kuiz Kategori">
    <div class="module-grid">
        @foreach($categories as $cat)
            <div class="card card-hover module-card" style="border-top: 6px solid {{ $cat->color }};">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--space-4);">
                    <div class="module-icon-box" aria-hidden="true">
                        <x-sc-category-icon :category="$cat" :size="50" />
                    </div>

                    {{-- VISUAL 3-STAR PROGRESS SLOTS --}}
                    <div style="text-align: right;">
                        <div style="font-size: 1.25rem; font-weight: 900; letter-spacing: 2px;" aria-label="Pencapaian {{ $cat->best_stars }} daripada 3 bintang">
                            @for($i = 1; $i <= 3; $i++)
                                @if($i <= $cat->best_stars)
                                    <span style="color: #f39c12;">⭐</span>
                                @else
                                    <span style="color: #cbd5e1;">☆</span>
                                @endif
                            @endfor
                        </div>
                        @if($cat->attempt_count > 0)
                            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700;">
                                Skor Terbaik: {{ $cat->best_score }}
                            </div>
                        @else
                            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700;">
                                Belum Cuba
                            </div>
                        @endif
                    </div>
                </div>

                <h3 style="font-size: 1.35rem; margin-bottom: var(--space-1);">Kuiz {{ $cat->name }}</h3>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: var(--space-5);">
                    5 soalan mencabar untuk menguji ingatan isyarat {{ $cat->name }}.
                </p>

                <div style="margin-top: auto; display: grid; gap: var(--space-2);">
                    <a href="{{ route('quiz.play', $cat) }}" class="btn btn-accent btn-block">
                        <i class="fa-solid fa-play" aria-hidden="true"></i> Kuiz Pilihan Jawapan
                    </a>
                    @if($cat->can_spell)
                        <a href="{{ route('quiz.play', ['category' => $cat, 'mode' => 'spelling']) }}" class="btn btn-outline btn-block">
                            <i class="fa-solid fa-spell-check" aria-hidden="true"></i> Cabaran Mengeja
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- TEACHER EXERCISES --}}
@if($exercises->isNotEmpty())
    <section aria-label="Latihan daripada Cikgu" style="margin-top: var(--space-8);">
        <h2 style="font-size: 1.55rem; margin-bottom: var(--space-4); display: flex; align-items: center; gap: 0.6rem;">
            <x-sc-icon name="guru" :size="30" />
            <span>Latihan daripada Cikgu</span>
        </h2>
        <div class="module-grid">
            @foreach($exercises as $ex)
                @php
                    $best = $exerciseBest->get($ex->id);
                    $bestStars = $best ? (int) $best->best_stars : 0;
                @endphp
                <div class="card card-hover module-card" style="border-top: 6px solid var(--role-teacher);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--space-3);">
                        <span class="role-badge teacher">{{ $ex->typeLabel() }}</span>
                        <div style="font-size: 1.2rem; font-weight: 900; letter-spacing: 2px;" aria-label="Pencapaian {{ $bestStars }} daripada 3 bintang">
                            @for($i = 1; $i <= 3; $i++)
                                @if($i <= $bestStars)
                                    <span style="color: #f39c12;">⭐</span>
                                @else
                                    <span style="color: #cbd5e1;">☆</span>
                                @endif
                            @endfor
                        </div>
                    </div>
                    <h3 style="font-size: 1.3rem; margin-bottom: var(--space-1);">{{ $ex->title }}</h3>
                    <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: var(--space-5); font-weight: 700;">
                        Oleh {{ $ex->teacher->name ?? 'Cikgu' }} &bull; {{ count($ex->items ?? []) }} soalan
                    </p>
                    <a href="{{ route('quiz.exercise', $ex) }}" class="btn btn-info btn-block" style="margin-top: auto;">
                        <i class="fa-solid fa-play" aria-hidden="true"></i> Mula Latihan
                    </a>
                </div>
            @endforeach
        </div>
    </section>
@endif
@endsection

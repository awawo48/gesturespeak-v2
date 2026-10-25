@extends('layouts.app', ['title' => 'Pusat Kuiz BIM - GestureSpeak'])

@section('content')
<div style="margin-bottom: 1.5rem;">
    <a href="{{ route('learn.hub') }}" style="color: var(--primary); font-weight: 800; font-size: 0.95rem;">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Pusat Pembelajaran
    </a>
</div>

{{-- HERO BANNER --}}
<div class="card" style="background: linear-gradient(135deg, #ffa502 0%, #ff7675 100%); color: #fff; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div style="display: flex; align-items: center; gap: 1.2rem;">
        <span style="font-size: 4rem;">🏆</span>
        <div>
            <h1 style="font-size: 2.2rem; color: #fff; margin: 0;">Cabaran Kuiz BIM</h1>
            <p style="opacity: 0.95; font-size: 1rem; margin: 0.2rem 0 0;">
                Jawab soalan dengan betul untuk kumpul sehingga 3 bintang setiap kuiz!
            </p>
        </div>
    </div>

    <div class="star-pill" style="font-size: 1.2rem; padding: 0.6rem 1.4rem;">
        ⭐ {{ $student->stars }} Bintang
    </div>
</div>

{{-- QUIZ GRID --}}
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
    @foreach($categories as $cat)
        <div class="card card-hover" style="border-top: 6px solid {{ $cat->color }}; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div style="font-size: 2.8rem; background: #f8fafc; width: 64px; height: 64px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                    {{ $cat->icon ?? '📖' }}
                </div>

                {{-- STARS BADGE --}}
                <div style="text-align: right;">
                    <div style="font-size: 1.2rem; color: #f39c12; font-weight: 900;">
                        @if($cat->best_stars > 0)
                            @for($i = 0; $i < $cat->best_stars; $i++) ⭐ @endfor
                        @else
                            <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 700;">Belum Cuba</span>
                        @endif
                    </div>
                    @if($cat->attempt_count > 0)
                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                            Skor Terbaik: {{ $cat->best_score }}/5
                        </div>
                    @endif
                </div>
            </div>

            <h3 style="font-size: 1.3rem; margin-bottom: 0.2rem;">Kuiz {{ $cat->name }}</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.2rem;">
                5 soalan mencabar untuk menguji ingatan isyarat {{ $cat->name }}.
            </p>

            <a href="{{ route('quiz.play', $cat) }}" class="btn btn-accent" style="margin-top: auto; width: 100%;">
                <i class="fa-solid fa-play"></i> Mula Kuiz Sekarang
            </a>
        </div>
    @endforeach
</div>
@endsection

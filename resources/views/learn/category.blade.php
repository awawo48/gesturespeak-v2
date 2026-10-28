@extends('layouts.app', ['title' => 'Modul: '.$category->name])

@section('content')
<div style="margin-bottom: 1.5rem;">
    <a href="{{ route('learn.hub') }}" style="color: var(--primary); font-weight: 800; font-size: 0.95rem;">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Pusat Pembelajaran
    </a>
</div>

{{-- CATEGORY HEADER BANNER --}}
<div class="card" style="background: linear-gradient(135deg, {{ $category->color }} 0%, #2d3436 180%); color: #fff; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div style="display: flex; align-items: center; gap: 1.2rem;">
        <span style="font-size: 3.5rem; background: rgba(255,255,255,0.2); width: 80px; height: 80px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
            {{ $category->icon ?? '📖' }}
        </span>
        <div>
            <h1 style="font-size: 2.2rem; color: #fff; margin: 0;">Modul {{ $category->name }}</h1>
            <p style="opacity: 0.9; margin: 0.2rem 0 0; font-size: 1rem;">
                {{ $category->description ?? 'Pelajari isyarat tangan BIM untuk modul ini.' }}
            </p>
        </div>
    </div>

    <div style="text-align: right; background: rgba(0,0,0,0.15); padding: 0.8rem 1.4rem; border-radius: var(--radius-md);">
        <div style="font-size: 0.85rem; font-weight: 800;">Kemajuan Modul</div>
        <div style="font-size: 2rem; font-weight: 900; font-family: var(--font-display);">
            {{ $percent }}%
        </div>
        <div style="font-size: 0.8rem; opacity: 0.85;">{{ $doneCount }} / {{ $signs->count() }} selesai</div>
    </div>
</div>

{{-- SIGNS GRID --}}
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1.2rem;">
    @foreach($signs as $sign)
        <a href="{{ route('learn.sign', [$category, $sign]) }}" class="card card-hover" style="text-align: center; padding: 1.5rem 1rem; border: 2px solid {{ $sign->is_done ? '#00b894' : 'var(--border-light)' }}; position: relative; background: {{ $sign->is_done ? '#f0fdf4' : '#fff' }};">

            @if($sign->is_done)
                <div style="position: absolute; top: 8px; right: 8px; background: #00b894; color: #fff; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                    <i class="fa-solid fa-check"></i>
                </div>
            @endif

            <div style="font-size: 2.5rem; font-weight: 900; font-family: var(--font-display); color: {{ $sign->is_done ? '#00b894' : 'var(--text-dark)' }}; margin-bottom: 0.3rem;">
                {{ $sign->word }}
            </div>

            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.8rem;">
                {{ $sign->meaning_en ?? $sign->word }}
            </div>

            <div style="display: flex; justify-content: center; gap: 0.4rem;">
                @if($sign->video)
                    <span title="Video Demonstrasi Tersedia" style="font-size: 0.8rem; background: #e0f2fe; color: #0284c7; padding: 0.2rem 0.5rem; border-radius: var(--radius-full);">
                        <i class="fa-solid fa-play"></i> Video
                    </span>
                @endif
                <span title="Boleh Dilatih Guna Kamera AI" style="font-size: 0.8rem; background: #fef3c7; color: #d97706; padding: 0.2rem 0.5rem; border-radius: var(--radius-full);">
                    <i class="fa-solid fa-camera"></i> AI
                </span>
            </div>
        </a>
    @endforeach
</div>
@endsection

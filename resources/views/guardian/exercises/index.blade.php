@extends('layouts.app', ['title' => 'Latihan Kelas - Portal Guru'])

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <a href="{{ route('guardian.dashboard') }}" style="color: #0984e3; font-weight: 800; font-size: 0.9rem;">
            <i class="fa-solid fa-arrow-left"></i> Portal Kelas
        </a>
        <h1 style="font-size: 1.8rem; margin: 0.4rem 0 0;">📝 Latihan &amp; Kuiz Kelas</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">Cipta latihan pilihan jawapan atau ejaan khas untuk murid anda.</p>
    </div>
    <a href="{{ route('guardian.exercises.create') }}" class="btn btn-primary" style="background: #0984e3;">
        <i class="fa-solid fa-plus"></i> Cipta Latihan Baharu
    </a>
</div>

@if($exercises->isEmpty())
    <div class="card" style="text-align: center; padding: 3rem;">
        <div style="font-size: 4rem;">📝</div>
        <h3>Belum ada latihan</h3>
        <p style="color: var(--text-muted);">Mulakan dengan mencipta latihan pertama untuk kelas anda.</p>
    </div>
@else
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.2rem;">
        @foreach($exercises as $ex)
            <div class="card" style="border-top: 5px solid {{ $ex->is_active ? '#0984e3' : '#b2bec3' }};">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
                    <span class="role-badge teacher">{{ $ex->typeLabel() }}</span>
                    @unless($ex->is_active)<span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 800;">DISEMBUNYIKAN</span>@endunless
                </div>
                <h3 style="font-size: 1.2rem; margin-bottom: 0.2rem;">{{ $ex->title }}</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                    {{ count($ex->items ?? []) }} soalan &bull; {{ $ex->attempts_count }} percubaan
                    @if($ex->attempts_count) &bull; Purata ⭐ {{ number_format($ex->attempts_avg_stars, 1) }} @endif
                </p>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="{{ route('guardian.exercises.show', $ex) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-chart-simple"></i> Keputusan</a>
                    <form action="{{ route('guardian.exercises.toggle', $ex) }}" method="POST" style="margin: 0;">
                        @csrf @method('PATCH')
                        <button class="btn btn-outline btn-sm" type="submit">
                            <i class="fa-solid {{ $ex->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i> {{ $ex->is_active ? 'Sembunyi' : 'Aktifkan' }}
                        </button>
                    </form>
                    <form action="{{ route('guardian.exercises.destroy', $ex) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Padam latihan ini beserta rekod keputusannya?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline btn-sm" type="submit" style="color: var(--danger); border-color: var(--danger);"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection


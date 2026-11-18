@extends('layouts.app', ['title' => $exercise->title.' - Keputusan'])

@section('content')
<a href="{{ route('guardian.exercises.index') }}" style="color: #0984e3; font-weight: 800; font-size: 0.9rem;">
    <i class="fa-solid fa-arrow-left"></i> Senarai Latihan
</a>
<h1 style="font-size: 1.8rem; margin: 0.4rem 0 0.2rem;">{{ $exercise->title }}</h1>
<p style="color: var(--text-muted); margin-bottom: 1.2rem;">
    <span class="role-badge teacher">{{ $exercise->typeLabel() }}</span>
    &bull; {{ count($words) }} soalan: {{ implode(', ', $words) }}
</p>

<div class="card" style="padding: 0; overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 2px solid var(--border-light); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                <th style="padding: 1rem 1.2rem;">Murid</th>
                <th style="padding: 1rem 1.2rem;">Skor</th>
                <th style="padding: 1rem 1.2rem;">Bintang</th>
                <th style="padding: 1rem 1.2rem;">Masa</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attempts as $a)
                <tr style="border-bottom: 1px solid var(--border-light);">
                    <td style="padding: 0.9rem 1.2rem; font-weight: 800;">{{ $a->student?->avatarEmoji() }} {{ $a->student?->name ?? '-' }}</td>
                    <td style="padding: 0.9rem 1.2rem;">{{ $a->score }}/{{ $a->total }}</td>
                    <td style="padding: 0.9rem 1.2rem;">{{ str_repeat('⭐', $a->stars) ?: '-' }}</td>
                    <td style="padding: 0.9rem 1.2rem; color: var(--text-muted);">{{ $a->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">Belum ada murid yang mencuba latihan ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection


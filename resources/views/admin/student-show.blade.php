@extends('layouts.app', ['title' => $student->name.' - Butiran Murid'])

@section('content')
<a href="{{ route('admin.students') }}" style="color: var(--primary); font-weight: 800; font-size: 0.9rem;">
    <i class="fa-solid fa-arrow-left"></i> Senarai Murid
</a>

{{-- PROFILE HEADER --}}
<div class="card" style="margin: 0.8rem 0 1.5rem; display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; justify-content: space-between;">
    <div style="display: flex; gap: 1.2rem; align-items: center;">
        <div style="font-size: 4rem;">{{ $student->avatarEmoji() }}</div>
        <div>
            <h1 style="font-size: 1.8rem; margin: 0;">{{ $student->name }}</h1>
            <div style="color: var(--text-muted); font-size: 0.9rem;">
                {{ $student->age ? $student->age.' tahun' : 'Umur tidak dinyatakan' }} &bull; Didaftar {{ $student->created_at->format('d M Y') }}
                @if($student->creator) &bull; oleh {{ $student->creator->name }} @endif
            </div>
            <div style="margin-top: 0.4rem;">
                <strong style="color: var(--primary); font-family: monospace; font-size: 1.1rem;">{{ $student->student_code }}</strong>
                <span class="star-pill" style="margin-left: 0.6rem;">⭐ {{ $student->stars }}</span>
            </div>
        </div>
    </div>
    <form action="{{ route('admin.students.delete', $student) }}" method="POST" onsubmit="return confirm('Padam profil murid ini? Semua rekod kemajuan akan turut dipadam.')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger);"><i class="fa-solid fa-trash"></i> Padam Murid</button>
    </form>
</div>

{{-- STATS --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    @foreach([
        ['Penguasaan BIM', $summary['overall'].'%', '🎯'],
        ['Isyarat Dikuasai', $stats['mastered'], '🏅'],
        ['Jumlah Latihan', $stats['practices'], '🖐️'],
        ['Kuiz Dimainkan', $stats['quizzes'], '🏆'],
    ] as [$label, $value, $icon])
        <div class="card" style="text-align: center;">
            <div style="font-size: 1.8rem;">{{ $icon }}</div>
            <div style="font-size: 1.8rem; font-weight: 900; color: var(--primary);">{{ $value }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $label }}</div>
        </div>
    @endforeach
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    {{-- GUARDIANS + TEACHER NOTE --}}
    <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 0.8rem;">👪 Penjaga &amp; Guru Dipautkan</h3>
        @forelse($student->guardians as $g)
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--border-light);">
                <div><strong>{{ $g->name }}</strong><div style="font-size: 0.8rem; color: var(--text-muted);">{{ $g->email }}</div></div>
                <span class="role-badge {{ $g->role }}">{{ $g->pivot->relationship }}</span>
            </div>
        @empty
            <p style="color: var(--text-muted);">Tiada penjaga dipautkan.</p>
        @endforelse

        <h3 style="font-size: 1.1rem; margin: 1.2rem 0 0.5rem;">📝 Ulasan Guru</h3>
        @if($student->teacher_note)
            <div style="background: #f8fbff; border-left: 4px solid #0984e3; padding: 0.8rem 1rem; border-radius: 8px;">
                {{ $student->teacher_note }}
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.4rem;">
                    {{ $student->teacherReviewer?->name }} &bull; {{ $student->teacher_note_updated_at?->diffForHumans() }}
                </div>
            </div>
        @else
            <p style="color: var(--text-muted);">Belum ada ulasan.</p>
        @endif
        @if($student->assignedCategory)
            <p style="margin-top: 0.6rem;">📚 Tugasan minggu ini: <strong>{{ $student->assignedCategory->icon }} {{ $student->assignedCategory->name }}</strong></p>
        @endif
    </div>

    {{-- CATEGORY PROGRESS --}}
    <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 0.8rem;">📊 Kemajuan Mengikut Kategori</h3>
        @foreach($summary['categories'] as $row)
            <div style="margin-bottom: 0.8rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; font-weight: 700;">
                    <span>{{ $row['icon'] }} {{ $row['name'] }}</span>
                    <span>{{ $row['done'] }}/{{ $row['total'] }} ({{ $row['percent'] }}%)</span>
                </div>
                <div style="height: 10px; background: #edf0f7; border-radius: 99px; overflow: hidden;">
                    <div style="height: 100%; width: {{ $row['percent'] }}%; background: {{ $row['color'] }};"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    {{-- QUIZ HISTORY --}}
    <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 0.8rem;">🏆 Sejarah Kuiz</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            @forelse($attempts as $a)
                <tr style="border-bottom: 1px solid var(--border-light);">
                    <td style="padding: 0.5rem 0;">{{ $a->category->icon ?? '' }} {{ $a->category->name ?? '-' }}
                        <span style="font-size: 0.75rem; color: var(--text-muted);">({{ ($a->mode ?? 'choice') === 'spelling' ? 'Mengeja' : 'Pilihan' }})</span></td>
                    <td>{{ $a->score }}/{{ $a->total }}</td>
                    <td>{{ str_repeat('⭐', $a->stars) ?: '-' }}</td>
                    <td style="color: var(--text-muted); font-size: 0.8rem;">{{ $a->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td style="color: var(--text-muted); padding: 0.5rem 0;">Belum ada kuiz.</td></tr>
            @endforelse
        </table>

        @if($exerciseAttempts->isNotEmpty())
            <h3 style="font-size: 1.05rem; margin: 1.2rem 0 0.5rem;">📝 Latihan Guru</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                @foreach($exerciseAttempts as $a)
                    <tr style="border-bottom: 1px solid var(--border-light);">
                        <td style="padding: 0.5rem 0;">{{ $a->exercise->title ?? '-' }}</td>
                        <td>{{ $a->score }}/{{ $a->total }}</td>
                        <td>{{ str_repeat('⭐', $a->stars) ?: '-' }}</td>
                        <td style="color: var(--text-muted); font-size: 0.8rem;">{{ $a->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    {{-- RECENT + NEEDS PRACTICE --}}
    <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 0.8rem;">🕒 Aktiviti Terkini</h3>
        @forelse($recent as $p)
            <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid var(--border-light); font-size: 0.9rem;">
                <span>{{ $p->sign->word ?? '-' }} <span style="color: var(--text-muted); font-size: 0.78rem;">({{ $p->sign->category->name ?? '' }})</span></span>
                <span>{{ $p->mastered ? '🏅 Dikuasai' : $p->practice_count.'× latihan' }}</span>
            </div>
        @empty
            <p style="color: var(--text-muted);">Belum ada aktiviti.</p>
        @endforelse

        <h3 style="font-size: 1.1rem; margin: 1.2rem 0 0.5rem;">⚠️ Perlu Latihan Tambahan</h3>
        @forelse($needsPractice as $p)
            <div style="display: flex; justify-content: space-between; padding: 0.3rem 0; font-size: 0.9rem;">
                <span>{{ $p->sign->word ?? '-' }}</span>
                <span style="color: var(--text-muted);">{{ $p->practice_count }}× &bull; terbaik {{ round($p->best_confidence) }}%</span>
            </div>
        @empty
            <p style="color: var(--text-muted);">Tiada isyarat yang memerlukan perhatian khas.</p>
        @endforelse
    </div>
</div>
@endsection


@extends('layouts.admin', ['title' => $student->name.' — Profil murid'])

@section('content')
<div style="margin-bottom: 12px;">
    <a href="{{ route('admin.students') }}" style="color: var(--adm-text-muted); font-weight: 500; font-size: 0.84rem; text-decoration: none;">
        &larr; Senarai murid
    </a>
</div>

<div class="adm-page-head" style="margin-bottom: 16px;">
    <div>
        <h1>{{ $student->name }}</h1>
        <p class="adm-page-sub">
            <span class="adm-mono">{{ $student->student_code }}</span>
            &middot; {{ $student->age ? $student->age.' tahun' : 'Umur tidak dinyatakan' }}
            &middot; Didaftar {{ $student->created_at->format('d M Y') }}
            @if($student->creator) &middot; oleh {{ $student->creator->name }} @endif
        </p>
    </div>
    <form action="{{ route('admin.students.delete', $student) }}" method="POST" onsubmit="return confirm('Padam profil murid ini? Semua rekod kemajuan akan turut dipadam.')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--adm-neg);">
            Padam murid
        </button>
    </form>
</div>

<div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); background: var(--adm-surface); border: 1px solid var(--adm-hairline-strong); border-radius: 6px; box-shadow: 0 2px 0 rgba(22, 50, 79, 0.05); margin-bottom: 20px;">
    <div style="padding: 16px 20px; border-inline-end: 1px solid var(--adm-hairline);">
        <div style="font-size: 0.8rem; color: var(--adm-text-muted);">Penguasaan BIM</div>
        <div class="adm-mono" style="font-size: 2rem; font-weight: 500; line-height: 1.08; margin-top: 8px;">{{ $summary['overall'] }}%</div>
    </div>
    <div style="padding: 16px 20px; border-inline-end: 1px solid var(--adm-hairline);">
        <div style="font-size: 0.8rem; color: var(--adm-text-muted);">Isyarat dikuasai</div>
        <div class="adm-mono" style="font-size: 2rem; font-weight: 500; line-height: 1.08; margin-top: 8px;">{{ $stats['mastered'] }}</div>
    </div>
    <div style="padding: 16px 20px; border-inline-end: 1px solid var(--adm-hairline);">
        <div style="font-size: 0.8rem; color: var(--adm-text-muted);">Jumlah latihan</div>
        <div class="adm-mono" style="font-size: 2rem; font-weight: 500; line-height: 1.08; margin-top: 8px;">{{ $stats['practices'] }}</div>
    </div>
    <div style="padding: 16px 20px;">
        <div style="font-size: 0.8rem; color: var(--adm-text-muted);">Bintang dikumpul</div>
        <div class="adm-mono" style="font-size: 2rem; font-weight: 500; line-height: 1.08; margin-top: 8px;">{{ $student->stars }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 20px;">
    <div class="adm-card">
        <div class="adm-card-head">
            <h2 class="adm-card-title">Penjaga &amp; guru dipautkan</h2>
        </div>
        @forelse($student->guardians as $g)
            <div class="adm-list-row">
                <div>
                    <div style="font-weight: 500; color: var(--adm-ink); font-size: 0.875rem;">{{ $g->name }}</div>
                    <div style="font-size: 0.78rem; color: var(--adm-text-muted);">{{ $g->email }}</div>
                </div>
                <span style="font-size: 0.8rem; color: var(--adm-text-secondary);">{{ $g->pivot->relationship }}</span>
            </div>
        @empty
            <p style="color: var(--adm-text-muted); font-size: 0.85rem; margin: 0;">Tiada penjaga dipautkan</p>
        @endforelse

        <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--adm-ink); margin: 20px 0 8px;">Ulasan guru</h3>
        @if($student->teacher_note)
            <div style="background: var(--adm-canvas); border: 1px solid var(--adm-hairline); padding: 12px 16px; border-radius: 4px; font-size: 0.85rem;">
                {{ $student->teacher_note }}
                <div style="font-size: 0.75rem; color: var(--adm-text-muted); margin-top: 4px;">
                    {{ $student->teacherReviewer?->name }} &middot; {{ $student->teacher_note_updated_at?->diffForHumans() }}
                </div>
            </div>
        @else
            <p style="color: var(--adm-text-muted); font-size: 0.84rem; margin: 0;">Belum ada ulasan</p>
        @endif
        @if($student->assignedCategory)
            <p style="margin: 12px 0 0; font-size: 0.84rem; color: var(--adm-text-muted);">
                Tugasan minggu ini: <span style="color: var(--adm-ink); font-weight: 500;">{{ $student->assignedCategory->name }}</span>
            </p>
        @endif
    </div>

    <div class="adm-card">
        <div class="adm-card-head">
            <h2 class="adm-card-title">Kemajuan mengikut modul</h2>
        </div>
        @foreach($summary['categories'] as $row)
            <div style="margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 4px;">
                    <span style="color: var(--adm-ink); font-weight: 500;">{{ $row['name'] }}</span>
                    <span class="adm-mono" style="color: var(--adm-text-muted);">{{ $row['done'] }}/{{ $row['total'] }} ({{ $row['percent'] }}%)</span>
                </div>
                <div class="adm-pixel-track">
                    <div class="adm-pixel-fill" style="width: {{ $row['percent'] }}%;"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
    <div class="adm-card">
        <div class="adm-card-head">
            <h2 class="adm-card-title">Sejarah kuiz</h2>
        </div>
        @forelse($attempts as $a)
            <div class="adm-list-row">
                <div>
                    <div style="font-weight: 500; color: var(--adm-ink); font-size: 0.875rem;">
                        {{ $a->category->name ?? '-' }}
                        <span style="font-weight: 400; color: var(--adm-text-muted); font-size: 0.78rem;">({{ ($a->mode ?? 'choice') === 'spelling' ? 'Mengeja' : 'Pilihan' }})</span>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--adm-text-muted);">{{ $a->created_at->diffForHumans() }}</div>
                </div>
                <div class="adm-mono" style="color: var(--adm-ink); white-space: nowrap;">
                    {{ $a->score }}/{{ $a->total }}
                </div>
            </div>
        @empty
            <p style="color: var(--adm-text-muted); font-size: 0.84rem; margin: 0;">Belum ada sejarah kuiz</p>
        @endforelse
    </div>

    <div class="adm-card">
        <div class="adm-card-head">
            <h2 class="adm-card-title">Aktiviti terkini</h2>
        </div>
        @forelse($recent as $p)
            <div class="adm-list-row">
                <div>
                    <span style="font-weight: 500; color: var(--adm-ink); font-size: 0.875rem;">{{ $p->sign->word ?? '-' }}</span>
                    <span style="color: var(--adm-text-muted); font-size: 0.78rem;">({{ $p->sign->category->name ?? '' }})</span>
                </div>
                <span class="adm-status">
                    <span class="adm-dot {{ $p->mastered ? 'adm-dot--ok' : 'adm-dot--muted' }}"></span>
                    {{ $p->mastered ? 'Dikuasai' : $p->practice_count.'x latihan' }}
                </span>
            </div>
        @empty
            <p style="color: var(--adm-text-muted); font-size: 0.84rem; margin: 0;">Belum ada aktiviti</p>
        @endforelse
    </div>
</div>
@endsection

@extends('layouts.app', ['title' => 'Pengurusan Murid - Admin'])

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.8rem; margin: 0;">Pengurusan Profil Murid</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Senarai semua kanak-kanak yang berdaftar di platform</p>
    </div>

    {{-- SEARCH FORM --}}
    <form method="GET" action="{{ route('admin.students') }}" style="display: flex; gap: 0.5rem;">
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            class="form-input"
            placeholder="Cari nama atau Kod Murid..."
            style="width: 250px; padding: 0.5rem 0.8rem; font-size: 0.9rem;"
        >
        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
    </form>
</div>

<div class="card" style="padding: 0; overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 2px solid var(--border-light); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                <th style="padding: 1rem 1.2rem;">Murid</th>
                <th style="padding: 1rem 1.2rem;">Kod Murid</th>
                <th style="padding: 1rem 1.2rem;">Bintang ⭐</th>
                <th style="padding: 1rem 1.2rem;">Penjaga / Guru Berkongsi</th>
                <th style="padding: 1rem 1.2rem;">Kuiz</th>
                <th style="padding: 1rem 1.2rem; text-align: right;">Tindakan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $st)
                <tr style="border-bottom: 1px solid var(--border-light);">
                    <td style="padding: 1rem 1.2rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span style="font-size: 1.8rem;">{{ $st->avatarEmoji() }}</span>
                            <div>
                                <div style="font-weight: 800;">{{ $st->name }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">
                                    {{ $st->age ? $st->age.' tahun' : '-' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 1rem 1.2rem;">
                        <strong style="color: var(--primary); font-family: monospace; font-size: 1.05rem;">
                            {{ $st->student_code }}
                        </strong>
                    </td>
                    <td style="padding: 1rem 1.2rem;">
                        <span class="star-pill" style="font-size: 0.85rem; padding: 0.2rem 0.6rem;">
                            ⭐ {{ $st->stars }}
                        </span>
                    </td>
                    <td style="padding: 1rem 1.2rem;">
                        @foreach($st->guardians as $g)
                            <span class="role-badge {{ $g->role }}" style="margin-right: 0.3rem;">
                                {{ $g->name }} ({{ $g->pivot->relationship }})
                            </span>
                        @endforeach
                    </td>
                    <td style="padding: 1rem 1.2rem;">
                        {{ $st->quiz_attempts_count }} kali
                    </td>
                    <td style="padding: 1rem 1.2rem; text-align: right;">
                        <form action="{{ route('admin.students.delete', $st) }}" method="POST" onsubmit="return confirm('Adakah anda pasti mahu memadam profil murid ini? Semua rekod kemajuan akan turut dipadam.')" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger); padding: 0.3rem 0.6rem;">
                                <i class="fa-solid fa-trash"></i> Padam
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        Tiada rekod murid dijumpai.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 1.5rem;">
    {{ $students->links() }}
</div>
@endsection

@extends('layouts.admin', ['title' => 'Profil murid — Sign Cilik'])

@section('content')
<div class="adm-page-head">
    <div>
        <h1>Profil murid</h1>
        <p class="adm-page-sub">Senarai kanak-kanak berdaftar, kod log masuk dan kemajuan bintang</p>
    </div>

    <form method="GET" action="{{ route('admin.students') }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            class="form-input"
            placeholder="Cari nama atau kod murid"
            style="width: 240px;"
        >
        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
    </form>
</div>

<div class="adm-card adm-card-flush" tabindex="0" role="region" aria-label="Senarai profil murid">
    <table class="adm-table">
        <thead>
            <tr>
                <th>Murid</th>
                <th>Kod murid</th>
                <th style="text-align: right;">Bintang</th>
                <th>Penjaga / guru</th>
                <th style="text-align: right;">Kuiz</th>
                <th style="text-align: right;">Tindakan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $st)
                <tr>
                    <td>
                        <a href="{{ route('admin.students.show', $st) }}" style="font-weight: 500; color: var(--adm-ink); text-decoration: none;">
                            {{ $st->name }}
                        </a>
                        <div style="font-size: 0.78rem; color: var(--adm-text-muted);">
                            {{ $st->age ? $st->age.' tahun' : 'Umur belum diisi' }}
                        </div>
                    </td>
                    <td>
                        <span class="adm-mono" style="color: var(--adm-ink);">{{ $st->student_code }}</span>
                    </td>
                    <td class="adm-mono" style="text-align: right; color: var(--adm-ink);">
                        {{ $st->stars }}
                    </td>
                    <td style="font-size: 0.8125rem; color: var(--adm-text-secondary);">
                        @if($st->guardians->isNotEmpty())
                            {{ $st->guardians->map(fn ($g) => $g->name.' ('.$g->pivot->relationship.')')->implode(', ') }}
                        @else
                            <span style="color: var(--adm-text-muted);">Tiada</span>
                        @endif
                    </td>
                    <td class="adm-mono" style="text-align: right; color: var(--adm-text-secondary);">
                        {{ $st->quiz_attempts_count }}
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <a href="{{ route('admin.students.show', $st) }}" class="adm-row-action">Butiran</a>
                        <form action="{{ route('admin.students.delete', $st) }}" method="POST" onsubmit="return confirm('Padam profil murid ini? Semua rekod kemajuan akan turut dipadam.')" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="adm-row-action adm-row-action--danger">Padam</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--adm-text-muted);">
                        Tiada rekod murid dijumpai
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 20px;">
    {{ $students->links() }}
</div>
@endsection

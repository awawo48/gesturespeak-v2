@extends('layouts.admin', ['title' => 'Pengguna & guru — Sign Cilik'])

@section('content')
<div class="adm-page-head">
    <div>
        <h1>Pengguna &amp; guru</h1>
        <p class="adm-page-sub">Senarai akaun ibu bapa dan guru berdaftar serta kelulusan guru baharu</p>
    </div>
</div>

@if($pendingCount > 0)
    <div class="adm-notice" role="status">
        <span><strong>{{ $pendingCount }} akaun guru</strong> sedang menunggu kelulusan anda</span>
        <a href="{{ route('admin.users', ['role' => 'teacher', 'status' => 'pending']) }}" class="btn btn-outline btn-sm">
            Lihat guru menunggu
        </a>
    </div>
@endif

<div class="adm-filter-bar">
    <nav class="adm-tabs" aria-label="Tapis pengguna">
        <a href="{{ route('admin.users', ['search' => $search]) }}" class="adm-tab {{ $role === '' && $status === '' ? 'active' : '' }}" @if($role === '' && $status === '') aria-current="page" @endif>Semua</a>
        <a href="{{ route('admin.users', ['role' => 'teacher', 'status' => 'pending', 'search' => $search]) }}" class="adm-tab {{ $status === 'pending' ? 'active' : '' }}" @if($status === 'pending') aria-current="page" @endif>
            Menunggu ({{ $pendingCount }})
        </a>
        <a href="{{ route('admin.users', ['role' => 'teacher', 'search' => $search]) }}" class="adm-tab {{ $role === 'teacher' && $status === '' ? 'active' : '' }}" @if($role === 'teacher' && $status === '') aria-current="page" @endif>Guru</a>
        <a href="{{ route('admin.users', ['role' => 'parent', 'search' => $search]) }}" class="adm-tab {{ $role === 'parent' ? 'active' : '' }}" @if($role === 'parent') aria-current="page" @endif>Ibu bapa</a>
    </nav>

    <form method="GET" action="{{ route('admin.users') }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
        <input type="hidden" name="role" value="{{ $role }}">
        <input type="hidden" name="status" value="{{ $status }}">
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            class="form-input"
            placeholder="Cari nama atau emel"
            style="width: 220px;"
        >
        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
    </form>
</div>

<div class="adm-card adm-card-flush" tabindex="0" role="region" aria-label="Senarai pengguna dan guru">
    <table class="adm-table">
        <thead>
            <tr>
                <th>Nama &amp; emel</th>
                <th>Peranan</th>
                <th>Status</th>
                <th style="text-align: right;">Murid</th>
                <th>Tarikh daftar</th>
                <th style="text-align: right;">Tindakan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td>
                        <div style="font-weight: 500; color: var(--adm-ink);">{{ $user->name }}</div>
                        <div style="font-size: 0.78rem; color: var(--adm-text-muted);">{{ $user->email }}</div>
                    </td>
                    <td style="color: var(--adm-text-secondary);">
                        {{ $user->isTeacher() ? 'Guru' : 'Ibu bapa' }}
                    </td>
                    <td>
                        @if($user->isPending())
                            <span class="adm-status"><span class="adm-dot adm-dot--warn"></span>Menunggu</span>
                        @elseif($user->isRejected())
                            <span class="adm-status"><span class="adm-dot adm-dot--danger"></span>Ditolak</span>
                        @else
                            <span class="adm-status"><span class="adm-dot adm-dot--ok"></span>Diluluskan</span>
                        @endif
                    </td>
                    <td class="adm-mono" style="text-align: right; color: var(--adm-ink);">
                        {{ $user->students_count }}
                    </td>
                    <td class="adm-mono" style="color: var(--adm-text-muted); font-size: 0.78rem;">
                        {{ $user->created_at->format('d/m/Y') }}
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        @if(! $user->isApproved())
                            <form action="{{ route('admin.users.approve', $user) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="adm-row-action adm-row-action--ok">Luluskan</button>
                            </form>
                        @endif
                        @if($user->isTeacher() && ! $user->isRejected())
                            <form action="{{ route('admin.users.reject', $user) }}" method="POST" onsubmit="return confirm('Tolak atau gantung akaun guru ini?')" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="adm-row-action">Tolak</button>
                            </form>
                        @endif
                        <form action="{{ route('admin.users.delete', $user) }}" method="POST" onsubmit="return confirm('Adakah anda pasti mahu memadam pengguna ini?')" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="adm-row-action adm-row-action--danger">Padam</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--adm-text-muted);">
                        Tiada pengguna dijumpai
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 20px;">
    {{ $users->links() }}
</div>
@endsection

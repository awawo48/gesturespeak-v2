@extends('layouts.app', ['title' => 'Pengurusan Pengguna - Admin'])

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.8rem; margin: 0;">Pengurusan Pengguna Dewasa</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Senarai akaun Ibu Bapa & Guru berdaftar dalam sistem</p>
    </div>

    {{-- SEARCH & FILTER FORM --}}
    <form method="GET" action="{{ route('admin.users') }}" style="display: flex; gap: 0.5rem;">
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            class="form-input"
            placeholder="Cari nama atau emel..."
            style="width: 220px; padding: 0.5rem 0.8rem; font-size: 0.9rem;"
        >
        <select name="role" class="form-select" style="width: 140px; padding: 0.5rem 0.8rem; font-size: 0.9rem;">
            <option value="">Semua Peranan</option>
            <option value="parent" {{ $role === 'parent' ? 'selected' : '' }}>Ibu Bapa</option>
            <option value="teacher" {{ $role === 'teacher' ? 'selected' : '' }}>Guru</option>
        </select>
        <select name="status" class="form-select" style="width: 150px; padding: 0.5rem 0.8rem; font-size: 0.9rem;">
            <option value="">Semua Status</option>
            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Menunggu</option>
            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Diluluskan</option>
            <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Ditolak</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Tapis</button>
    </form>
</div>

@if($pendingCount > 0)
    <div class="alert" style="background: #fff7e6; border: 1px solid #ffd591; color: #ad6800; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.8rem; margin-bottom: 1.2rem;">
        <div>
            <i class="fa-solid fa-hourglass-half"></i>
            <strong>{{ $pendingCount }} akaun guru</strong> sedang menunggu kelulusan anda.
        </div>
        <a href="{{ route('admin.users', ['role' => 'teacher', 'status' => 'pending']) }}" class="btn btn-sm" style="background: #fa8c16; color: #fff;">
            Lihat Guru Menunggu
        </a>
    </div>
@endif

<div class="card" style="padding: 0; overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 2px solid var(--border-light); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                <th style="padding: 1rem 1.2rem;">Nama & Emel</th>
                <th style="padding: 1rem 1.2rem;">Peranan</th>
                <th style="padding: 1rem 1.2rem;">Status</th>
                <th style="padding: 1rem 1.2rem;">Murid Dipautkan</th>
                <th style="padding: 1rem 1.2rem;">Tarikh Daftar</th>
                <th style="padding: 1rem 1.2rem; text-align: right;">Tindakan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr style="border-bottom: 1px solid var(--border-light);">
                    <td style="padding: 1rem 1.2rem;">
                        <div style="font-weight: 800;">{{ $user->name }}</div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">{{ $user->email }}</div>
                    </td>
                    <td style="padding: 1rem 1.2rem;">
                        <span class="role-badge {{ $user->role }}">{{ $user->isTeacher() ? 'Guru' : 'Ibu Bapa' }}</span>
                    </td>
                    <td style="padding: 1rem 1.2rem;">
                        @if($user->isPending())
                            <span style="background: #fff7e6; color: #d46b08; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.8rem; font-weight: 800;">⏳ Menunggu</span>
                        @elseif($user->isRejected())
                            <span style="background: #fff1f0; color: #cf1322; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.8rem; font-weight: 800;">✖ Ditolak</span>
                        @else
                            <span style="background: #f6ffed; color: #389e0d; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.8rem; font-weight: 800;">✔ Diluluskan</span>
                        @endif
                    </td>
                    <td style="padding: 1rem 1.2rem;">
                        <strong>{{ $user->students_count }}</strong> murid
                    </td>
                    <td style="padding: 1rem 1.2rem; color: var(--text-muted); font-size: 0.85rem;">
                        {{ $user->created_at->format('d/m/Y') }}
                    </td>
                    <td style="padding: 1rem 1.2rem; text-align: right; white-space: nowrap;">
                        @if(! $user->isApproved())
                            <form action="{{ route('admin.users.approve', $user) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm" style="background: var(--success); color: #fff; padding: 0.3rem 0.6rem;">
                                    <i class="fa-solid fa-check"></i> Luluskan
                                </button>
                            </form>
                        @endif
                        @if($user->isTeacher() && ! $user->isRejected())
                            <form action="{{ route('admin.users.reject', $user) }}" method="POST" onsubmit="return confirm('Tolak / gantung akaun guru ini?')" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline btn-sm" style="color: #d46b08; border-color: #d46b08; padding: 0.3rem 0.6rem;">
                                    <i class="fa-solid fa-ban"></i> Tolak
                                </button>
                            </form>
                        @endif
                        <form action="{{ route('admin.users.delete', $user) }}" method="POST" onsubmit="return confirm('Adakah anda pasti mahu memadam pengguna ini?')" style="display: inline;">
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
                        Tiada pengguna dijumpai.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 1.5rem;">
    {{ $users->links() }}
</div>
@endsection

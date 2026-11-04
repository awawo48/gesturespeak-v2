@extends('layouts.app', ['title' => 'Menunggu Kelulusan - GestureSpeak'])

@section('content')
<div style="max-width: 560px; margin: 2rem auto;">
    <div class="card" style="text-align: center; padding: 2.5rem 2rem; border-top: 6px solid {{ $user->isRejected() ? 'var(--danger)' : '#fa8c16' }};">
        @if($user->isRejected())
            <div style="font-size: 3.5rem; margin-bottom: 0.8rem;">🚫</div>
            <h1 style="font-size: 1.7rem; margin-bottom: 0.5rem;">Permohonan Akaun Guru Ditolak</h1>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                Maaf {{ $user->name }}, permohonan akaun guru anda tidak diluluskan oleh Admin.
                Sila hubungi pentadbir sekolah / sistem jika anda rasa ini satu kesilapan.
            </p>
        @else
            <div style="font-size: 3.5rem; margin-bottom: 0.8rem;">⏳👩‍🏫</div>
            <h1 style="font-size: 1.7rem; margin-bottom: 0.5rem;">Akaun Guru Menunggu Kelulusan</h1>
            <p style="color: var(--text-muted); margin-bottom: 1.2rem;">
                Terima kasih {{ $user->name }}! Akaun anda (<strong>{{ $user->email }}</strong>) telah didaftarkan.
                Admin perlu mengesahkan bahawa anda ialah guru sebelum Portal Guru dibuka.
            </p>
            <div style="background: #fff7e6; border-radius: var(--radius-md); padding: 1rem; text-align: left; font-size: 0.9rem; color: #ad6800; margin-bottom: 1.5rem;">
                <strong>Selepas diluluskan, anda boleh:</strong>
                <ul style="margin: 0.5rem 0 0 1.2rem;">
                    <li>Mendaftar &amp; memautkan murid kelas anda</li>
                    <li>Melihat analitik prestasi kelas</li>
                    <li>Menulis ulasan &amp; menetapkan kerja rumah BIM untuk ibu bapa</li>
                </ul>
            </div>
            <a href="{{ route('guardian.pending') }}" class="btn btn-primary" style="margin-bottom: 0.8rem;">
                <i class="fa-solid fa-rotate"></i> Semak Status Semula
            </a>
        @endif

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Log Keluar
            </button>
        </form>
    </div>
</div>
@endsection


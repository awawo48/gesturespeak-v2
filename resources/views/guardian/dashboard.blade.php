@extends('layouts.app', ['title' => 'Dashboard Ibu Bapa & Guru - GestureSpeak'])

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.8rem;">
            <h1 style="font-size: 2rem; margin: 0;">Selamat Kembali, {{ Auth::user()->name }}!</h1>
            <span class="role-badge {{ Auth::user()->role }}">
                {{ Auth::user()->isTeacher() ? 'Guru Sekolah' : 'Ibu Bapa / Penjaga' }}
            </span>
        </div>
        <p style="color: var(--text-muted); margin-top: 0.3rem;">
            Uruskan profil murid, pautkan kod murid antara rumah & sekolah, dan pantau kemajuan pembelajaran BIM.
        </p>
    </div>

    <div style="display: flex; gap: 0.8rem;">
        <button class="btn btn-primary" onclick="document.getElementById('modal-new-child').style.display='flex'">
            <i class="fa-solid fa-plus"></i> Tambah Murid Baru
        </button>
        <button class="btn btn-outline" onclick="document.getElementById('modal-link-child').style.display='flex'">
            <i class="fa-solid fa-link"></i> Pautkan Kod Murid
        </button>
    </div>
</div>

{{-- SECTION: LIST OF CHILDREN --}}
@if($children->isEmpty())
    <div class="card" style="text-align: center; padding: 3.5rem 2rem; border: 2px dashed var(--border-light);">
        <div style="font-size: 4rem; margin-bottom: 1rem;">👨‍👩‍👧‍👦</div>
        <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Belum Ada Murid Dipautkan</h3>
        <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem;">
            Anda boleh <strong>cipta profil murid baru</strong> untuk anak anda, atau <strong>masukkan Kod Murid</strong> yang diberikan oleh {{ Auth::user()->isTeacher() ? 'ibu bapa murid' : 'guru sekolah' }}.
        </p>
        <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
            <button class="btn btn-primary" onclick="document.getElementById('modal-new-child').style.display='flex'">
                <i class="fa-solid fa-plus"></i> Tambah Murid Baru
            </button>
            <button class="btn btn-outline" onclick="document.getElementById('modal-link-child').style.display='flex'">
                <i class="fa-solid fa-link"></i> Masukkan Kod Murid Sedia Ada
            </button>
        </div>
    </div>
@else
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        @foreach($children as $child)
            <div class="card card-hover" style="border-top: 5px solid var(--primary); display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                        <span style="font-size: 3rem; background: #f0edff; width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            {{ $child->avatarEmoji() }}
                        </span>
                        <div>
                            <h3 style="font-size: 1.3rem; margin: 0;">{{ $child->name }}</h3>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">
                                {{ $child->age ? $child->age.' tahun' : 'Umur tidak dinyatakan' }}
                            </div>
                        </div>
                    </div>

                    <div class="star-pill" title="Bintang Terkumpul">
                        ⭐ {{ $child->stars }}
                    </div>
                </div>

                {{-- STUDENT CODE (HOME-SCHOOL LINK PILL) --}}
                <div style="background: #f8fafc; border-radius: var(--radius-md); padding: 0.75rem 1rem; border: 1px dashed rgba(108, 92, 231, 0.3); margin-bottom: 1.2rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: var(--text-muted);">
                            Kod Murid (Kongsi Bersama {{ Auth::user()->isTeacher() ? 'Ibu Bapa' : 'Guru' }}):
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 900; color: var(--primary); letter-spacing: 1px;">
                            {{ $child->student_code }}
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn btn-outline btn-sm"
                        onclick="navigator.clipboard.writeText('{{ $child->student_code }}'); alert('Kod Murid {{ $child->student_code }} disalin!')"
                        title="Salin Kod Murid"
                    >
                        <i class="fa-regular fa-copy"></i> Salin
                    </button>
                </div>

                {{-- PROGRESS BAR --}}
                <div style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 800; margin-bottom: 0.4rem;">
                        <span>Kemajuan BIM Keseluruhan</span>
                        <span style="color: var(--primary);">{{ $child->summary['overall'] }}%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: {{ $child->summary['overall'] }}%;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-top: 0.4rem;">
                        <span>{{ $child->mastered_count }} Isyarat Dikuasai</span>
                        <span>{{ $child->quiz_attempts_count }} Percubaan Kuiz</span>
                    </div>
                </div>

                {{-- ACTION BUTTONS --}}
                <div style="margin-top: auto; display: grid; grid-template-columns: 1.3fr 1fr; gap: 0.6rem;">
                    <form action="{{ route('guardian.child.start', $child) }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn btn-accent btn-sm" style="width: 100%;">
                            <i class="fa-solid fa-play"></i> Buka Mod Murid
                        </button>
                    </form>

                    <a href="{{ route('guardian.child.show', $child) }}" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-chart-line"></i> Laporan
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- MODAL 1: ADD NEW CHILD --}}
<div id="modal-new-child" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; padding: 1rem;">
    <div class="card" style="max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto; position: relative;">
        <button type="button" onclick="document.getElementById('modal-new-child').style.display='none'" style="position: absolute; top: 1.2rem; right: 1.2rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">
            &times;
        </button>

        <h2 style="font-size: 1.6rem; margin-bottom: 0.3rem;">Tambah Profil Murid Baru</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
            Sistem akan cipta akaun dan jana <strong>Kod Murid Unik</strong> secara automatik.
        </p>

        <form action="{{ route('guardian.child.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name" class="form-label">Nama Murid:</label>
                <input type="text" id="name" name="name" class="form-input" placeholder="Contoh: Adam / Sarah" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="age" class="form-label">Umur (Tahun):</label>
                    <input type="number" id="age" name="age" min="2" max="18" value="7" class="form-input">
                </div>

                <div class="form-group">
                    <label for="pin" class="form-label">PIN 4-Digit (Untuk Murid Log Masuk):</label>
                    <input type="password" id="pin" name="pin" maxlength="4" pattern="\d{4}" class="form-input" placeholder="1234" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Pilih Avatar Kegemaran:</label>
                <div class="avatar-selector">
                    @foreach($avatars as $key => $emoji)
                        <label>
                            <input type="radio" name="avatar" value="{{ $key }}" class="avatar-radio" {{ $loop->first ? 'checked' : '' }}>
                            <div class="avatar-box">{{ $emoji }}</div>
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                <i class="fa-solid fa-floppy-disk"></i> Simpan & Jana Kod Murid
            </button>
        </form>
    </div>
</div>

{{-- MODAL 2: LINK EXISTING CHILD (HOME-SCHOOL COLLABORATION) --}}
<div id="modal-link-child" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; padding: 1rem;">
    <div class="card" style="max-width: 480px; width: 100%; position: relative;">
        <button type="button" onclick="document.getElementById('modal-link-child').style.display='none'" style="position: absolute; top: 1.2rem; right: 1.2rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">
            &times;
        </button>

        <div style="font-size: 2.5rem; color: var(--secondary); margin-bottom: 0.5rem;">🔗</div>
        <h2 style="font-size: 1.6rem; margin-bottom: 0.3rem;">Pautkan Kod Murid Sedia Ada</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
            Masukkan Kod Murid yang diberikan oleh {{ Auth::user()->isTeacher() ? 'ibu bapa murid' : 'guru sekolah' }} untuk melihat laporan kemajuan yang sama.
        </p>

        <form action="{{ route('guardian.child.link') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="link_student_code" class="form-label">Kod Murid (Contoh: ADAM-4821):</label>
                <input
                    type="text"
                    id="link_student_code"
                    name="student_code"
                    class="form-input"
                    placeholder="ADAM-XXXX"
                    style="text-transform: uppercase; font-size: 1.2rem; font-weight: 800; letter-spacing: 2px; text-align: center;"
                    required
                >
            </div>

            <button type="submit" class="btn btn-secondary btn-lg" style="width: 100%;">
                <i class="fa-solid fa-link"></i> Pautkan Murid Ini
            </button>
        </form>
    </div>
</div>
@endsection

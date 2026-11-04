@extends('layouts.app', ['title' => 'Laporan Kemajuan: '.$child->name])

@push('styles')
<style>
    .print-only {
        display: none;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 12mm 14mm;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            background: #ffffff !important;
            color: #1e293b !important;
            font-size: 10.5pt !important;
        }

        .site-header,
        .site-footer,
        .alert,
        .no-print,
        #kiko-widget,
        #modal-edit-child {
            display: none !important;
        }

        .print-only {
            display: block !important;
        }

        .page-container {
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #cbd5e1 !important;
            padding: 0.9rem 1.1rem !important;
            margin-bottom: 0.9rem !important;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .print-grid-2 {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 0.8rem !important;
        }

        .print-metrics-row {
            display: grid !important;
            grid-template-columns: 1fr 1.8fr !important;
            gap: 0.9rem !important;
            margin-bottom: 0.9rem !important;
        }

        .progress-track {
            background: #e2e8f0 !important;
            border: 1px solid #cbd5e1 !important;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('activityChart');
        if (ctx) {
            const rawData = @json($activity);
            const labels = Object.keys(rawData).map(d => {
                const parts = d.split('-');
                return parts[2] + '/' + parts[1]; // DD/MM
            });
            const data = Object.values(rawData);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Bilangan Latihan Isyarat',
                        data: data,
                        borderColor: '#6c5ce7',
                        backgroundColor: 'rgba(108, 92, 231, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#6c5ce7',
                        pointRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush

@section('content')
@php
    $isTeacher = Auth::user()->isTeacher();
    $canEditProfile = $isCreator || Auth::user()->isParent();
@endphp

{{-- PRINT-ONLY OFFICIAL REPORT LETTERHEAD --}}
<div class="print-only" style="border-bottom: 3px solid #0984e3; padding-bottom: 0.8rem; margin-bottom: 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <div style="font-size: 0.78rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1.2px; color: #0984e3;">
                🤟 GestureSpeak V2 &bull; Sistem Pembelajaran Bahasa Isyarat Malaysia (BIM)
            </div>
            <h1 style="font-size: 1.55rem; margin: 0.15rem 0 0; color: #0f172a;">
                LAPORAN PRESTASI &amp; PENGUASAAN MURID
            </h1>
        </div>
        <div style="text-align: right; font-size: 0.8rem; color: #475569;">
            <div><strong>Tarikh Cetakan:</strong> {{ now()->format('d/m/Y, h:i A') }}</div>
            <div><strong>Dicetak Oleh:</strong> {{ Auth::user()->name }} ({{ $isTeacher ? 'Guru' : 'Ibu Bapa' }})</div>
        </div>
    </div>
</div>

<div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.8rem;">
    <a href="{{ route('guardian.dashboard') }}" style="color: var(--primary); font-weight: 800; font-size: 0.95rem;">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke {{ $isTeacher ? 'Portal Kelas' : 'Portal Ibu Bapa' }}
    </a>

    <button type="button" onclick="window.print()" class="btn btn-primary btn-sm" style="background: #0984e3;">
        <i class="fa-solid fa-print"></i> Cetak / Simpan PDF Laporan
    </button>
</div>

{{-- CHILD PROFILE BANNER --}}
<div class="card" style="margin-bottom: 2rem; border-top: 5px solid {{ $isTeacher ? '#0984e3' : 'var(--primary)' }}; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
    <div style="display: flex; align-items: center; gap: 1.2rem;">
        <span style="font-size: 3.5rem; background: #f0edff; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
            {{ $child->avatarEmoji() }}
        </span>
        <div>
            <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                <h1 style="font-size: 2rem; margin: 0;">{{ $child->name }}</h1>
                <div class="star-pill">⭐ {{ $child->stars }} Bintang</div>
            </div>
            <div style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.2rem;">
                Kod Murid: <strong style="color: var(--primary);">{{ $child->student_code }}</strong> &bull;
                Umur: <strong>{{ $child->age ?? '-' }} Tahun</strong>
                @if($child->creator)
                    &bull; Didaftar oleh: <strong>{{ $child->creator->name }}</strong>
                @endif
            </div>

            {{-- LINKED GUARDIANS PILLS --}}
            <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem; flex-wrap: wrap;">
                <span style="font-size: 0.8rem; color: var(--text-muted); align-self: center;">Akses Bersama:</span>
                @foreach($child->guardians as $g)
                    <span class="role-badge {{ $g->role }}">
                        <i class="fa-solid fa-user-check"></i> {{ $g->name }} ({{ $g->role === 'teacher' ? 'Guru' : 'Ibu Bapa' }})
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="no-print" style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
        <form action="{{ route('guardian.child.start', $child) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-accent">
                <i class="fa-solid fa-play"></i> Buka Mod Murid
            </button>
        </form>

        @if($canEditProfile)
            <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-edit-child').style.display='flex'">
                <i class="fa-solid fa-key"></i> Urus Profil / Reset PIN
            </button>
        @endif

        <form action="{{ route('guardian.child.unlink', $child) }}" method="POST" onsubmit="return confirm('Nyahpautkan murid ini dari akaun anda? (Profil murid kekal disimpan)')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline" style="color: #d46b08; border-color: #d46b08;" title="Nyahpautkan dari akaun anda">
                <i class="fa-solid fa-unlink"></i> Nyahpaut
            </button>
        </form>

        @if($isCreator)
            <form action="{{ route('guardian.child.destroy', $child) }}" method="POST" onsubmit="return confirm('AMARAN: Padam profil {{ $child->name }} dan semua rekod kemajuannya secara kekal?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline" style="color: var(--danger); border-color: var(--danger);" title="Padam profil murid sepenuhnya">
                    <i class="fa-solid fa-trash"></i> Padam Profil
                </button>
            </form>
        @endif
    </div>
</div>

{{-- ROLE-SPECIFIC PANEL: TEACHER FEEDBACK FORM (NO-PRINT) --}}
@if($isTeacher)
    <div class="card no-print" style="margin-bottom: 2rem; border-left: 5px solid #0984e3; background: #f8fbff;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <h3 style="font-size: 1.25rem; margin: 0; color: #0984e3;">
                    <i class="fa-solid fa-chalkboard-user"></i> Ulasan Guru &amp; Tugasan Mingguan (Kerja Rumah)
                </h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.2rem 0 0;">
                    Ulasan dan modul sasaran di bawah akan dipaparkan terus di Portal Ibu Bapa, Pusat Pembelajaran {{ $child->name }}, dan pada cetakan laporan rasmi.
                </p>
            </div>
            @if($child->teacher_note_updated_at)
                <span style="font-size: 0.8rem; color: var(--text-muted);">
                    Dikemas kini: {{ $child->teacher_note_updated_at->format('d/m/Y, h:i A') }}
                </span>
            @endif
        </div>

        <form action="{{ route('guardian.child.note', $child) }}" method="POST">
            @csrf
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label" for="assigned_category_id">📌 Modul Sasaran / Kerja Rumah Minggu Ini:</label>
                    <select id="assigned_category_id" name="assigned_category_id" class="form-select">
                        <option value="">-- Tiada Tugasan Khusus --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (int) $child->assigned_category_id === (int) $cat->id ? 'selected' : '' }}>
                                {{ $cat->icon }} {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label" for="teacher_note">💬 Nota / Maklum Balas untuk Ibu Bapa:</label>
                    <textarea id="teacher_note" name="teacher_note" rows="2" class="form-input" placeholder="Contoh: {{ $child->name }} sudah mahir abjad A-F. Mohon ibu bapa bantu latih isyarat Keluarga di rumah.">{{ old('teacher_note', $child->teacher_note) }}</textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-sm" style="background: #0984e3;">
                <i class="fa-solid fa-paper-plane"></i> Simpan Ulasan &amp; Hantar ke Ibu Bapa
            </button>
        </form>
    </div>
@endif

{{-- READ-ONLY TEACHER REVIEW CARD (VISIBLE TO PARENTS ON SCREEN + ALWAYS VISIBLE ON PRINT) --}}
<div class="card {{ $isTeacher ? 'print-only' : '' }}" style="margin-bottom: 2rem; border-left: 5px solid #0984e3; background: #f8fbff;">
    <h3 style="font-size: 1.2rem; margin: 0 0 0.6rem; color: #0984e3;">
        <i class="fa-solid fa-school"></i> Ulasan Guru Sekolah &amp; Fokus Latihan
    </h3>
    @if($child->teacher_note || $child->assignedCategory)
        @if($child->assignedCategory)
            <div style="display: inline-block; background: #fff; border: 2px solid #0984e3; color: #0984e3; padding: 0.35rem 0.85rem; border-radius: 999px; font-weight: 900; font-size: 0.88rem; margin-bottom: 0.6rem;">
                📌 Fokus Minggu Ini: {{ $child->assignedCategory->icon }} {{ $child->assignedCategory->name }}
            </div>
        @endif
        @if($child->teacher_note)
            <p style="font-size: 0.98rem; background: #fff; padding: 0.85rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); margin: 0 0 0.45rem;">
                “{{ $child->teacher_note }}”
            </p>
        @endif
        <div style="font-size: 0.8rem; color: var(--text-muted);">
            Daripada: <strong>{{ $child->teacherReviewer->name ?? 'Guru Sekolah' }}</strong>
            @if($child->teacher_note_updated_at)
                &bull; {{ $child->teacher_note_updated_at->format('d/m/Y, h:i A') }}
            @endif
        </div>
    @else
        <p style="color: var(--text-muted); margin: 0; font-size: 0.92rem;">
            Tiada ulasan khusus direkodkan buat masa ini.
        </p>
    @endif
</div>

{{-- METRICS SUMMARY ROW --}}
<div class="print-metrics-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">

    {{-- OVERALL PROGRESS CARD --}}
    <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 1rem;">Penguasaan Keseluruhan BIM</h3>
        <div style="display: flex; align-items: baseline; gap: 0.5rem; margin-bottom: 0.5rem;">
            <span style="font-size: 3rem; font-weight: 900; color: var(--primary); font-family: var(--font-display);">
                {{ $summary['overall'] }}%
            </span>
            <span style="color: var(--text-muted); font-size: 0.9rem;">selesai</span>
        </div>
        <div class="progress-track" style="height: 18px;">
            <div class="progress-fill" style="width: {{ $summary['overall'] }}%;"></div>
        </div>
    </div>

    {{-- ACTIVITY CHART CARD --}}
    <div class="card" style="grid-column: span 2;">
        <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;">Aktiviti Latihan (14 Hari Lepas)</h3>
        <div style="height: 160px; width: 100%;">
            <canvas id="activityChart"></canvas>
        </div>
    </div>

</div>

{{-- CATEGORY PROGRESS BREAKDOWN --}}
<div class="card" style="margin-bottom: 2rem;">
    <h3 style="font-size: 1.3rem; margin-bottom: 1.2rem;">Pecahan Mengikut Kategori Modul</h3>

    <div class="print-grid-2" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.2rem;">
        @foreach($summary['categories'] as $cat)
            <div style="background: #f8fafc; border-radius: var(--radius-md); padding: 1rem; border-left: 5px solid {{ $cat['color'] }};">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <div style="font-weight: 800; font-size: 1.05rem;">
                        {{ $cat['icon'] }} {{ $cat['name'] }}
                    </div>
                    <div style="font-weight: 900; color: {{ $cat['color'] }}; font-size: 1.1rem;">
                        {{ $cat['percent'] }}%
                    </div>
                </div>

                <div class="progress-track" style="height: 10px; margin-bottom: 0.4rem;">
                    <div class="progress-fill" style="width: {{ $cat['percent'] }}%; background: {{ $cat['color'] }};"></div>
                </div>

                <div style="font-size: 0.8rem; color: var(--text-muted); text-align: right;">
                    {{ $cat['done'] }} / {{ $cat['total'] }} isyarat
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="print-grid-2" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">

    {{-- QUIZ ATTEMPTS HISTORY --}}
    <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 1rem;">Sejarah Skor Kuiz</h3>
        @if($attempts->isEmpty())
            <p style="color: var(--text-muted); font-size: 0.9rem;">Belum ada kuiz diselesaikan.</p>
        @else
            <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                @foreach($attempts as $att)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.7rem; background: #f8fafc; border-radius: var(--radius-sm);">
                        <div>
                            <strong>{{ $att->category->name }}</strong>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                {{ $att->created_at->format('d/m/Y, h:i A') }}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="color: #f39c12; font-weight: 900;">
                                @for($i = 0; $i < $att->stars; $i++) ⭐ @endfor
                            </div>
                            <div style="font-size: 0.8rem; font-weight: 800; color: var(--primary);">
                                {{ $att->score }} / {{ $att->total }} mata
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- SIGNS NEEDING PRACTICE --}}
    <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 1rem;">Perlu Latihan Tambahan</h3>
        @if($needsPractice->isEmpty())
            <div style="text-align: center; padding: 1.5rem; color: var(--text-muted);">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🎉</div>
                Semua isyarat yang dilatih telah berjaya dikuasai!
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                @foreach($needsPractice as $p)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.7rem; background: #fff5f5; border-radius: var(--radius-sm); border-left: 4px solid #ff7675;">
                        <div>
                            <strong>{{ $p->sign->word }}</strong> ({{ $p->sign->meaning_en }})
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                Dilatih {{ $p->practice_count }} kali
                            </div>
                        </div>
                        <div style="font-size: 0.85rem; font-weight: 800; color: #ff7675;">
                            Ketepatan: {{ (int)$p->best_confidence }}%
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

{{-- PRINT-ONLY OFFICIAL SIGNATURE BLOCK --}}
<div class="print-only" style="margin-top: 2.2rem; padding-top: 1.2rem; border-top: 2px dashed #cbd5e1; break-inside: avoid; page-break-inside: avoid;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">
        <div>
            <div style="font-size: 0.82rem; font-weight: 800; color: #475569; margin-bottom: 2.8rem;">
                Disahkan Oleh (Guru Kelas / Pembimbing):
            </div>
            <div style="border-bottom: 1.5px solid #1e293b; margin-bottom: 0.35rem;"></div>
            <div style="font-size: 0.85rem; font-weight: 800;">
                Nama: {{ $isTeacher ? Auth::user()->name : ($child->teacherReviewer->name ?? '..................................................') }}
            </div>
            <div style="font-size: 0.8rem; color: #64748b;">Tarikh: ....................................</div>
        </div>

        <div>
            <div style="font-size: 0.82rem; font-weight: 800; color: #475569; margin-bottom: 2.8rem;">
                Diterima &amp; Disemak Oleh (Ibu Bapa / Penjaga):
            </div>
            <div style="border-bottom: 1.5px solid #1e293b; margin-bottom: 0.35rem;"></div>
            <div style="font-size: 0.85rem; font-weight: 800;">
                Nama: {{ ! $isTeacher ? Auth::user()->name : '..................................................' }}
            </div>
            <div style="font-size: 0.8rem; color: #64748b;">Tarikh: ....................................</div>
        </div>
    </div>
</div>

@if($canEditProfile)
<div id="modal-edit-child" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; padding: 1rem;">
    <div class="card" style="max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto; position: relative;">
        <button type="button" onclick="document.getElementById('modal-edit-child').style.display='none'" style="position: absolute; top: 1.2rem; right: 1.2rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">
            &times;
        </button>

        <h2 style="font-size: 1.5rem; margin-bottom: 0.3rem;">Kemas Kini Profil &amp; PIN {{ $child->name }}</h2>
        <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.2rem;">
            Biarkan ruangan PIN kosong jika tidak mahu menukar PIN 4-digit sedia ada.
        </p>

        <form action="{{ route('guardian.child.update', $child) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label" for="edit_name">Nama Murid:</label>
                <input type="text" id="edit_name" name="name" value="{{ $child->name }}" class="form-input" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="edit_age">Umur (Tahun):</label>
                    <input type="number" id="edit_age" name="age" min="2" max="18" value="{{ $child->age }}" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_pin">PIN 4-Digit Baharu (Pilihan):</label>
                    <input type="password" id="edit_pin" name="pin" maxlength="4" pattern="\d{4}" class="form-input" placeholder="••••">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Avatar:</label>
                <div class="avatar-selector">
                    @foreach($avatars as $key => $emoji)
                        <label>
                            <input type="radio" name="avatar" value="{{ $key }}" class="avatar-radio" {{ $child->avatar === $key ? 'checked' : '' }}>
                            <div class="avatar-box">{{ $emoji }}</div>
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
            </button>
        </form>
    </div>
</div>
@endif
@endsection

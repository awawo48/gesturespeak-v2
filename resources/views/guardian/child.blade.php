@extends('layouts.app', ['title' => 'Laporan Kemajuan: '.$child->name])

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
<div style="margin-bottom: 1.5rem;">
    <a href="{{ route('guardian.dashboard') }}" style="color: var(--primary); font-weight: 800; font-size: 0.95rem;">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
    </a>
</div>

{{-- CHILD PROFILE BANNER --}}
<div class="card" style="margin-bottom: 2rem; border-top: 5px solid var(--primary); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
    <div style="display: flex; align-items: center; gap: 1.2rem;">
        <span style="font-size: 3.5rem; background: #f0edff; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
            {{ $child->avatarEmoji() }}
        </span>
        <div>
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <h1 style="font-size: 2rem; margin: 0;">{{ $child->name }}</h1>
                <div class="star-pill">⭐ {{ $child->stars }} Bintang</div>
            </div>
            <div style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.2rem;">
                Kod Murid: <strong style="color: var(--primary);">{{ $child->student_code }}</strong> &bull;
                Umur: <strong>{{ $child->age ?? '-' }} Tahun</strong>
            </div>

            {{-- LINKED GUARDIANS PILLS --}}
            <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem; flex-wrap: wrap;">
                <span style="font-size: 0.8rem; color: var(--text-muted); align-self: center;">Akses Bersama:</span>
                @foreach($child->guardians as $g)
                    <span class="role-badge {{ $g->role }}">
                        <i class="fa-solid fa-user-check"></i> {{ $g->name }} ({{ $g->pivot->relationship }})
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 0.8rem;">
        <form action="{{ route('guardian.child.start', $child) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-accent btn-lg">
                <i class="fa-solid fa-play"></i> Buka Mod Murid
            </button>
        </form>

        <form action="{{ route('guardian.child.unlink', $child) }}" method="POST" onsubmit="return confirm('Adakah anda pasti mahu menyahpautkan murid ini dari akaun anda?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline" style="color: var(--danger); border-color: var(--danger);" title="Nyahpautkan Murid">
                <i class="fa-solid fa-unlink"></i> Nyahpaut
            </button>
        </form>
    </div>
</div>

{{-- METRICS SUMMARY ROW --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">

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

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.2rem;">
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

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">

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
@endsection

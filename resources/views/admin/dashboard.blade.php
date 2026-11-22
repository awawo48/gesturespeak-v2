@extends('layouts.admin', ['title' => 'Papan pemuka — Sign Cilik'])

@push('styles')
<style>
    .dash-metrics {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        background: var(--adm-surface);
        border: 1px solid var(--adm-hairline-strong);
        border-radius: 6px;
        margin-bottom: 20px;
        box-shadow: 0 2px 0 rgba(22, 50, 79, 0.05);
    }
    .dash-metric {
        padding: 16px 20px;
        border-inline-end: 1px solid var(--adm-hairline);
    }
    .dash-metric:last-child { border-inline-end: none; }
    .dash-metric-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .dash-metric-label {
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--adm-text-muted);
    }
    .dash-metric-val {
        font-family: var(--adm-mono);
        font-size: 2rem;
        font-weight: 500;
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.01em;
        color: var(--adm-ink);
        line-height: 1.08;
        margin: 8px 0 4px;
    }
    .dash-metric-sub {
        font-size: 0.78rem;
        color: var(--adm-text-muted);
    }
    .chart-toggle {
        display: inline-flex;
        padding: 2px;
        background: var(--adm-raised);
        border: 1px solid var(--adm-hairline);
        border-radius: 4px;
        gap: 2px;
    }
    .chart-toggle-btn {
        border: none;
        background: transparent;
        color: var(--adm-text-muted);
        font-family: var(--adm-sans);
        font-size: 0.78rem;
        font-weight: 500;
        padding: 4px 10px;
        border-radius: 3px;
        cursor: pointer;
    }
    .chart-toggle-btn[aria-pressed="true"] {
        background: var(--adm-surface);
        color: var(--adm-ink);
        box-shadow: 0 1px 0 rgba(22, 50, 79, 0.08);
    }
    .chart-readout {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 8px 12px;
        margin-bottom: 12px;
        border-radius: 4px;
        background: var(--adm-raised);
        border: 1px solid var(--adm-hairline);
        font-size: 0.8rem;
        color: var(--adm-text-secondary);
    }
    .mod-summary-strip {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 16px;
        padding-bottom: 14px;
        border-bottom: 1px solid var(--adm-hairline);
    }
    .mod-summary-item {
        padding: 8px 10px;
        border-radius: 4px;
        background: var(--adm-canvas);
        border: 1px solid var(--adm-hairline);
    }
    .adm-avatar-chip {
        width: 30px;
        height: 30px;
        border-radius: 3px;
        background: var(--adm-raised);
        border: 1px solid var(--adm-hairline-strong);
        color: var(--adm-ink);
        font-family: var(--adm-display);
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .quick-link-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 9px 10px;
        border-radius: 4px;
        text-decoration: none;
        color: var(--adm-ink);
        font-size: 0.85rem;
        font-weight: 500;
        border: 1px solid transparent;
        transition: background-color 140ms var(--adm-ease), border-color 140ms var(--adm-ease);
    }
    @media (hover: hover) and (pointer: fine) {
        .quick-link-row:hover {
            background: var(--adm-raised);
            border-color: var(--adm-hairline);
        }
    }
    .dash-main-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.55fr) minmax(290px, 1fr);
        gap: 20px;
        align-items: start;
    }
    .mod-row {
        padding: 10px 0;
        border-bottom: 1px solid var(--adm-hairline);
    }
    .mod-row:first-child { padding-top: 0; }
    .mod-row:last-child { border-bottom: none; padding-bottom: 0; }
    .mod-head {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 12px;
        margin-bottom: 6px;
        font-size: 0.84rem;
    }
    .mod-track {
        display: flex;
    }
    .mod-seg-steps, .mod-seg-dtw, .mod-seg-static {
        position: relative;
        z-index: 1;
    }
    .mod-seg-steps { background: var(--adm-pos); }
    .mod-seg-dtw { background: var(--adm-sky); }
    .mod-seg-static { background: var(--adm-ink); }
    .mod-legend {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
        font-size: 0.75rem;
        color: var(--adm-text-muted);
    }
    .mod-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .mod-swatch {
        width: 8px;
        height: 8px;
        border-radius: 1px;
        display: inline-block;
    }
    .chart-figure { margin: 0; }
    .chart-svg {
        width: 100%;
        height: auto;
        display: block;
        overflow: visible;
    }
    .chart-axis-text {
        font-family: var(--adm-mono);
        font-size: 12px;
        fill: var(--adm-text-muted);
    }
    .chart-grid-line {
        stroke: var(--adm-hairline);
        stroke-width: 1;
    }
    .chart-point {
        cursor: pointer;
        transition: r 120ms var(--adm-ease);
    }
    .chart-point:hover { r: 6; }
    .chart-details {
        margin-top: 12px;
        border-top: 1px solid var(--adm-hairline);
        padding-top: 10px;
    }
    .chart-details summary {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--adm-accent);
        cursor: pointer;
        list-style: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .chart-details summary::-webkit-details-marker { display: none; }
    @media (max-width: 960px) {
        .dash-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dash-metric:nth-child(2) { border-inline-end: none; }
        .dash-metric:nth-child(1), .dash-metric:nth-child(2) { border-bottom: 1px solid var(--adm-hairline); }
        .dash-main-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 520px) {
        .dash-metrics { grid-template-columns: 1fr; }
        .dash-metric { border-inline-end: none; border-bottom: 1px solid var(--adm-hairline); }
        .dash-metric:last-child { border-bottom: none; }
        .mod-summary-strip { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="adm-page-head">
    <div>
        <h1>Papan pemuka</h1>
        <p class="adm-page-sub">Ringkasan kurikulum Bahasa Isyarat Malaysia, akaun pengguna dan liputan data AI</p>
    </div>

    <a href="{{ route('admin.ai.studio') }}" class="btn btn-primary btn-sm">
        Buka studio AI
    </a>
</div>

@if(($stats['pending_teachers'] ?? 0) > 0)
    <div class="adm-notice" role="status">
        <span><strong>{{ $stats['pending_teachers'] }} akaun guru</strong> menunggu kelulusan pentadbir</span>
        <a href="{{ route('admin.users', ['role' => 'teacher', 'status' => 'pending']) }}" class="btn btn-outline btn-sm">
            Semak sekarang
        </a>
    </div>
@endif

<div class="dash-metrics">
    <div class="dash-metric">
        <div class="dash-metric-top">
            <span class="dash-metric-label">Pengguna dewasa</span>
        </div>
        <div class="dash-metric-val">{{ number_format($stats['total_users']) }}</div>
        <div class="dash-metric-sub">{{ $stats['total_parents'] }} ibu bapa &middot; {{ $stats['total_teachers'] }} guru</div>
    </div>
    <div class="dash-metric">
        <div class="dash-metric-top">
            <span class="dash-metric-label">Profil murid</span>
        </div>
        <div class="dash-metric-val">{{ number_format($stats['total_students']) }}</div>
        <div class="dash-metric-sub">{{ number_format($stats['total_practices'] ?? 0) }} jumlah latihan</div>
    </div>
    <div class="dash-metric">
        <div class="dash-metric-top">
            <span class="dash-metric-label">Isyarat dikuasai</span>
        </div>
        <div class="dash-metric-val">{{ number_format($stats['total_mastered']) }}</div>
        <div class="dash-metric-sub">Daripada {{ $stats['total_signs'] }} isyarat BIM</div>
    </div>
    <div class="dash-metric">
        <div class="dash-metric-top">
            <span class="dash-metric-label">Sesi kuiz selesai</span>
        </div>
        <div class="dash-metric-val">{{ number_format($stats['total_quizzes']) }}</div>
        <div class="dash-metric-sub">Rekod percubaan murid</div>
    </div>
</div>

<div class="dash-main-grid">
    {{-- Primary Analysis Column: Interactive 7-Day Line/Bar Chart + BIM AI Dataset Coverage --}}
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        @php
            $days = $dailyActivity ?? collect();
            $maxY = max(4, (int) $days->max(fn ($d) => max($d['quizzes'], $d['samples'])));
            $stepY = max(1, (int) ceil($maxY / 4));
            $topY = $stepY * 4;
            $w = 640; $h = 205;
            $padL = 34; $padR = 20; $padT = 14; $padB = 28;
            $plotW = $w - $padL - $padR;
            $plotH = $h - $padT - $padB;
            $nPts = max(2, $days->count());
            $ptQuiz = [];
            $ptSample = [];
            foreach ($days->values() as $idx => $d) {
                $x = round($padL + ($idx / ($nPts - 1)) * $plotW, 1);
                $yQ = round($padT + $plotH - ($d['quizzes'] / $topY) * $plotH, 1);
                $yS = round($padT + $plotH - ($d['samples'] / $topY) * $plotH, 1);
                $hQ = round(($d['quizzes'] / $topY) * $plotH, 1);
                $hS = round(($d['samples'] / $topY) * $plotH, 1);
                $ptQuiz[] = ['x' => $x, 'y' => $yQ, 'h' => $hQ, 'val' => $d['quizzes'], 'label' => $d['label'], 'date' => $d['date'], 'avg' => $d['avg_pct']];
                $ptSample[] = ['x' => $x, 'y' => $yS, 'h' => $hS, 'val' => $d['samples'], 'label' => $d['label'], 'date' => $d['date']];
            }
            $quizLinePts = collect($ptQuiz)->map(fn ($p) => $p['x'].','.$p['y'])->implode(' ');
            $sampleLinePts = collect($ptSample)->map(fn ($p) => $p['x'].','.$p['y'])->implode(' ');
            $baseY = $padT + $plotH;
            $quizAreaPts = $ptQuiz
                ? ($ptQuiz[0]['x'].','.$baseY.' '.$quizLinePts.' '.$ptQuiz[count($ptQuiz) - 1]['x'].','.$baseY)
                : '';
            $sumQuizzes7d = (int) $days->sum('quizzes');
            $sumSamples7d = (int) $days->sum('samples');
            $avgDays = $days->filter(fn ($d) => $d['avg_pct'] !== null);
            $avgScore7d = $avgDays->isNotEmpty() ? (int) round($avgDays->avg('avg_pct')) : null;
            $daysJson = $days->values()->toJson();
        @endphp

        <div class="adm-card" x-data="{ chartMode: 'line', activeDay: null, daysData: {{ $daysJson }} }">
            <div class="adm-card-head">
                <div>
                    <h2 class="adm-card-title">Analisis aktiviti 7 hari terkini</h2>
                    <div style="font-size: 0.78rem; color: var(--adm-text-muted); margin-top: 2px;">
                        Pantauan sesi kuiz harian murid dan kutipan sampel kamera AI
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <div class="mod-legend">
                        <span class="mod-legend-item"><span class="mod-swatch" style="background: var(--adm-accent);"></span>Sesi kuiz</span>
                        <span class="mod-legend-item"><span class="mod-swatch" style="background: var(--adm-pos);"></span>Sampel AI</span>
                    </div>
                    <div class="chart-toggle" role="group" aria-label="Mod paparan carta">
                        <button type="button" class="chart-toggle-btn" :aria-pressed="(chartMode === 'line').toString()" @click="chartMode = 'line'">Garis</button>
                        <button type="button" class="chart-toggle-btn" :aria-pressed="(chartMode === 'bar').toString()" @click="chartMode = 'bar'">Bar</button>
                    </div>
                </div>
            </div>

            <div class="chart-readout" aria-live="polite">
                <template x-if="activeDay === null">
                    <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap; width: 100%; justify-content: space-between;">
                        <span><strong>Ringkasan 7 hari:</strong> <span class="adm-mono">{{ $sumQuizzes7d }}</span> kuiz &middot; <span class="adm-mono">{{ $sumSamples7d }}</span> sampel AI</span>
                        <span>Purata skor kuiz: <strong class="adm-mono" style="color: var(--adm-ink);">{{ $avgScore7d !== null ? $avgScore7d.'%' : 'Tiada rekod' }}</strong></span>
                    </div>
                </template>
                <template x-if="activeDay !== null">
                    <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap; width: 100%; justify-content: space-between;">
                        <span><strong x-text="daysData[activeDay].date + ' (' + daysData[activeDay].label + ')'"></strong></span>
                        <span>
                            Kuiz: <strong class="adm-mono" style="color: var(--adm-accent);" x-text="daysData[activeDay].quizzes"></strong> &middot;
                            Sampel AI: <strong class="adm-mono" style="color: var(--adm-pos);" x-text="daysData[activeDay].samples"></strong> &middot;
                            Skor: <strong class="adm-mono" x-text="daysData[activeDay].avg_pct !== null ? daysData[activeDay].avg_pct + '%' : '—'"></strong>
                        </span>
                    </div>
                </template>
            </div>

            <figure class="chart-figure">
                <svg class="chart-svg" viewBox="0 0 {{ $w }} {{ $h }}" role="img" aria-labelledby="activity-chart-title activity-chart-desc" @mouseleave="activeDay = null">
                    <title id="activity-chart-title">Carta aktiviti harian 7 hari terkini</title>
                    <desc id="activity-chart-desc">Perbandingan bilangan sesi kuiz murid ({{ $sumQuizzes7d }} keseluruhan) dan sampel AI direkod ({{ $sumSamples7d }} keseluruhan) sepanjang 7 hari terkini.</desc>

                    @for($tick = 0; $tick <= 4; $tick++)
                        @php
                            $valTick = $tick * $stepY;
                            $yTick = round($padT + $plotH - ($valTick / $topY) * $plotH, 1);
                        @endphp
                        <line x1="{{ $padL }}" y1="{{ $yTick }}" x2="{{ $w - $padR }}" y2="{{ $yTick }}" class="chart-grid-line" />
                        <text x="{{ $padL - 8 }}" y="{{ $yTick + 4 }}" text-anchor="end" class="chart-axis-text">{{ $valTick }}</text>
                    @endfor

                    {{-- Line Chart Mode --}}
                    <g x-show="chartMode === 'line'">
                        @if($quizAreaPts)
                            <polygon points="{{ $quizAreaPts }}" fill="rgba(38, 176, 229, 0.14)" />
                            <polyline fill="none" stroke="#0284c7" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" points="{{ $quizLinePts }}" />
                        @endif
                        @if($sampleLinePts)
                            <polyline fill="none" stroke="#059669" stroke-width="2" stroke-dasharray="5 4" stroke-linejoin="round" stroke-linecap="round" points="{{ $sampleLinePts }}" />
                        @endif
                        @foreach($ptQuiz as $i => $pq)
                            @php $ps = $ptSample[$i]; @endphp
                            <circle cx="{{ $ps['x'] }}" cy="{{ $ps['y'] }}" r="4" fill="#059669" stroke="#ffffff" stroke-width="1.5" class="chart-point">
                                <title>{{ $ps['date'] }}: {{ $ps['val'] }} sampel AI</title>
                            </circle>
                            <circle cx="{{ $pq['x'] }}" cy="{{ $pq['y'] }}" r="4.5" fill="#0284c7" stroke="#ffffff" stroke-width="1.5" class="chart-point">
                                <title>{{ $pq['date'] }}: {{ $pq['val'] }} kuiz{{ $pq['avg'] !== null ? ' (purata '.$pq['avg'].'%)' : '' }}</title>
                            </circle>
                        @endforeach
                    </g>

                    {{-- Grouped Bar Chart Mode --}}
                    <g x-show="chartMode === 'bar'" style="display: none;">
                        @foreach($ptQuiz as $i => $pq)
                            @php
                                $ps = $ptSample[$i];
                                $qH = $pq['val'] > 0 ? max(4, $pq['h']) : 2;
                                $sH = $ps['val'] > 0 ? max(4, $ps['h']) : 2;
                            @endphp
                            <rect x="{{ $pq['x'] - 13 }}" y="{{ $baseY - $qH }}" width="11" height="{{ $qH }}" rx="1" fill="{{ $pq['val'] > 0 ? '#0284c7' : '#cbd5e1' }}">
                                <title>{{ $pq['date'] }}: {{ $pq['val'] }} kuiz</title>
                            </rect>
                            <rect x="{{ $ps['x'] + 2 }}" y="{{ $baseY - $sH }}" width="11" height="{{ $sH }}" rx="1" fill="{{ $ps['val'] > 0 ? '#059669' : '#e2e8f0' }}">
                                <title>{{ $ps['date'] }}: {{ $ps['val'] }} sampel AI</title>
                            </rect>
                        @endforeach
                    </g>

                    {{-- Shared X Labels + Accessible Hover/Focus Hit Strips --}}
                    @foreach($ptQuiz as $i => $pq)
                        <text x="{{ $pq['x'] }}" y="{{ $h - 6 }}" text-anchor="middle" class="chart-axis-text">{{ $pq['label'] }}</text>
                        <rect x="{{ $pq['x'] - 30 }}" y="{{ $padT }}" width="60" height="{{ $plotH }}" fill="transparent" style="cursor: pointer;"
                              tabindex="0" role="button" aria-label="Ringkasan {{ $pq['date'] }}: {{ $pq['val'] }} kuiz, {{ $ptSample[$i]['val'] }} sampel AI"
                              @mouseenter="activeDay = {{ $i }}" @focus="activeDay = {{ $i }}" />
                    @endforeach
                </svg>
                <figcaption class="adm-page-sub" style="margin-top: 6px; font-size: 0.78rem;">
                    Arahkan penunjuk pada graf untuk butiran harian, atau tukar paparan antara graf garis dan bar
                </figcaption>

                <details class="chart-details">
                    <summary>Lihat jadual data 7 hari</summary>
                    <div class="adm-card-flush" tabindex="0" role="region" aria-label="Jadual data aktiviti 7 hari terkini" style="margin-top: 10px; border: 1px solid var(--adm-hairline); border-radius: 4px;">
                        <table class="adm-table">
                            <caption style="padding: 8px 14px; font-size: 0.78rem; color: var(--adm-text-muted); text-align: left; caption-side: top;">Ringkasan harian kuiz dan sampel AI</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Tarikh</th>
                                    <th scope="col" style="text-align: right;">Sesi kuiz</th>
                                    <th scope="col" style="text-align: right;">Purata skor</th>
                                    <th scope="col" style="text-align: right;">Sampel AI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($days as $d)
                                    <tr>
                                        <th scope="row" style="font-weight: 500; color: var(--adm-ink);">{{ $d['date'] }} ({{ $d['label'] }})</th>
                                        <td class="adm-mono" style="text-align: right;">{{ $d['quizzes'] }}</td>
                                        <td class="adm-mono" style="text-align: right; color: var(--adm-text-muted);">{{ $d['avg_pct'] !== null ? $d['avg_pct'].'%' : '—' }}</td>
                                        <td class="adm-mono" style="text-align: right;">{{ $d['samples'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </figure>
        </div>

        {{-- Product Moment: BIM Curriculum & AI Dataset Coverage --}}
        @php
            $sTotals = $sampleTotals ?? ['steps' => 0, 'sequence' => 0, 'static' => 0];
        @endphp
        <div class="adm-card">
        <div class="adm-card-head">
            <div>
                <h2 class="adm-card-title">Liputan data AI mengikut modul BIM</h2>
                <div style="font-size: 0.78rem; color: var(--adm-text-muted); margin-top: 2px;">Perbandingan isyarat yang sudah mempunyai sampel latihan kamera</div>
            </div>
            <div class="mod-legend">
                <span class="mod-legend-item"><span class="mod-swatch mod-seg-steps"></span>Langkah</span>
                <span class="mod-legend-item"><span class="mod-swatch mod-seg-dtw"></span>DTW</span>
                <span class="mod-legend-item"><span class="mod-swatch mod-seg-static"></span>Statik</span>
                <span class="mod-legend-item"><span class="mod-swatch" style="background: #e8f1f8;"></span>Tiada</span>
            </div>
        </div>

        <div class="mod-summary-strip">
            <div class="mod-summary-item">
                <div style="font-size: 0.72rem; color: var(--adm-text-muted);">Urutan Langkah</div>
                <div class="adm-mono" style="color: var(--adm-pos); font-size: 1.05rem;">{{ number_format($sTotals['steps']) }} sampel</div>
            </div>
            <div class="mod-summary-item">
                <div style="font-size: 0.72rem; color: var(--adm-text-muted);">Klip Dinamik (DTW)</div>
                <div class="adm-mono" style="color: var(--adm-accent); font-size: 1.05rem;">{{ number_format($sTotals['sequence']) }} klip</div>
            </div>
            <div class="mod-summary-item">
                <div style="font-size: 0.72rem; color: var(--adm-text-muted);">Pegun Statik</div>
                <div class="adm-mono" style="color: var(--adm-ink); font-size: 1.05rem;">{{ number_format($sTotals['static']) }} sampel</div>
            </div>
        </div>

        @forelse($moduleReadiness as $mod)
            @php
                $t = max(1, $mod['total']);
                $pSteps = round(($mod['steps'] / $t) * 100, 1);
                $pDtw = round(($mod['dtw'] / $t) * 100, 1);
                $pStatic = round(($mod['static'] / $t) * 100, 1);
                $readyPct = (int) round(($mod['ready'] / $t) * 100);
            @endphp
            <div class="mod-row">
                <div class="mod-head">
                    <span style="font-weight: 600; color: var(--adm-ink);">{{ $mod['name'] }}</span>
                    <span class="adm-mono" style="color: var(--adm-text-muted);">{{ $mod['ready'] }}/{{ $mod['total'] }} sedia ({{ $readyPct }}%)</span>
                </div>
                <div class="adm-pixel-track mod-track" title="{{ $mod['name'] }}: {{ $mod['steps'] }} langkah, {{ $mod['dtw'] }} DTW, {{ $mod['static'] }} statik, {{ $mod['missing'] }} belum dirakam">
                    @if($pSteps > 0)<div class="mod-seg-steps" style="width: {{ $pSteps }}%;"></div>@endif
                    @if($pDtw > 0)<div class="mod-seg-dtw" style="width: {{ $pDtw }}%;"></div>@endif
                    @if($pStatic > 0)<div class="mod-seg-static" style="width: {{ $pStatic }}%;"></div>@endif
                </div>
            </div>
        @empty
            <p style="color: var(--adm-text-muted); font-size: 0.85rem; margin: 0;">Belum ada modul BIM didaftarkan</p>
        @endforelse
        </div>
    </div>

    {{-- Narrow Secondary Column: Quick Links, Recent Students & Quizzes --}}
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="adm-card">
            <div class="adm-card-head" style="margin-bottom: 10px;">
                <h2 class="adm-card-title">Tindakan pantas</h2>
            </div>
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <a href="{{ route('admin.ai.studio') }}" class="quick-link-row">
                    <span>Rekod sampel kamera di Studio AI</span>
                    <span style="color: var(--adm-accent);">&rarr;</span>
                </a>
                <a href="{{ route('admin.signs') }}" class="quick-link-row">
                    <span>Kemaskini kurikulum isyarat BIM</span>
                    <span style="color: var(--adm-accent);">&rarr;</span>
                </a>
                <a href="{{ route('admin.users') }}" class="quick-link-row">
                    <span>Urus akaun ibu bapa &amp; guru</span>
                    <span style="color: var(--adm-accent);">&rarr;</span>
                </a>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-head">
                <h2 class="adm-card-title">Murid terkini</h2>
                <a href="{{ route('admin.students') }}" style="color: var(--adm-accent); font-size: 0.8rem; font-weight: 600; text-decoration: none;">
                    Lihat semua
                </a>
            </div>

            @forelse($recentStudents as $student)
                <div class="adm-list-row">
                    <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                        <span class="adm-avatar-chip" aria-hidden="true">{{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}</span>
                        <div style="min-width: 0;">
                            <a href="{{ route('admin.students.show', $student) }}" style="font-weight: 600; color: var(--adm-ink); text-decoration: none; font-size: 0.875rem;">
                                {{ $student->name }}
                            </a>
                            <div style="font-size: 0.75rem; color: var(--adm-text-muted); margin-top: 2px;">
                                <span class="adm-mono">{{ $student->student_code }}</span> &middot; {{ $student->guardians->count() }} penjaga
                            </div>
                        </div>
                    </div>
                    <div class="adm-mono" style="color: var(--adm-ink); white-space: nowrap;">
                        {{ $student->stars }} bintang
                    </div>
                </div>
            @empty
                <p style="color: var(--adm-text-muted); font-size: 0.85rem; margin: 0;">Belum ada murid didaftarkan</p>
            @endforelse
        </div>

        <div class="adm-card">
            <div class="adm-card-head">
                <h2 class="adm-card-title">Percubaan kuiz terkini</h2>
            </div>

            @forelse($recentQuizzes as $q)
                @php
                    $qPct = $q->total > 0 ? round(($q->score / $q->total) * 100) : 0;
                @endphp
                <div class="adm-list-row">
                    <div style="min-width: 0;">
                        <div style="font-weight: 600; color: var(--adm-ink); font-size: 0.875rem;">{{ $q->student->name ?? 'Murid' }}</div>
                        <div style="font-size: 0.75rem; color: var(--adm-text-muted); margin-top: 2px;">
                            {{ $q->category->name ?? 'Umum' }} &middot; {{ $q->created_at->diffForHumans() }}
                        </div>
                    </div>
                    <span class="adm-status">
                        <span class="adm-dot {{ $qPct >= 75 ? 'adm-dot--ok' : 'adm-dot--warn' }}"></span>
                        <span class="adm-mono" style="color: var(--adm-ink);">{{ $q->score }}/{{ $q->total }}</span>
                    </span>
                </div>
            @empty
                <p style="color: var(--adm-text-muted); font-size: 0.85rem; margin: 0;">Belum ada aktiviti kuiz</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

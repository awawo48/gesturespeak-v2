@extends('layouts.admin', ['title' => 'Studio latihan AI — Sign Cilik'])

@push('styles')
<style>
    .studio-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .studio-header h1 {
        font-size: 1.5rem;
        font-weight: 600;
        letter-spacing: -0.015em;
        margin: 0;
    }
    .studio-header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .studio-topbar-meta {
        font-family: var(--adm-mono);
        font-size: 0.78rem;
        font-variant-numeric: tabular-nums;
        color: var(--adm-text-muted);
    }

    .studio-grid-v2 {
        display: grid;
        grid-template-columns: minmax(0, 1.55fr) minmax(290px, 0.72fr);
        gap: 20px;
        align-items: start;
    }
    .studio-grid-v2.layout-wide { grid-template-columns: 1fr; }
    .studio-main-col, .studio-side-col { min-width: 0; }
    @media (min-width: 980px) {
        .studio-grid-v2:not(.layout-wide) .studio-main-col {
            position: sticky;
            top: 20px;
        }
    }
    @media (max-width: 980px) {
        .studio-grid-v2 { grid-template-columns: 1fr; }
    }

    .console-card { padding: 0; overflow: hidden; }
    .console-tabs-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 0 20px;
        border-bottom: 1px solid var(--adm-hairline);
        flex-wrap: wrap;
    }
    .mode-tablist {
        display: flex;
        align-items: center;
        gap: 20px;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .mode-tab-btn {
        position: relative;
        flex: none;
        border: none;
        background: transparent;
        padding: 12px 0;
        font-family: var(--adm-sans);
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--adm-text-muted);
        cursor: pointer;
        white-space: nowrap;
        transition: color 140ms var(--adm-ease);
    }
    .mode-tab-btn[aria-selected="true"] { color: var(--adm-ink); }
    .mode-tab-btn[aria-selected="true"]::after {
        content: "";
        position: absolute;
        inset: auto 0 0;
        border-block-end: 2px solid var(--adm-sky);
    }
    .console-body { padding: 16px 20px 20px; }
    .console-target-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .console-target-title {
        font-size: 1.15rem;
        font-weight: 600;
        color: var(--adm-ink);
        margin: 0;
    }

    /* Compact Instrument Controls */
    .seg-group {
        display: inline-flex;
        background: var(--adm-raised);
        padding: 2px;
        border-radius: 4px;
        border: 1px solid var(--adm-hairline);
        gap: 2px;
    }
    .seg-btn {
        border: none;
        background: transparent;
        color: var(--adm-text-muted);
        font-family: var(--adm-sans);
        font-size: 0.78rem;
        font-weight: 500;
        padding: 4px 10px;
        border-radius: 3px;
        cursor: pointer;
        white-space: nowrap;
    }
    .seg-btn.active {
        background: var(--adm-surface);
        color: var(--adm-ink);
        box-shadow: 0 1px 0 rgba(22, 50, 79, 0.08);
    }
    .cam-tool-btn {
        border: 1px solid var(--adm-hairline-strong);
        background: var(--adm-surface);
        color: var(--adm-ink);
        font-family: var(--adm-sans);
        font-size: 0.8rem;
        font-weight: 500;
        height: 32px;
        padding: 0 10px;
        border-radius: 4px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 1px 0 rgba(22, 50, 79, 0.06);
        transition: background-color 140ms var(--adm-ease), border-color 140ms var(--adm-ease);
    }
    @media (hover: hover) and (pointer: fine) {
        .cam-tool-btn:hover { background: var(--adm-raised); }
    }
    .cam-tool-btn.active {
        background: var(--adm-ink);
        border-color: var(--adm-ink);
        color: #fff;
    }
    .cam-select-compact {
        padding: 0 10px;
        height: 32px;
        font-size: 0.8rem;
        font-weight: 500;
        border-radius: 4px;
        border: 1px solid var(--adm-hairline-strong);
        background: var(--adm-surface);
        color: var(--adm-ink);
        font-family: var(--adm-sans);
        max-width: 210px;
    }

    /* Camera Stage (Sign Cilik deep navy visor, clean 1px border, no neon blur) */
    .studio-stage {
        position: relative;
        background: var(--adm-rail);
        border-radius: 6px;
        overflow: hidden;
        aspect-ratio: 4/3;
        width: 100%;
        max-height: 70dvh;
        margin-bottom: 12px;
        border: 1px solid rgba(88, 202, 243, 0.32);
    }
    .studio-stage.ratio-16-9 { aspect-ratio: 16/9; }
    .studio-stage.ratio-1-1 { aspect-ratio: 1/1; max-width: 540px; margin-inline: auto; }
    .studio-stage.ratio-auto { aspect-ratio: auto; min-height: 360px; height: 52dvh; }
    .studio-stage:fullscreen {
        max-height: none; max-width: none; width: 100vw; height: 100dvh; border-radius: 0; border: none;
    }
    .studio-stage video, .studio-stage canvas {
        position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);
    }
    .studio-stage.unmirrored video, .studio-stage.unmirrored canvas { transform: none; }
    .studio-stage.fit-contain video, .studio-stage.fit-contain canvas { object-fit: contain; }
    .studio-stage canvas { pointer-events: none; }

    .studio-hud {
        position: absolute; top: 12px; left: 12px; right: 12px;
        display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;
        pointer-events: none; z-index: 4;
    }
    .hud-stack { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .hud-pill {
        padding: 3px 8px;
        border-radius: 3px;
        font-family: var(--adm-mono);
        font-size: 0.85rem;
        font-variant-numeric: tabular-nums;
        background: rgba(15, 34, 56, 0.88);
        color: #e2e8f0;
        border: 1px solid rgba(88, 202, 243, 0.28);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .hud-pill.ok { color: #86efac; }
    .hud-pill.warn { color: var(--adm-sun); }

    .hud-live-test {
        position: absolute; bottom: 12px; left: 12px; right: 52px;
        background: rgba(15, 34, 56, 0.92);
        color: #fff;
        padding: 10px 14px;
        border-radius: 4px;
        display: none; align-items: center; justify-content: space-between; gap: 14px;
        z-index: 4;
        border: 1px solid rgba(88, 202, 243, 0.28);
    }
    .hud-live-label {
        font-family: var(--adm-mono);
        font-size: 1.45rem;
        font-weight: 500;
        font-variant-numeric: tabular-nums;
        color: #fff;
        min-width: 56px;
        text-align: center;
    }

    .stage-fs-controls { position: absolute; bottom: 12px; right: 12px; z-index: 5; display: flex; gap: 6px; }
    .stage-icon-btn {
        width: 32px; height: 32px; border-radius: 3px;
        border: 1px solid rgba(88, 202, 243, 0.28);
        background: rgba(15, 34, 56, 0.88); color: #e2e8f0;
        cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
    }
    .studio-countdown {
        position: absolute; inset: 0; display: none; align-items: center; justify-content: center;
        font-size: 5rem; font-weight: 500; color: var(--adm-sun);
        font-family: var(--adm-mono);
        background: rgba(15, 34, 56, 0.5);
        z-index: 6; pointer-events: none;
    }

    /* Step Timeline & Actions */
    .steps-panel-v2 { display: none; margin-top: 12px; }
    .steps-panel-head {
        display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;
    }
    .steps-meta-inputs { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .steps-name-input {
        padding: 0 10px; height: 32px;
        border: 1px solid var(--adm-hairline-strong); border-radius: 4px;
        font-family: var(--adm-sans); font-size: 0.8rem; color: var(--adm-ink);
        background: var(--adm-surface); width: 200px; max-width: 100%;
    }
    .steps-countdown-toggle {
        font-size: 0.8rem; color: var(--adm-text-muted);
        display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
    }
    .steps-timeline {
        display: flex; gap: 6px; flex-wrap: wrap; min-height: 40px; align-items: center;
        padding: 6px 10px; background: var(--adm-canvas);
        border: 1px solid var(--adm-hairline); border-radius: 4px;
    }
    .step-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px; border-radius: 4px;
        background: var(--adm-surface); color: var(--adm-ink);
        border: 1px solid var(--adm-hairline-strong);
        font-size: 0.8rem; font-weight: 500;
    }
    .step-pill-num {
        font-family: var(--adm-mono); font-size: 0.74rem; color: var(--adm-pos);
    }
    .steps-actions-v2 {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 12px;
    }
    .btn-capture-primary {
        background: var(--adm-ink); color: #fff; border: 1px solid var(--adm-ink);
        border-radius: 4px; height: 36px; padding: 0 14px;
        font-family: var(--adm-sans); font-weight: 500; font-size: 0.84rem;
        display: inline-flex; align-items: center; gap: 8px; cursor: pointer;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.14), 0 2px 0 #091523;
    }
    .btn-capture-primary:disabled { opacity: 0.45; cursor: not-allowed; }
    .btn-capture-primary .keycap {
        background: rgba(255, 255, 255, 0.16); color: #e2e8f0;
        font-family: var(--adm-mono); font-size: 0.72rem;
        padding: 1px 6px; border-radius: 2px;
    }
    .btn-save-steps-v2 {
        background: var(--adm-surface); color: var(--adm-ink);
        border: 1px solid var(--adm-hairline-strong);
        border-radius: 4px; height: 36px; padding: 0 14px;
        font-family: var(--adm-sans); font-weight: 500; font-size: 0.84rem;
        display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
        box-shadow: 0 1px 0 rgba(22, 50, 79, 0.06);
    }
    .btn-save-steps-v2:disabled { opacity: 0.45; cursor: not-allowed; }
    .quiet-link-btn {
        border: none; background: transparent; color: var(--adm-text-muted);
        font-family: var(--adm-sans); font-size: 0.8rem; font-weight: 500;
        padding: 4px 8px; border-radius: 3px; cursor: pointer;
    }
    .quiet-link-btn.danger { color: var(--adm-neg); }
    .quiet-link-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    @media (hover: hover) and (pointer: fine) {
        .quiet-link-btn:hover:not(:disabled) { background: var(--adm-raised); color: var(--adm-ink); }
        .quiet-link-btn.danger:hover:not(:disabled) { background: #fef2f2; }
    }

    .studio-btn-row {
        display: flex; gap: 8px; flex-wrap: wrap;
    }
    .btn:disabled { opacity: 0.45; cursor: not-allowed; }
    .btn-danger { background: #fef2f2; color: var(--adm-neg); border: 1px solid #fecaca; }

    .studio-msg {
        margin-top: 12px; font-size: 0.8125rem; font-weight: 500;
        min-height: 1.4em; color: var(--adm-text-muted);
        padding: 8px 12px; border-radius: 4px; background: var(--adm-canvas);
        border: 1px solid var(--adm-hairline);
    }
    .studio-msg.ok { color: #166534; background: #f0fdf4; border-color: #bbf7d0; }
    .studio-msg.err { color: #991b1b; background: #fef2f2; border-color: #fecaca; }

    .burst-track {
        margin-top: 10px;
        display: none;
    }
    .burst-fill {
        width: 0;
        transition: width 0.08s linear;
    }

    .test-result {
        display: flex; align-items: center; gap: 16px;
        padding: 12px 14px; background: var(--adm-canvas);
        border-radius: 4px; border: 1px solid var(--adm-hairline);
    }
    .test-label {
        font-size: 1.35rem; font-weight: 500; font-family: var(--adm-mono);
        font-variant-numeric: tabular-nums; min-width: 72px; text-align: center;
        color: var(--adm-ink); line-height: 1.1;
    }
    .test-bar { flex: 1; }
    .test-bar > div { width: 0; background: var(--adm-pos); }
    .test-bar.adm-pixel-track--dark > div { background: #22c55e; }

    /* Right Sign Picker */
    .chip-scroll {
        display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px; scrollbar-width: thin;
    }
    .sign-chip {
        border: 1px solid var(--adm-hairline-strong);
        background: var(--adm-surface);
        color: var(--adm-ink);
        padding: 4px 10px; border-radius: 4px;
        font-size: 0.78rem; font-weight: 500; cursor: pointer;
        white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;
    }
    .sign-chip.active { background: var(--adm-ink); border-color: var(--adm-ink); color: #fff; }
    .sign-chip .chip-count {
        font-family: var(--adm-mono); font-size: 0.72rem; font-variant-numeric: tabular-nums; opacity: 0.72;
    }

    .filter-tablist {
        display: flex; gap: 16px; border-bottom: 1px solid var(--adm-hairline);
        margin-bottom: 10px; overflow-x: auto;
    }
    .filter-tab-btn {
        position: relative; flex: none; border: none; background: transparent;
        padding: 6px 0; font-family: var(--adm-sans); font-size: 0.8rem; font-weight: 500;
        color: var(--adm-text-muted); cursor: pointer;
    }
    .filter-tab-btn.active { color: var(--adm-ink); }
    .filter-tab-btn.active::after {
        content: ""; position: absolute; inset: auto 0 0; border-block-end: 2px solid var(--adm-ink);
    }

    .sample-row {
        display: flex; justify-content: space-between; align-items: center; gap: 8px;
        padding: 8px 10px; background: var(--adm-surface); border-radius: 4px;
        font-size: 0.84rem; border: 1px solid var(--adm-hairline); cursor: pointer;
    }
    @media (hover: hover) and (pointer: fine) {
        .sample-row:hover { background: var(--adm-canvas); }
    }
    .sample-row.active-target { background: var(--adm-raised); border-color: var(--adm-ink); }
    .icon-btn {
        border: none; background: transparent; color: var(--adm-text-muted);
        cursor: pointer; padding: 4px 6px; border-radius: 3px;
    }
    @media (hover: hover) and (pointer: fine) {
        .icon-btn:hover { color: var(--adm-neg); background: #fef2f2; }
    }

    .studio-disclosure-card { padding: 0; overflow: hidden; }
    .studio-disclosure { border-bottom: 1px solid var(--adm-hairline); }
    .studio-disclosure:last-child { border-bottom: none; }
    .studio-disclosure summary {
        list-style: none; padding: 12px 16px; font-size: 0.84rem; font-weight: 500;
        color: var(--adm-ink); cursor: pointer; display: flex; align-items: center; justify-content: space-between;
    }
    .studio-disclosure summary::-webkit-details-marker { display: none; }
    .studio-disclosure summary.danger-summary { color: var(--adm-neg); }
    .studio-disclosure-body { padding: 0 16px 14px; }

    /* Popover */
    .camera-toolbar {
        display: flex; flex-direction: column; gap: 10px;
        padding: 12px; background: var(--adm-canvas);
        border: 1px solid var(--adm-hairline); border-radius: 4px;
    }
    .camera-toolbar-group { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .rec-settings-box {
        background: var(--adm-canvas); border: 1px solid var(--adm-hairline);
        border-radius: 4px; padding: 12px;
    }
    .rec-settings-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(145px, 1fr));
        gap: 10px; margin-top: 10px;
    }
    .rec-field label {
        display: block; font-size: 0.75rem; font-weight: 500; color: var(--adm-text-muted); margin-bottom: 4px;
    }
    .rec-field input[type="number"], .rec-field select {
        width: 100%; height: 34px; padding: 0 8px; border-radius: 4px;
        border: 1px solid var(--adm-hairline-strong); font-family: var(--adm-sans);
        font-size: 0.82rem; background: var(--adm-surface);
    }
    .kbd-tag {
        display: inline-block; padding: 1px 5px; font-size: 0.72rem;
        background: var(--adm-raised); color: var(--adm-ink);
        border: 1px solid var(--adm-hairline-strong); border-radius: 2px; font-family: var(--adm-mono);
    }
    #studio-settings-popover {
        width: min(560px, calc(100vw - 32px)); max-height: 82dvh; overflow-y: auto;
        overscroll-behavior: contain;
        padding: 20px; border: 1px solid var(--adm-hairline-strong); border-radius: 6px;
        background: var(--adm-surface); box-shadow: 0 8px 24px rgba(15, 34, 56, 0.16); margin: auto;
    }
    #studio-settings-popover:is(:popover-open, .\:popover-open) { display: block; }
    #studio-settings-popover::backdrop {
        background: rgba(15, 34, 56, 0.42);
        overflow: hidden;
        overscroll-behavior: contain;
    }

    @media (pointer: coarse) {
        .cam-tool-btn, .seg-btn, .sign-chip, .stage-icon-btn, .btn-capture-primary, .btn-save-steps-v2 { min-height: 44px; }
    }
</style>
@endpush

@section('content')
<div class="studio-header">
    <div>
        <h1>Studio latihan AI</h1>
        <div class="studio-topbar-meta" style="margin-top: 2px;">
            <span id="stat-total-samples">{{ $totalSamples }}</span> sampel &middot;
            <span id="stat-total-static">{{ $totalStatic }}</span> statik &middot;
            <span id="stat-total-seq">{{ $totalSequence }}</span> DTW &middot;
            <span id="stat-total-steps">{{ $totalSteps ?? 0 }}</span> langkah
        </div>
    </div>

    <div class="studio-header-actions" role="toolbar" aria-label="Tindakan studio">
        <button type="button" class="btn btn-outline btn-sm" popovertarget="studio-settings-popover" id="btn-open-settings">
            Tetapan &#9662;
        </button>
        <a href="{{ route('admin.ai.export') }}" class="btn btn-primary btn-sm" id="btn-export">
            Eksport CSV
        </a>
    </div>
</div>

{{-- NATIVE POPOVER: CAMERA & RECORDING SETTINGS (On-Demand Disclosure) --}}
<div id="studio-settings-popover" popover="auto" aria-label="Tetapan kamera dan rakaman">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.9rem;">
        <h2 style="font-family: var(--font-display); font-weight: 600; font-size: 1.05rem; margin: 0; color: var(--primary);">Tetapan kamera &amp; rakaman</h2>
        <button type="button" class="icon-btn" popovertarget="studio-settings-popover" popovertargetaction="hide" aria-label="Tutup tetapan">&times;</button>
    </div>

    <div style="display: flex; flex-direction: column; gap: 0.9rem;">
        <div>
            <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Peranti &amp; paparan kamera</div>
            <div class="camera-toolbar" style="margin-bottom: 0;">
                <div class="camera-toolbar-group">
                    <select id="camera-device-select" class="cam-select-compact" title="Pilih peranti kamera">
                        <option value="">Kamera Lalai (Auto)</option>
                    </select>
                    <button type="button" class="cam-tool-btn" id="btn-refresh-devices" title="Imbas semula senarai kamera" aria-label="Imbas semula senarai kamera">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>
                <div class="camera-toolbar-group">
                    <div class="seg-group" title="Susun atur ruang kerja">
                        <button type="button" class="seg-btn active" id="btn-layout-split">Bersebelahan</button>
                        <button type="button" class="seg-btn" id="btn-layout-wide">Lebar</button>
                    </div>
                    <div class="seg-group" title="Nisbah Skrin Kamera">
                        <button type="button" class="seg-btn active" data-ratio="4-3">4:3</button>
                        <button type="button" class="seg-btn" data-ratio="16-9">16:9</button>
                        <button type="button" class="seg-btn" data-ratio="1-1">1:1</button>
                        <button type="button" class="seg-btn" data-ratio="auto">Auto</button>
                    </div>
                </div>
                <div class="camera-toolbar-group">
                    <button type="button" class="cam-tool-btn" id="btn-toggle-fit"><i class="fa-solid fa-compress"></i> <span id="fit-label">Penuh</span></button>
                    <button type="button" class="cam-tool-btn active" id="btn-toggle-mirror"><i class="fa-solid fa-arrows-left-right"></i> Cermin</button>
                    <button type="button" class="cam-tool-btn active" id="btn-toggle-skeleton"><i class="fa-solid fa-hand-sparkles"></i> Rangka</button>
                    <button type="button" class="cam-tool-btn" id="btn-toggle-joints"><i class="fa-solid fa-hashtag"></i> 0-20</button>
                    <button type="button" class="cam-tool-btn" id="btn-toggle-hands"><i class="fa-solid fa-hands"></i> <span id="hands-label">1 Tangan</span></button>
                    <button type="button" class="cam-tool-btn active" id="btn-toggle-face"><i class="fa-solid fa-face-smile"></i> AI Wajah</button>
                </div>
            </div>
        </div>

        <div class="rec-settings-box" style="margin-bottom: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" id="btn-toggle-rec-settings" style="border: none; background: transparent; font-family: var(--font-main); font-weight: 600; font-size: 0.82rem; color: var(--primary); cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem;">
                    <span>Parameter rakaman</span>
                    <span id="rec-summary-pill" style="font-family: var(--adm-mono); font-size: 0.74rem; color: var(--adm-ink); background: var(--adm-raised); border: 1px solid var(--adm-hairline-strong); padding: 1px 6px; border-radius: 2px;">Detik: 3s</span>
                    <i class="fa-solid fa-chevron-down" id="rec-settings-chevron" style="font-size: 0.72rem; color: var(--text-muted);"></i>
                </button>
                <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; font-size: 0.74rem; color: var(--text-muted);">
                    <span id="kbd-static-1"><kbd class="kbd-tag">Space</kbd> Tangkap</span>
                    <span id="kbd-static-burst"><kbd class="kbd-tag">B</kbd> Burst</span>
                    <span id="kbd-dynamic-seq" style="display: none;"><kbd class="kbd-tag">G</kbd> Gerakan</span>
                    <span><kbd class="kbd-tag">T</kbd> Uji</span>
                </div>
            </div>
            <div id="rec-settings-panel" style="display: block;">
                <div class="rec-settings-grid">
                    <div class="rec-field">
                        <label for="cfg-countdown">Kiraan detik</label>
                        <select id="cfg-countdown">
                            <option value="0">0 saat (Terus)</option>
                            <option value="1">1 saat</option>
                            <option value="3" selected>3 saat</option>
                            <option value="5">5 saat</option>
                        </select>
                    </div>
                    <div class="rec-field" id="field-burst-count">
                        <label for="cfg-burst-count">Sampel statik (Burst)</label>
                        <input type="number" id="cfg-burst-count" min="5" max="200" step="5" value="30">
                    </div>
                    <div class="rec-field" id="field-seq-mode" style="display: none;">
                        <label for="cfg-seq-mode">Mod rakaman gerakan</label>
                        <select id="cfg-seq-mode">
                            <option value="auto" selected>Auto Mula–Gerak–Tamat</option>
                            <option value="manual">Bilangan Frame Tetap (Manual)</option>
                        </select>
                    </div>
                    <div class="rec-field" id="field-seq-frames" style="display: none;">
                        <label for="cfg-seq-frames">Had frame klip (DTW)</label>
                        <input type="number" id="cfg-seq-frames" min="10" max="90" step="5" value="30">
                    </div>
                    <div class="rec-field" id="field-burst-interval">
                        <label for="cfg-burst-interval">Selang statik</label>
                        <select id="cfg-burst-interval">
                            <option value="50">50ms</option>
                            <option value="100" selected>100ms</option>
                            <option value="180">180ms</option>
                        </select>
                    </div>
                    <div class="rec-field">
                        <label for="cfg-confidence">Sensitiviti: <span id="cfg-conf-val">55%</span></label>
                        <input type="range" id="cfg-confidence" min="30" max="85" step="5" value="55" style="width: 100%; accent-color: var(--primary); margin-top: 0.35rem;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="studio-grid-v2" id="studio-grid">

    {{-- ================= LEFT: ONE FOCUSED RECORDING & TESTING CONSOLE ================= --}}
    <div class="studio-main-col">
        <div class="card console-card">

            {{-- Single-Row Underline Mode Tabs + Rakam / Uji Toggle --}}
            <div class="console-tabs-bar">
                <div class="mode-tablist" id="cam-mode-switcher" role="tablist" aria-label="Mod pengecaman kamera">
                    <button type="button" class="mode-tab-btn" id="btn-cam-mode-steps" data-cam-mode="steps" role="tab" aria-selected="false">
                        Urutan langkah
                    </button>
                    <button type="button" class="mode-tab-btn" id="btn-cam-mode-dynamic" data-cam-mode="dynamic" role="tab" aria-selected="false">
                        Dinamik (DTW)
                    </button>
                    <button type="button" class="mode-tab-btn" id="btn-cam-mode-static" data-cam-mode="static" role="tab" aria-selected="true">
                        Statik (KNN)
                    </button>
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.45rem 0;">
                    <div id="live-tester-mode-wrap" style="display: none; gap: 0.2rem; background: #f1f5f9; padding: 0.2rem; border-radius: 8px;">
                        <button type="button" class="seg-btn active" id="btn-test-mode-open" title="Uji semua templat gerakan serentak">Open-Set</button>
                        <button type="button" class="seg-btn" id="btn-test-mode-target" title="Simulasi penilaian ketat pelajar untuk isyarat sasaran">Simulasi Pelajar</button>
                    </div>
                    <button type="button" class="cam-tool-btn" id="btn-toggle-test" disabled>
                        <i class="fa-solid fa-play"></i> Uji cuba
                    </button>
                </div>
            </div>

            <div class="console-body">
                {{-- Active Target Row --}}
                <div class="console-target-row">
                    <div style="display: flex; align-items: baseline; gap: 0.65rem; flex-wrap: wrap;">
                        <h2 class="console-target-title" id="active-sign-text">A</h2>
                        <span id="active-sign-sample-count" style="font-size: 0.82rem; color: var(--text-muted); font-weight: 700;"></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.4rem;" role="toolbar" aria-label="Navigasi isyarat dan kamera">
                        <button type="button" class="cam-tool-btn" id="btn-prev-sign" aria-label="Isyarat sebelumnya" title="Isyarat sebelumnya">
                            <i class="fa-solid fa-arrow-left"></i>
                        </button>
                        <button type="button" class="cam-tool-btn" id="btn-next-sign" aria-label="Isyarat seterusnya" title="Isyarat seterusnya">
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <button type="button" class="cam-tool-btn" id="btn-reload-model" title="Segar semula model AI" aria-label="Segar semula model AI">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" id="btn-cancel-record" style="display: none;">
                            <i class="fa-solid fa-xmark"></i> Batal
                        </button>
                        <button type="button" class="cam-tool-btn" id="btn-stop-camera" style="display: none;">
                            <i class="fa-solid fa-stop"></i> Tutup kamera
                        </button>
                    </div>
                </div>

                <div id="sign-motion-hint" style="display: none; margin-bottom: 0.7rem; padding: 0.45rem 0.75rem; background: #f8fafc; border: 1px solid rgba(22, 50, 79, 0.1); border-radius: 8px; font-size: 0.78rem; color: var(--text-muted); line-height: 1.4;"></div>

                {{-- Camera Viewport Stage (Hero Moment) --}}
                <div class="studio-stage" id="studio-stage" style="margin-bottom: 0.85rem;">
                    <video id="studio-video" autoplay muted playsinline style="display: none;"></video>
                    <canvas id="studio-canvas"></canvas>

                    <div class="pixel-reticle" aria-hidden="true">
                        <span class="pixel-corner pixel-corner--tl"></span>
                        <span class="pixel-corner pixel-corner--tr"></span>
                        <span class="pixel-corner pixel-corner--bl"></span>
                        <span class="pixel-corner pixel-corner--br"></span>
                    </div>

                    <div class="studio-hud" id="studio-hud" style="display: none;">
                        <div class="hud-stack">
                            <span class="hud-pill" id="hud-cam-mode" style="background: rgba(22, 50, 79, 0.92);">Mod Statik</span>
                            <span class="hud-pill warn" id="hud-hand">Tiada tangan</span>
                            <span class="hud-pill" id="hud-face" style="display: none;">Wajah</span>
                            <span class="hud-pill" id="hud-dtw-buffer" style="display: none; background: rgba(2, 132, 199, 0.88);">DTW: 0/30f</span>
                            <span class="hud-pill target" style="display: none;">Sasaran: <span id="hud-target-label">A</span></span>
                        </div>
                        <div class="hud-stack">
                            <span class="hud-pill" id="hud-res" style="display: none;">—</span>
                            <span class="hud-pill" id="hud-fps">0 FPS</span>
                        </div>
                    </div>

                    <div class="studio-countdown" id="studio-countdown">3</div>

                    <div class="hud-live-test" id="hud-live-test">
                        <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1;">
                            <div class="hud-live-label" id="hud-test-label">—</div>
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.25rem;">
                                    <span>Padanan langsung</span>
                                    <span id="hud-test-conf">0%</span>
                                </div>
                                <div class="adm-pixel-track adm-pixel-track--dark test-bar">
                                    <div class="adm-pixel-fill" id="hud-test-bar"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="stage-fs-controls">
                        <button type="button" class="stage-icon-btn" id="btn-fullscreen" title="Skrin penuh" aria-label="Skrin penuh">
                            <i class="fa-solid fa-maximize" id="fs-icon"></i>
                        </button>
                    </div>

                    <div id="studio-placeholder" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #e2e8f0; padding: 24px; text-align: center; z-index: 3;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: #94a3b8; margin-bottom: 10px;" aria-hidden="true"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                        <div style="font-weight: 600; font-size: 1rem; margin-bottom: 4px; color: #fff;">Kamera studio sedia</div>
                        <p style="color: #94a3b8; font-size: 0.84rem; max-width: 320px; margin: 0 0 14px;">
                            Hidupkan kamera untuk merakam atau menguji isyarat secara langsung
                        </p>
                        <button type="button" class="btn btn-outline btn-sm" id="btn-start-camera" style="background: #fff; color: #16324f;">
                            Buka kamera
                        </button>
                    </div>
                </div>

                {{-- Compact Live Test Readout (kept slim inside Console) --}}
                <div class="test-result" id="live-tester-strip" style="margin-top: 0; margin-bottom: 0.85rem; display: none;">
                    <div class="test-label" id="test-label" style="font-size: 1.65rem; min-width: 76px;">—</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">
                            <span id="live-tester-title">Uji pengecaman langsung</span>
                            <span id="test-conf">0%</span>
                        </div>
                        <div class="adm-pixel-track test-bar"><div class="adm-pixel-fill" id="test-bar"></div></div>
                        <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 0.35rem;" id="test-info">Model sedia</div>
                        <span id="live-tester-desc" style="display: none;"></span>
                    </div>
                </div>

                {{-- Recording Controls: Static / DTW --}}
                <div class="studio-btn-row" id="classic-record-row">
                    <button type="button" class="btn btn-primary" id="btn-record-one" disabled>
                        <i class="fa-solid fa-record-vinyl"></i> Rakam 1 statik
                    </button>
                    <button type="button" class="btn btn-accent" id="btn-record-burst" disabled>
                        <i class="fa-solid fa-burst"></i> <span id="lbl-record-burst">Rakam statik (30)</span>
                    </button>
                    <button type="button" class="btn btn-secondary" id="btn-record-sequence" disabled style="display: none; background: var(--primary); color: #fff;">
                        <i class="fa-solid fa-film"></i> <span id="lbl-record-seq">Rakam Auto Mula–Gerak–Tamat</span>
                    </button>
                </div>

                {{-- Recording Controls: Urutan Langkah (V2 Clean Timeline + 1 Primary + 1 Secondary) --}}
                <div id="steps-recorder-panel" class="steps-panel-v2">
                    <div class="steps-panel-head" style="margin-bottom: 0.5rem;">
                        <span style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted);">Urutan semasa (2–6 pose)</span>
                        <div class="steps-meta-inputs">
                            <input type="text" id="input-step-name" class="steps-name-input" maxlength="40" placeholder="Label langkah (pilihan)">
                            <label class="steps-countdown-toggle">
                                <input type="checkbox" id="chk-step-countdown" checked> Detik 2s
                            </label>
                        </div>
                    </div>
                    <div id="steps-draft-list" class="steps-timeline" style="margin-bottom: 0;">
                        <span style="font-size: 0.82rem; color: var(--text-muted);">Belum ada langkah. Tekan Tangkap langkah atau Space.</span>
                    </div>
                    <div class="steps-actions-v2">
                        <button type="button" class="btn-capture-primary" id="btn-capture-step" disabled>
                            <span>Tangkap langkah <span id="lbl-next-step-num">1</span></span>
                            <span class="keycap">Space</span>
                        </button>
                        <button type="button" class="btn-save-steps-v2" id="btn-save-steps" disabled>
                            <i class="fa-solid fa-check"></i> Simpan urutan
                        </button>
                        <div style="display: flex; align-items: center; gap: 0.35rem; margin-inline-start: auto; flex-wrap: wrap;">
                            <button type="button" class="quiet-link-btn" id="btn-undo-step" disabled>Buang terakhir</button>
                            <button type="button" class="quiet-link-btn" id="btn-reset-steps" disabled>Set semula</button>
                            <button type="button" class="quiet-link-btn danger" id="btn-clear-steps">Padam</button>
                        </div>
                    </div>
                </div>

                <div class="adm-pixel-track burst-track" id="burst-progress"><div class="adm-pixel-fill burst-fill" id="burst-fill"></div></div>
                <div class="adm-loading-bar" id="studio-loading-bar" style="display: none; margin-top: 10px;" role="status" aria-live="polite">
                    <span class="adm-loading-label" id="studio-loading-label">LOADING...</span>
                    <div class="adm-loading-track is-animating" aria-hidden="true">
                        <span class="adm-loading-cell"></span>
                        <span class="adm-loading-cell"></span>
                        <span class="adm-loading-cell"></span>
                        <span class="adm-loading-cell"></span>
                        <span class="adm-loading-cell"></span>
                    </div>
                </div>
                <div class="studio-msg" id="record-status" style="text-align: left;">Sedia untuk merakam data</div>
            </div>
        </div>
    </div>

    {{-- ================= RIGHT: FOCUSED SIGN PICKER + COLLAPSED DISCLOSURES ================= --}}
    <div class="studio-side-col">
        <div class="card" style="padding: 1.15rem; margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; gap: 0.5rem;">
                <h2 style="font-family: var(--font-display); font-weight: 600; font-size: 1.05rem; margin: 0; color: var(--primary);">Senarai isyarat</h2>
                <button type="button" class="cam-tool-btn" id="btn-toggle-new-sign">
                    <i class="fa-solid fa-plus"></i> Baru
                </button>
            </div>

            {{-- Inline New Sign Creator --}}
            <div id="new-sign-box" style="display: none; margin-bottom: 0.8rem; padding: 0.85rem; background: #f8fafc; border-radius: 10px; border: 1px solid rgba(22, 50, 79, 0.14);">
                <div style="font-weight: 700; font-size: 0.84rem; margin-bottom: 0.55rem; color: var(--primary); display: flex; justify-content: space-between; align-items: center;">
                    <span>Daftar isyarat baru</span>
                    <button type="button" class="icon-btn" id="btn-close-new-sign" aria-label="Tutup">&times;</button>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                    <div>
                        <label for="new-sign-word" style="font-size: 0.74rem; font-weight: 700; display: block; margin-bottom: 0.2rem;">Perkataan</label>
                        <input type="text" id="new-sign-word" class="form-input" placeholder="cth: Makan" style="font-size: 0.82rem; padding: 0.38rem 0.55rem;">
                    </div>
                    <div>
                        <label for="new-sign-label" style="font-size: 0.74rem; font-weight: 700; display: block; margin-bottom: 0.2rem;">Label AI</label>
                        <input type="text" id="new-sign-label" class="form-input" placeholder="MAKAN" style="font-size: 0.82rem; padding: 0.38rem 0.55rem; text-transform: uppercase;">
                    </div>
                    <div>
                        <label for="new-sign-category" style="font-size: 0.74rem; font-weight: 700; display: block; margin-bottom: 0.2rem;">Kategori</label>
                        <select id="new-sign-category" class="form-select" style="font-size: 0.82rem; padding: 0.38rem 0.55rem;">
                            @foreach($allCategories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="new-sign-type" style="font-size: 0.74rem; font-weight: 700; display: block; margin-bottom: 0.2rem;">Jenis</label>
                        <select id="new-sign-type" class="form-select" style="font-size: 0.82rem; padding: 0.38rem 0.55rem;">
                            <option value="0">Dinamik</option>
                            <option value="1">Statik</option>
                        </select>
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-sm" id="btn-save-new-sign" style="width: 100%; margin-top: 0.6rem;">
                    <i class="fa-solid fa-check"></i> Simpan isyarat
                </button>
            </div>

            {{-- Search & Quick Label Input --}}
            <div style="position: relative; margin-bottom: 0.6rem;">
                <input type="text" id="sign-search-input" class="form-input" list="sign-datalist" placeholder="Cari atau taip label (cth: BUKU)" value="A" autocomplete="off" style="padding: 0.48rem 0.75rem 0.48rem 2rem; font-size: 0.84rem; font-weight: 700; text-transform: uppercase;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 0.7rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.8rem; pointer-events: none;"></i>
                <datalist id="sign-datalist">
                    @foreach($categories as $cat)
                        @foreach($cat->signs as $s)
                            <option value="{{ $s->label }}">{{ $s->word }} ({{ $cat->name }})</option>
                        @endforeach
                    @endforeach
                </datalist>
            </div>

            {{-- Curriculum Quick Chips (compact single-row scroll) --}}
            <div class="chip-scroll" id="quick-sign-chips" style="margin-top: 0; margin-bottom: 0.65rem;">
                @foreach($categories as $cat)
                    @foreach($cat->signs as $s)
                        <button type="button" class="sign-chip" data-label="{{ strtoupper($s->label) }}" data-cat="cat-{{ $cat->id }}" data-static="{{ $s->is_static ? '1' : '0' }}" title="{{ $s->word }} ({{ $cat->name }})">
                            <span>{{ $s->word }}</span>
                            <span class="chip-count" data-chip-count="{{ strtoupper($s->label) }}">0</span>
                        </button>
                    @endforeach
                @endforeach
            </div>

            {{-- Filter Tabs for Saved Dataset --}}
            <div class="filter-tablist" role="tablist" aria-label="Tapis senarai sampel">
                <button type="button" class="filter-tab-btn active" data-list-filter="all">Semua</button>
                <button type="button" class="filter-tab-btn" data-list-filter="steps">Langkah</button>
                <button type="button" class="filter-tab-btn" data-list-filter="seq">DTW</button>
                <button type="button" class="filter-tab-btn" data-list-filter="low">&lt;50</button>
            </div>

            <input type="text" id="sample-filter-input" class="form-input" placeholder="Tapis senarai tersimpan..." style="margin-bottom: 0.6rem; padding: 0.38rem 0.65rem; font-size: 0.8rem;">

            <div id="sample-list-msg" style="display: none; padding: 0.5rem 0.7rem; border-radius: 8px; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.6rem;"></div>
            <div style="max-height: 360px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.35rem; padding-right: 0.15rem;" id="sample-list"></div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.65rem;">Klik mana-mana isyarat untuk mula rakam</div>
        </div>

        {{-- COLLAPSED SECONDARY DISCLOSURES (On-Demand via <details>) --}}
        <div class="card studio-disclosure-card">
            <details class="studio-disclosure">
                <summary><span>&#9656; Pilih pantas ikut modul</span></summary>
                <div class="studio-disclosure-body" style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <select id="category-chip-filter" class="form-select" style="font-size: 0.82rem; padding: 0.42rem 0.65rem;">
                        <option value="ALL">Semua Modul</option>
                        @foreach($categories as $cat)
                            <option value="cat-{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <select id="sign-select-dropdown" class="form-select" style="font-size: 0.82rem; padding: 0.42rem 0.65rem;">
                        <option value="">-- Pilih Pantas --</option>
                        @foreach($categories as $cat)
                            <optgroup label="{{ $cat->name }}">
                                @foreach($cat->signs as $s)
                                    <option value="{{ strtoupper($s->label) }}" data-static="{{ $s->is_static ? '1' : '0' }}">{{ $s->word }} ({{ $s->label }})</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            </details>

            <details class="studio-disclosure">
                <summary><span>&#9656; Import dataset CSV</span></summary>
                <div class="studio-disclosure-body">
                    <p style="color: var(--text-muted); font-size: 0.78rem; margin: 0 0 0.55rem;">
                        Menyokong <code>label, lm0_x … lm20_z</code> atau <code>label, x0 … z20</code>.
                    </p>
                    <form method="POST" action="{{ route('admin.ai.import') }}" enctype="multipart/form-data" id="import-form">
                        @csrf
                        <input type="file" name="dataset" id="import-file" accept=".csv,text/csv" class="form-input" required style="margin-bottom: 0.55rem; padding: 0.42rem; font-size: 0.8rem;">
                        <div style="display: flex; gap: 0.45rem; flex-wrap: wrap;">
                            <select name="mode" id="import-mode" class="form-select" style="flex: 1; min-width: 140px; padding: 0.4rem 0.6rem; font-size: 0.8rem;">
                                <option value="append">Tambah kepada data sedia ada</option>
                                <option value="replace">Ganti label yang sama</option>
                            </select>
                            <button type="submit" class="btn btn-secondary btn-sm" id="btn-import">Import</button>
                        </div>
                    </form>
                </div>
            </details>

            <details class="studio-disclosure">
                <summary class="danger-summary"><span>&#9656; Urus &amp; padam data pukal</span></summary>
                <div class="studio-disclosure-body">
                    <p style="color: var(--text-muted); font-size: 0.78rem; margin: 0 0 0.6rem;">
                        Tindakan ini akan memadam semua sampel dalam pangkalan data AI.
                    </p>
                    <button type="button" class="btn btn-danger btn-sm" id="btn-clear-all" @if(!$totalSamples) style="display:none" @endif style="width: 100%;">
                        <i class="fa-solid fa-trash"></i> Padam semua sampel
                    </button>
                </div>
            </details>
        </div>
    </div>
</div>

{{-- NATIVE MODAL DIALOG: DELETE CONFIRMATION --}}
<dialog id="delete-modal" class="adm-dialog" closedby="any" aria-labelledby="delete-modal-title" style="max-width: 400px; text-align: center;">
    <h2 id="delete-modal-title" style="margin: 0 0 8px; font-size: 1.05rem;">Sahkan pemadaman</h2>
    <p id="delete-modal-desc" style="color: var(--adm-text-muted); font-size: 0.875rem; margin: 0 0 20px; line-height: 1.5;">Adakah anda pasti mahu memadam sampel ini?</p>
    <div style="display: flex; gap: 8px; justify-content: center;">
        <form method="dialog" style="flex: 1; display: flex;">
            <button type="submit" class="btn btn-outline btn-sm" id="btn-cancel-delete" style="width: 100%; justify-content: center;" autofocus>Batal</button>
        </form>
        <button type="button" class="btn btn-danger btn-sm" id="btn-confirm-delete" style="flex: 1; background: var(--adm-neg); color: #fff; border-color: var(--adm-neg); justify-content: center;">
            Ya, padam
        </button>
    </div>
</dialog>

@push('scripts')
<script type="module">
    import {
        isHandOnlyLabel,
        normaliseLandmarks,
        extractFaceContext,
        buildFeatureVector,
        drawFaceAnchors,
        evaluateFaceSignBoost,
        isPinkyUpShape,
        isIndexPointingShape,
        trimIdleSequenceFrames,
        extractPoseContext,
        selectHandsOrder,
        buildMultimodalVector,
        drawPoseAnchors,
        getOverlayThemeColors,
        getEffectiveFaceRule,
        KnnClassifier,
        PredictionSmoother,
        HandMotionTracker,
        MotionSegmenter,
        DynamicGestureEngine,
        StepSequenceClassifier
    } from "{{ asset('js/gesture-ai.js') }}";

    const ROUTES = {
        store: "{{ route('admin.ai.sample.store') }}",
        newSign: "{{ route('admin.ai.sign.store') }}",
        clear: "{{ route('admin.ai.clear') }}",
        dataset: "{{ route('admin.ai.dataset') }}",
    };
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const MIN_RECOMMENDED = 50;

    const $ = id => document.getElementById(id);
    const stage = $("studio-stage");
    const video = $("studio-video");
    const canvas = $("studio-canvas");
    const ctx = canvas.getContext("2d");

    let handLandmarker = null;
    let faceLandmarker = null;
    let poseLandmarker = null;
    let visionRuntimePromise = null;
    let initPromise = null;
    let faceInitPromise = null;
    let poseInitPromise = null;
    let stream = null;
    let running = false;
    let latestLandmarks = null;
    let allHandsLandmarks = [];
    let allHandsHandednesses = [];
    let enableFaceAi = true;
    let latestFaceLandmarks = null;
    let latestFaceBlendshapes = null;
    let latestFaceContext = null;
    let latestPoseLandmarks = null;
    let latestPoseContext = null;
    let lastPrimaryWrist = null;
    let prevPrimaryWristForVec = null;
    let faceFrameTick = 0;
    let auxFrameTick = 0;
    let lastHandTs = -1;
    let lastFaceTs = -1;
    let lastPoseTs = -1;
    let busy = false;
    let cancelRequested = false;

    // Flexible UI State
    let studioCameraMode = "static"; // "static" (KNN only) | "dynamic" (DTW + Motion only)
    let liveTestMode = "open-set"; // "open-set" (all templates) | "target-locked" (student simulation)
    let showSkeleton = true;
    let showJointNumbers = false;
    let isMirrored = true;
    let fitContain = false;
    let numHands = 1;
    let minConfidence = 0.55;
    let listFilterMode = "all";
    let lastDynamicTestMatch = { label: null, text: "", pct: "0%", time: 0 };

    const motionTracker = new HandMotionTracker(45);
    const segmenter = new MotionSegmenter();
    const dynamicEngine = new DynamicGestureEngine({ audience: "studio" });
    const stepClassifier = new StepSequenceClassifier();
    let counts = @json((object) $sampleCounts);
    let sequenceCounts = @json((object) $sequenceCounts);
    let stepCounts = @json((object) ($stepCounts ?? []));
    let draftSteps = [];

    // Map of label -> boolean isStatic from curriculum chips
    const signStaticMap = {};
    document.querySelectorAll(".sign-chip").forEach(chip => {
        if (chip.dataset.label) {
            signStaticMap[chip.dataset.label] = chip.dataset.static === "1";
        }
    });

    function isLabelForCurrentMode(label) {
        if (!label) return false;
        const lbl = label.toUpperCase();
        if (lbl in signStaticMap) {
            return studioCameraMode === "static" ? signStaticMap[lbl] : !signStaticMap[lbl];
        }
        // Fallback if custom label not in chips: check if it has sequence samples vs static samples
        if (studioCameraMode === "dynamic") {
            return (sequenceCounts[lbl] || 0) > 0 || !isHandOnlyLabel(lbl);
        }
        return (counts[lbl] || 0) > 0 || isHandOnlyLabel(lbl);
    }

    function applyChipAndDropdownFilters() {
        const catVal = $("category-chip-filter")?.value || "ALL";
        const wantStatic = studioCameraMode === "static" ? "1" : "0";

        document.querySelectorAll(".sign-chip").forEach(chip => {
            const matchCat = (catVal === "ALL" || chip.dataset.cat === catVal);
            const matchMode = !chip.dataset.static || chip.dataset.static === wantStatic;
            chip.style.display = (matchCat && matchMode) ? "" : "none";
        });

        const drop = $("sign-select-dropdown");
        if (drop) {
            drop.querySelectorAll("option[data-static]").forEach(opt => {
                opt.hidden = opt.dataset.static !== wantStatic;
            });
            drop.querySelectorAll("optgroup").forEach(grp => {
                const hasVisible = Array.from(grp.querySelectorAll("option")).some(o => !o.hidden);
                grp.hidden = !hasVisible;
            });
        }
    }

    function setStudioCameraMode(mode) {
        if (busy) {
            setMsg("Batalkan rakaman aktif dahulu sebelum menukar mod kamera.", "err");
            return;
        }
        studioCameraMode = (mode === "dynamic" || mode === "steps") ? mode : "static";
        const isStatic = studioCameraMode === "static";
        const isDyn = studioCameraMode === "dynamic";
        const isSteps = studioCameraMode === "steps";
        const isMultiHand = isDyn || isSteps;

        $("btn-cam-mode-static")?.setAttribute("aria-selected", String(isStatic));
        $("btn-cam-mode-dynamic")?.setAttribute("aria-selected", String(isDyn));
        $("btn-cam-mode-steps")?.setAttribute("aria-selected", String(isSteps));

        // Toggle Recording Buttons & Settings Fields per Camera Mode
        if ($("classic-record-row")) $("classic-record-row").style.display = isSteps ? "none" : "";
        $("btn-record-one").style.display = isStatic ? "" : "none";
        $("btn-record-burst").style.display = isStatic ? "" : "none";
        $("btn-record-sequence").style.display = isDyn ? "" : "none";
        if ($("steps-recorder-panel")) $("steps-recorder-panel").style.display = isSteps ? "block" : "none";

        $("field-burst-count").style.display = isStatic ? "" : "none";
        $("field-burst-interval").style.display = isStatic ? "" : "none";
        $("field-seq-mode").style.display = isDyn ? "" : "none";
        $("field-seq-frames").style.display = isDyn ? "" : "none";

        $("kbd-static-1").style.display = isStatic ? "" : "none";
        $("kbd-static-burst").style.display = isStatic ? "" : "none";
        $("kbd-dynamic-seq").style.display = isDyn ? "" : "none";

        // Auto-configure two-hand + Pose tracking in Dynamic & Steps modes
        numHands = isMultiHand ? 2 : 1;
        if ($("hands-label")) $("hands-label").textContent = `${numHands} Tangan`;
        $("btn-toggle-hands")?.classList.toggle("active", numHands === 2);
        if (handLandmarker) {
            handLandmarker.setOptions({ numHands }).catch(e => console.warn(e));
        }
        if (isMultiHand && running && !poseLandmarker) {
            initPoseModel();
        }

        // Update HUD Pills & Live Tester Header
        const hudMode = $("hud-cam-mode");
        if (hudMode) {
            hudMode.textContent = isSteps
                ? "Mod Urutan Langkah (2–6 Pose)"
                : (isDyn ? "Mod Dinamik (2 Tangan + Bahu)" : "Mod Statik (KNN)");
            hudMode.style.background = isSteps
                ? "rgba(5, 150, 105, 0.92)"
                : (isDyn ? "rgba(2, 132, 199, 0.92)" : "rgba(22, 50, 79, 0.92)");
        }
        const hudDtw = $("hud-dtw-buffer");
        if (hudDtw) {
            hudDtw.style.display = isMultiHand ? "" : "none";
        }
        const testerModeWrap = $("live-tester-mode-wrap");
        if (testerModeWrap) {
            testerModeWrap.style.display = isMultiHand ? "inline-flex" : "none";
        }

        const testerTitle = $("live-tester-title");
        const testerDesc = $("live-tester-desc");
        if (testerTitle) {
            testerTitle.innerHTML = isSteps
                ? '<i class="fa-solid fa-list-ol" style="color: var(--success);"></i> Uji Urutan Langkah (Step-by-Step)'
                : (isDyn
                    ? '<i class="fa-solid fa-film" style="color: var(--secondary-dark);"></i> Uji Kamera Dinamik (Auto Gerakan + Bahu + 2 Tangan)'
                    : '<i class="fa-solid fa-hand" style="color: var(--primary);"></i> Uji Kamera Statik (KNN Pegun Sahaja)');
        }
        if (testerDesc) {
            testerDesc.textContent = isSteps
                ? "Menguji pose mengikut turutan Langkah 1 → Langkah 2 → … secara masa nyata."
                : (isDyn
                    ? (liveTestMode === "open-set"
                        ? "Mod Open-Set: Mengesan semua templat DTW & J/Z sebaik sahaja gerakan tamat."
                        : "Simulasi Pelajar (Target-Locked): Menguji isyarat sasaran mengikut peraturan ketat murid & Mod Cermin.")
                    : "Menguji bentuk jari pegun (KNN) secara terasing tanpa gangguan gerakan DTW.");
        }

        const newSignType = $("new-sign-type");
        if (newSignType) newSignType.value = isMultiHand ? "0" : "1";

        // Reset buffers so switching modes starts fresh
        motionTracker.reset();
        segmenter.reset();
        stepClassifier.reset();
        smoother.reset();
        lastPrimaryWrist = null;
        prevPrimaryWristForVec = null;
        lastDynamicTestMatch = { label: null, text: "", pct: "0%", time: 0 };
        if (testing) updateTestDisplay("—", "0%", "0%");

        applyChipAndDropdownFilters();
        updateRecSummary();

        // Ensure active sign matches the selected camera mode
        const currentLabel = getActiveLabel();
        if (!isLabelForCurrentMode(currentLabel)) {
            const avail = getKnownLabels();
            if (avail.length > 0) setActiveLabel(avail[0]);
        } else {
            updateActiveSignInfo(true);
        }

        try { localStorage.setItem("gs_ai_studio_cam_mode", studioCameraMode); } catch {}
    }

    // Ordered list of known labels for Prev / Next stepping
    function getKnownLabels() {
        const chipLabels = Array.from(document.querySelectorAll(".sign-chip"))
            .filter(el => el.style.display !== "none")
            .map(el => el.dataset.label);
        return chipLabels.length ? chipLabels : ["A"];
    }

    // ---------- UI Helpers ----------
    function setMsg(text, type = "") {
        const el = $("record-status");
        if (!el) return;
        el.textContent = text;
        el.className = "studio-msg" + (type ? " " + type : "");
    }

    function setStudioLoading(active, label = "LOADING...") {
        const bar = $("studio-loading-bar");
        if (!bar) return;
        const lbl = $("studio-loading-label");
        if (lbl && label) lbl.textContent = label;
        bar.style.display = active ? "inline-flex" : "none";
    }

    function setRecordButtons(enabled) {
        $("btn-record-one").disabled = !enabled;
        $("btn-record-burst").disabled = !enabled;
        $("btn-record-sequence").disabled = !enabled;
        if ($("btn-capture-step")) $("btn-capture-step").disabled = !enabled || draftSteps.length >= 6;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
    }

    function updateRecSummary() {
        const cd = Number($("cfg-countdown").value || 0);
        const burst = Math.max(5, Math.min(200, Number($("cfg-burst-count").value || 30)));
        const seq = Math.max(10, Math.min(90, Number($("cfg-seq-frames").value || 30)));
        const seqMode = $("cfg-seq-mode")?.value || "auto";
        if (studioCameraMode === "dynamic") {
            const modeText = seqMode === "auto" ? "Auto Mula–Gerak–Tamat" : `Manual ${seq}f`;
            $("rec-summary-pill").innerHTML = `Mod Dinamik &bull; Detik: ${cd}s &bull; ${modeText}`;
        } else {
            $("rec-summary-pill").innerHTML = `Mod Statik &bull; Detik: ${cd}s &bull; Burst: ${burst}`;
        }
        $("lbl-record-burst").textContent = `Rakam Statik (${burst})`;
        $("lbl-record-seq").textContent = seqMode === "auto"
            ? "Rakam Auto Mula–Gerak–Tamat"
            : `Rakam Klip Gerakan DTW (${seq}f)`;
    }

    function syncChipCountsAndActive() {
        const active = getActiveLabel();
        document.querySelectorAll(".sign-chip").forEach(chip => {
            const lbl = chip.dataset.label;
            chip.classList.toggle("active", lbl === active);
            const badge = chip.querySelector("[data-chip-count]");
            if (badge) {
                const isStaticChip = chip.dataset.static !== "0";
                badge.textContent = isStaticChip
                    ? (counts[lbl] || 0)
                    : (studioCameraMode === "steps" ? (stepCounts[lbl] || 0) : (sequenceCounts[lbl] || 0));
            }
        });
    }

    function renderCounts(newCounts, total, newSeqCounts = null, newStepCounts = null) {
        if (newCounts) counts = newCounts;
        if (newSeqCounts) sequenceCounts = newSeqCounts;
        if (newStepCounts) stepCounts = newStepCounts;

        const sumStatic = Object.values(counts).reduce((a, b) => a + Number(b), 0);
        const sumSeq = Object.values(sequenceCounts).reduce((a, b) => a + Number(b), 0);
        const sumSteps = Object.values(stepCounts).reduce((a, b) => a + Number(b), 0);
        $("stat-total-samples").textContent = total ?? (sumStatic + sumSeq + sumSteps);
        $("stat-total-static").textContent = sumStatic;
        $("stat-total-seq").textContent = sumSeq;
        if ($("stat-total-steps")) $("stat-total-steps").textContent = sumSteps;

        const allLabels = [...new Set([...Object.keys(counts), ...Object.keys(sequenceCounts), ...Object.keys(stepCounts)])].sort();
        $("btn-clear-all").style.display = allLabels.length ? "" : "none";

        const searchQ = ($("sample-filter-input")?.value || "").trim().toUpperCase();
        const active = getActiveLabel();

        const filtered = allLabels.filter(label => {
            if (searchQ && !label.includes(searchQ)) return false;
            const staticN = counts[label] || 0;
            const seqN = sequenceCounts[label] || 0;
            const stepN = stepCounts[label] || 0;
            if (listFilterMode === "low") return (staticN + seqN + stepN) < MIN_RECOMMENDED && seqN < 5 && stepN < 1;
            if (listFilterMode === "seq") return seqN > 0;
            if (listFilterMode === "steps") return stepN > 0;
            return true;
        });

        const list = $("sample-list");
        if (!allLabels.length) {
            list.innerHTML = '<p style="color: var(--text-muted); font-size: 0.88rem; text-align: center; padding: 1.5rem 0;">Belum ada sampel dirakam. Mulakan rakaman pertama anda!</p>';
        } else if (!filtered.length) {
            list.innerHTML = '<p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 1.2rem 0;">Tiada label sepadan dengan tapisan semasa.</p>';
        } else {
            list.innerHTML = filtered.map(label => {
                const staticN = counts[label] || 0;
                const seqN = sequenceCounts[label] || 0;
                const stepN = stepCounts[label] || 0;
                const totalN = staticN + seqN + stepN;
                const isLow = totalN < MIN_RECOMMENDED && seqN < 5 && stepN < 1;
                const isActive = label === active;
                return `
                <div class="sample-row ${isActive ? "active-target" : ""}" data-select-label="${escapeHtml(label)}" title="Pilih ${escapeHtml(label)}">
                    <span style="display:flex; align-items:center; gap:8px; min-width:0;">
                        <span style="width:7px; height:7px; border-radius:1px; flex-shrink:0; background:${isLow ? '#94a3b8' : '#059669'};"></span>
                        <span style="font-family: var(--adm-mono); font-size: 0.9rem; color: var(--adm-ink); overflow: hidden; text-overflow: ellipsis;">${escapeHtml(label)}</span>
                    </span>
                    <span style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                        <span style="font-family: var(--adm-mono); font-size: 0.74rem; color: var(--adm-text-muted);">
                            ${[
                                stepN > 0 ? `${stepN} langkah` : '',
                                seqN > 0 ? `${seqN} DTW` : '',
                                staticN > 0 ? `${staticN} statik` : ''
                            ].filter(Boolean).join(' · ')}
                        </span>
                        <button type="button" class="icon-btn" data-delete-label="${escapeHtml(label)}" aria-label="Padam ${escapeHtml(label)}" title="Padam sampel ${escapeHtml(label)}">
                            &times;
                        </button>
                    </span>
                </div>`;
            }).join("");
        }

        syncChipCountsAndActive();
        updateActiveSignInfo(false);
    }

    async function postJson(url, body) {
        const resp = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": CSRF },
            body: JSON.stringify(body),
        });
        if (resp.status === 419) throw new Error("Sesi tamat. Sila muat semula halaman (F5).");
        if (resp.status === 401 || resp.status === 403) throw new Error("Akses ditolak. Sila log masuk semula sebagai admin.");
        let data = null;
        try { data = await resp.json(); } catch { /* non-JSON */ }
        if (!resp.ok || !data || data.success === false) {
            const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
            throw new Error(firstError || data?.message || `Ralat pelayan (${resp.status}).`);
        }
        return data;
    }

    // ---------- Camera Device Enumeration ----------
    async function populateCameraDevices() {
        if (!navigator.mediaDevices?.enumerateDevices) return;
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const cams = devices.filter(d => d.kind === "videoinput");
            const sel = $("camera-device-select");
            const currentVal = sel.value;
            sel.innerHTML = '<option value="">Kamera Lalai (Auto)</option>';
            cams.forEach((cam, idx) => {
                const opt = document.createElement("option");
                opt.value = cam.deviceId;
                opt.textContent = cam.label || `Kamera ${idx + 1}`;
                sel.appendChild(opt);
            });
            if (currentVal && Array.from(sel.options).some(o => o.value === currentVal)) {
                sel.value = currentVal;
            }
        } catch (e) {
            console.warn("Gagal menyenaraikan peranti kamera:", e);
        }
    }
    populateCameraDevices();

    // ---------- MediaPipe Initialization (Hand + Face Hybrid) ----------
    async function getVisionRuntime() {
        if (!visionRuntimePromise) {
            visionRuntimePromise = (async () => {
                const mod = await import(
                    "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/vision_bundle.mjs"
                );
                const fileset = await mod.FilesetResolver.forVisionTasks(
                    "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm"
                );
                return { mod, fileset };
            })().catch(err => {
                visionRuntimePromise = null;
                throw err;
            });
        }
        return visionRuntimePromise;
    }

    async function initFaceModel() {
        if (faceLandmarker) return faceLandmarker;
        if (faceInitPromise) return faceInitPromise;

        faceInitPromise = (async () => {
            try {
                const hudFace = $("hud-face");
                if (hudFace && enableFaceAi) {
                    hudFace.style.display = "";
                    hudFace.textContent = "Wajah: Memuat AI...";
                    hudFace.className = "hud-pill";
                }
                const { mod, fileset } = await getVisionRuntime();
                const { FaceLandmarker } = mod;
                const faceOptions = delegate => ({
                    baseOptions: {
                        modelAssetPath: "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task",
                        delegate,
                    },
                    runningMode: "VIDEO",
                    numFaces: 1,
                    outputFaceBlendshapes: true,
                    minFaceDetectionConfidence: 0.5,
                    minTrackingConfidence: 0.5,
                });
                try {
                    faceLandmarker = await FaceLandmarker.createFromOptions(fileset, faceOptions("GPU"));
                } catch (e) {
                    console.warn("FaceLandmarker GPU gagal, guna CPU:", e);
                    faceLandmarker = await FaceLandmarker.createFromOptions(fileset, faceOptions("CPU"));
                }
                return faceLandmarker;
            } catch (err) {
                console.warn("Gagal memuatkan FaceLandmarker (mod tangan sahaja diteruskan):", err);
                const hudFace = $("hud-face");
                if (hudFace) {
                    hudFace.textContent = "Wajah: Tidak Aktif";
                    hudFace.className = "hud-pill warn";
                }
                return null;
            } finally {
                faceInitPromise = null;
            }
        })();

        return faceInitPromise;
    }

    async function initPoseModel() {
        if (poseLandmarker) return poseLandmarker;
        if (poseInitPromise) return poseInitPromise;

        poseInitPromise = (async () => {
            try {
                const { mod, fileset } = await getVisionRuntime();
                const { PoseLandmarker } = mod;
                const poseOptions = delegate => ({
                    baseOptions: {
                        modelAssetPath: "https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_lite/float16/1/pose_landmarker_lite.task",
                        delegate,
                    },
                    runningMode: "VIDEO",
                    numPoses: 1,
                    minPoseDetectionConfidence: 0.45,
                    minPosePresenceConfidence: 0.45,
                    minTrackingConfidence: 0.45,
                });
                try {
                    poseLandmarker = await PoseLandmarker.createFromOptions(fileset, poseOptions("GPU"));
                } catch (e) {
                    console.warn("PoseLandmarker GPU gagal, guna CPU:", e);
                    poseLandmarker = await PoseLandmarker.createFromOptions(fileset, poseOptions("CPU"));
                }
                return poseLandmarker;
            } catch (err) {
                console.warn("Gagal memuatkan PoseLandmarker (fallback trajektori kamera):", err);
                return null;
            } finally {
                poseInitPromise = null;
            }
        })();

        return poseInitPromise;
    }

    async function initModel() {
        if (handLandmarker) return handLandmarker;
        if (initPromise) return initPromise;

        initPromise = (async () => {
            setStudioLoading(true, "LOADING MODEL AI...");
            try {
                setMsg("Memuatkan model MediaPipe HandLandmarker...");
                const { mod, fileset } = await getVisionRuntime();
                const { HandLandmarker } = mod;
                const options = delegate => ({
                    baseOptions: {
                        modelAssetPath: "https://storage.googleapis.com/mediapipe-models/hand_landmarker/hand_landmarker/float16/1/hand_landmarker.task",
                        delegate,
                    },
                    runningMode: "VIDEO",
                    numHands: numHands,
                    minHandDetectionConfidence: minConfidence,
                    minTrackingConfidence: Math.max(0.4, minConfidence - 0.05),
                });
                try {
                    handLandmarker = await HandLandmarker.createFromOptions(fileset, options("GPU"));
                } catch (e) {
                    console.warn("GPU delegate gagal, guna CPU:", e);
                    handLandmarker = await HandLandmarker.createFromOptions(fileset, options("CPU"));
                }
                if (enableFaceAi && !faceLandmarker) {
                    initFaceModel();
                }
                if (studioCameraMode === "dynamic" && !poseLandmarker) {
                    initPoseModel();
                }
                if (running) {
                    setMsg("Kamera & Model AI aktif. Sedia untuk merakam.", "ok");
                }
                return handLandmarker;
            } catch (err) {
                console.error("Gagal memuatkan MediaPipe:", err);
                setMsg("Kamera aktif tetapi model AI gagal dimuatkan. Semak sambungan internet.", "err");
                return null;
            } finally {
                initPromise = null;
                setStudioLoading(false);
            }
        })();

        return initPromise;
    }

    async function startCamera(switchDevice = false) {
        if (running && !switchDevice) return;
        const btn = $("btn-start-camera");
        btn.disabled = true;

        if (!navigator.mediaDevices?.getUserMedia) {
            setMsg("Pelayar menyekat akses kamera. Sila buka melalui http://localhost atau https://.", "err");
            btn.disabled = false;
            return;
        }

        try {
            if (stream) {
                stream.getTracks().forEach(t => t.stop());
                stream = null;
            }

            const selectedDeviceId = $("camera-device-select").value;
            const videoConstraints = selectedDeviceId
                ? { deviceId: { exact: selectedDeviceId }, width: { ideal: 1280 }, height: { ideal: 720 } }
                : { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: "user" };

            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: videoConstraints, audio: false });
            } catch (constraintErr) {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: selectedDeviceId ? { deviceId: { ideal: selectedDeviceId } } : true,
                    audio: false,
                });
            }

            video.style.display = "block";
            video.srcObject = stream;
            try { await video.play(); } catch (playErr) { console.warn("Play notice:", playErr); }

            if (video.readyState < 2) {
                await Promise.race([
                    new Promise(res => video.addEventListener("loadeddata", res, { once: true })),
                    new Promise(res => setTimeout(res, 1500)),
                ]);
            }

            await populateCameraDevices();
            const activeTrack = stream.getVideoTracks()[0];
            const trackSettings = activeTrack?.getSettings?.() || {};
            if (trackSettings.deviceId && !$("camera-device-select").value) {
                $("camera-device-select").value = trackSettings.deviceId;
            }

            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            $("hud-res").textContent = `${canvas.width}×${canvas.height}`;
            $("studio-placeholder").style.display = "none";
            $("studio-hud").style.display = "flex";
            $("hud-face").style.display = enableFaceAi ? "" : "none";
            $("btn-stop-camera").style.display = "";
            $("btn-toggle-test").disabled = false;
            setRecordButtons(true);

            const wasRunning = running;
            running = true;
            lastVideoTime = -1;

            if (!handLandmarker) {
                setMsg("Kamera dibuka! Memuatkan pengesan sendi, bahu & wajah AI...");
                initModel();
            } else {
                if (enableFaceAi && !faceLandmarker) initFaceModel();
                if (studioCameraMode === "dynamic" && !poseLandmarker) initPoseModel();
                setMsg("Kamera aktif. Tunjukkan isyarat tangan anda.", "ok");
            }

            if (!wasRunning) {
                requestAnimationFrame(loop);
            }
        } catch (e) {
            console.error(e);
            const msg = e.name === "NotAllowedError" ? "Akses kamera ditolak. Benarkan kamera dalam tetapan pelayar."
                : e.name === "NotFoundError" ? "Tiada kamera dijumpai."
                : e.name === "NotReadableError" ? "Kamera sedang digunakan oleh aplikasi lain."
                : "Gagal memulakan kamera: " + e.message;
            setMsg(msg, "err");
        } finally {
            btn.disabled = false;
        }
    }

    function stopCamera() {
        running = false;
        cancelRequested = true;
        stopTest();
        stream?.getTracks().forEach(t => t.stop());
        stream = null;
        video.srcObject = null;
        video.style.display = "none";
        latestLandmarks = null;
        allHandsLandmarks = [];
        allHandsHandednesses = [];
        latestFaceLandmarks = null;
        latestFaceBlendshapes = null;
        latestFaceContext = null;
        latestPoseLandmarks = null;
        latestPoseContext = null;
        lastPrimaryWrist = null;
        prevPrimaryWristForVec = null;
        segmenter.reset();
        motionTracker.reset();
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        $("studio-placeholder").style.display = "flex";
        $("studio-hud").style.display = "none";
        $("btn-stop-camera").style.display = "none";
        $("btn-toggle-test").disabled = true;
        setRecordButtons(false);
        setMsg("Kamera ditutup.");
    }

    // ---------- Main Vision Loop ----------
    let lastVideoTime = -1, frames = 0, fpsTimer = performance.now();
    function loop() {
        if (!running) return;

        if (handLandmarker && video.currentTime !== lastVideoTime && video.readyState >= 2 && video.videoWidth > 0) {
            lastVideoTime = video.currentTime;
            if (canvas.width !== video.videoWidth || canvas.height !== video.videoHeight) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                $("hud-res").textContent = `${canvas.width}×${canvas.height}`;
            }

            let res = null;
            try {
                const handTs = Math.max(performance.now(), lastHandTs + 1);
                lastHandTs = handTs;
                res = handLandmarker.detectForVideo(video, handTs);
            } catch (err) {
                console.warn("Detect glitch:", err);
            }

            // Interleaved PoseLandmarker (even ticks in dynamic mode) + FaceLandmarker detection
            if (studioCameraMode === "dynamic") {
                const tick = ++auxFrameTick;
                if ((tick % 2) === 0 && poseLandmarker) {
                    try {
                        const poseTs = Math.max(performance.now(), lastPoseTs + 1);
                        lastPoseTs = poseTs;
                        const poseRes = poseLandmarker.detectForVideo(video, poseTs);
                        latestPoseLandmarks = poseRes?.landmarks?.[0] || null;
                    } catch (poseErr) {
                        console.warn("Pose detect glitch:", poseErr);
                    }
                } else if (enableFaceAi && faceLandmarker) {
                    try {
                        const faceTs = Math.max(performance.now(), lastFaceTs + 1);
                        lastFaceTs = faceTs;
                        const faceRes = faceLandmarker.detectForVideo(video, faceTs);
                        latestFaceLandmarks = faceRes?.faceLandmarks?.[0] || null;
                        latestFaceBlendshapes = faceRes?.faceBlendshapes?.[0]?.categories || null;
                    } catch (faceErr) {
                        console.warn("Face detect glitch:", faceErr);
                    }
                }
            } else if (enableFaceAi && faceLandmarker && (++faceFrameTick % 2) === 0) {
                try {
                    const faceTs = Math.max(performance.now(), lastFaceTs + 1);
                    lastFaceTs = faceTs;
                    const faceRes = faceLandmarker.detectForVideo(video, faceTs);
                    latestFaceLandmarks = faceRes?.faceLandmarks?.[0] || null;
                    latestFaceBlendshapes = faceRes?.faceBlendshapes?.[0]?.categories || null;
                } catch (faceErr) {
                    console.warn("Face detect glitch:", faceErr);
                }
            }

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            const hud = $("hud-hand");
            const hudFace = $("hud-face");

            if (res && res.landmarks && res.landmarks.length > 0) {
                allHandsLandmarks = res.landmarks;
                allHandsHandednesses = res.handednesses || [];
                prevPrimaryWristForVec = lastPrimaryWrist;
                const ordered = selectHandsOrder(allHandsLandmarks, allHandsHandednesses, "Right", lastPrimaryWrist);
                latestLandmarks = ordered.primary;
                lastPrimaryWrist = latestLandmarks?.[0] ? { x: latestLandmarks[0].x, y: latestLandmarks[0].y } : null;
            } else {
                latestLandmarks = null;
                allHandsLandmarks = [];
                allHandsHandednesses = [];
                lastPrimaryWrist = null;
                prevPrimaryWristForVec = null;
            }

            // Compute Pose & spatial Hand-to-Face anchor contexts
            latestPoseContext = (studioCameraMode === "dynamic" && latestPoseLandmarks)
                ? extractPoseContext(latestPoseLandmarks)
                : null;
            latestFaceContext = enableFaceAi
                ? extractFaceContext(latestLandmarks, latestFaceLandmarks, latestFaceBlendshapes)
                : null;

            // Update Face HUD pill
            if (hudFace) {
                if (!enableFaceAi) {
                    hudFace.style.display = "none";
                } else {
                    hudFace.style.display = "";
                    if (!faceLandmarker) {
                        hudFace.textContent = "Wajah: Memuat AI...";
                        hudFace.className = "hud-pill";
                    } else if (latestFaceContext?.hasFace) {
                        const zoneInfo = (latestFaceContext.zone && latestFaceContext.zone !== "bebas")
                            ? ` • Zon ${latestFaceContext.zoneLabel}`
                            : "";
                        hudFace.textContent = `Wajah: ${latestFaceContext.expressionLabel}${zoneInfo}`;
                        hudFace.className = "hud-pill ok";
                    } else {
                        hudFace.textContent = "Tiada wajah";
                        hudFace.className = "hud-pill warn";
                    }
                }
            }

            // Draw Pose shoulder anchors in Dynamic mode & BIM face anchors
            if (showSkeleton && studioCameraMode === "dynamic" && latestPoseContext?.hasPose) {
                drawPoseAnchors(ctx, latestPoseLandmarks, canvas.width, canvas.height, latestPoseContext);
            }
            if (showSkeleton && enableFaceAi && latestFaceLandmarks && latestFaceContext?.hasFace) {
                drawFaceAnchors(ctx, latestFaceLandmarks, canvas.width, canvas.height, latestFaceContext);
            }

            if (latestLandmarks) {
                motionTracker.push(latestLandmarks);

                const hudDtw = $("hud-dtw-buffer");
                if (hudDtw && studioCameraMode === "dynamic" && !testing) {
                    const moving = !motionTracker.isStill(0.032);
                    const poseTag = latestPoseContext?.hasPose ? "+Bahu" : "";
                    hudDtw.textContent = moving
                        ? `Gerak aktif (${allHandsLandmarks.length}T${poseTag})`
                        : `Sedia auto-klip (${allHandsLandmarks.length}T${poseTag})`;
                }

                if (showSkeleton) {
                    allHandsLandmarks.forEach((lm, handIdx) => drawSkeleton(lm, handIdx));
                }

                // Draw motion trail when J/Z active OR in Dynamic Camera Mode
                const activeLbl = getActiveLabel();
                const needJCheck = studioCameraMode !== "dynamic" || activeLbl === "J";
                const needZCheck = studioCameraMode === "dynamic" && activeLbl === "Z";
                const jCheck = needJCheck ? motionTracker.detectJMotion() : { matched: false };
                const zCheck = needZCheck ? motionTracker.detectZMotion() : { matched: false };
                const shouldDrawTrail = studioCameraMode === "dynamic"
                    ? (segmenter.state === "CAPTURING" || !motionTracker.isStill(0.032))
                    : (jCheck.matched || (isPinkyUpShape(latestLandmarks) && !motionTracker.isStill(0.04)));

                if (shouldDrawTrail) {
                    const trailJoint = activeLbl === "J" ? "pinky" : (activeLbl === "Z" ? "index" : "palm");
                    const trail = motionTracker.getTrail(trailJoint);
                    const isMatchedMotion = jCheck.matched || zCheck.matched;
                    if (trail.length > 1) {
                        const theme = getOverlayThemeColors();
                        ctx.save();
                        ctx.beginPath();
                        ctx.strokeStyle = isMatchedMotion ? theme.green : theme.blueLight;
                        ctx.lineWidth = 5;
                        ctx.lineCap = "round";
                        ctx.lineJoin = "round";
                        ctx.shadowColor = isMatchedMotion ? theme.green : theme.blueDark;
                        ctx.shadowBlur = 12;
                        trail.forEach((pt, i) => {
                            const tx = pt.x * canvas.width;
                            const ty = pt.y * canvas.height;
                            if (i === 0) ctx.moveTo(tx, ty);
                            else ctx.lineTo(tx, ty);
                        });
                        ctx.stroke();
                        ctx.restore();
                    }
                }

                hud.textContent = allHandsLandmarks.length > 1 ? `${allHandsLandmarks.length} Tangan dikesan 👐` : "Tangan dikesan ✋";
                hud.className = "hud-pill ok";
            } else {
                hud.textContent = "Tiada tangan";
                hud.className = "hud-pill warn";
            }

            if (testing && !busy) runTest();

            frames++;
            const now = performance.now();
            if (now - fpsTimer >= 1000) {
                $("hud-fps").textContent = frames + " FPS";
                frames = 0;
                fpsTimer = now;
            }
        }
        requestAnimationFrame(loop);
    }

    function drawSkeleton(lm, handIdx = 0) {
        const w = canvas.width, h = canvas.height;
        const theme = getOverlayThemeColors();
        const isPrimary = handIdx === 0;
        const keyJoints = new Set([0, 4, 8, 12, 16, 20]); // Wrist & fingertips

        ctx.save();
        ctx.strokeStyle = isPrimary ? theme.blueLight : theme.yellow;
        ctx.lineWidth = 3;
        ctx.lineCap = "round";
        ctx.lineJoin = "round";
        const connections = [
            [0,1],[1,2],[2,3],[3,4], [0,5],[5,6],[6,7],[7,8], [0,9],[9,10],[10,11],[11,12],
            [0,13],[13,14],[14,15],[15,16], [0,17],[17,18],[18,19],[19,20], [5,9],[9,13],[13,17],[0,17]
        ];
        connections.forEach(([i, j]) => {
            ctx.beginPath();
            ctx.moveTo(lm[i].x * w, lm[i].y * h);
            ctx.lineTo(lm[j].x * w, lm[j].y * h);
            ctx.stroke();
        });
        lm.forEach((p, idx) => {
            const px = p.x * w, py = p.y * h;
            const isKey = keyJoints.has(idx);
            ctx.beginPath();
            ctx.arc(px, py, isKey ? 5 : 4, 0, 2 * Math.PI);
            ctx.fillStyle = isPrimary
                ? (isKey ? theme.yellow : theme.blueLight)
                : (isKey ? theme.blueLight : theme.yellow);
            ctx.fill();
            ctx.strokeStyle = theme.navy;
            ctx.lineWidth = 1.8;
            ctx.stroke();

            if (showJointNumbers) {
                ctx.save();
                ctx.translate(px, py);
                if (isMirrored) ctx.scale(-1, 1);
                ctx.font = "bold 11px Nunito, sans-serif";
                ctx.fillStyle = "#ffffff";
                ctx.strokeStyle = theme.navy;
                ctx.lineWidth = 2.5;
                ctx.strokeText(String(idx), 6, -5);
                ctx.fillText(String(idx), 6, -5);
                ctx.restore();
            }
        });
        ctx.restore();
    }

    // ---------- Target Sign Selection ----------
    function getActiveLabel() {
        const input = $("sign-search-input");
        return input ? (input.value || "").trim().toUpperCase() : "";
    }

    function setActiveLabel(label) {
        if (!label) return;
        $("sign-search-input").value = label.trim().toUpperCase();
        updateActiveSignInfo(true);
    }

    function updateActiveSignInfo(refreshListHighlight = true) {
        const label = getActiveLabel();
        $("active-sign-text").textContent = label || "(Tiada)";
        $("hud-target-label").textContent = label || "—";

        const hintEl = $("sign-motion-hint");
        if (hintEl) {
            if (label === 'I') {
                hintEl.style.display = "block";
                hintEl.innerHTML = '<i class="fa-solid fa-hand-holding"></i> <strong>Panduan Isyarat I (Pegun):</strong> Jari kelengkeng tegak dan tangan dipegang diam tanpa bergerak.';
            } else if (label === 'J') {
                hintEl.style.display = "block";
                hintEl.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> <strong>Panduan Isyarat J (Dinamik):</strong> Lukis bentuk cangkuk "J" di udara menggunakan jari kelengkeng.';
            } else if (label && !isHandOnlyLabel(label) && enableFaceAi) {
                hintEl.style.display = "block";
                hintEl.innerHTML = studioCameraMode === "dynamic"
                    ? '<i class="fa-solid fa-face-smile"></i> <strong>Mod Dinamik Multimodal (146 ciri):</strong> Pastikan 2 tangan, bahu &amp; wajah kelihatan jelas supaya konteks lengkap dirakam.'
                    : '<i class="fa-solid fa-face-smile"></i> <strong>Mod Hibrid Tangan + Wajah (78 ciri):</strong> Pastikan wajah kelihatan dalam kamera supaya kedudukan tangan pada zon muka &amp; ekspresi BIM turut dirakam.';
            } else {
                hintEl.style.display = "none";
            }
        }

        const countEl = $("active-sign-sample-count");
        if (countEl) {
            if (!label) {
                countEl.textContent = "";
            } else {
                const sN = counts[label] || 0;
                const qN = sequenceCounts[label] || 0;
                const stN = stepCounts[label] || 0;
                if (sN > 0 || qN > 0 || stN > 0) {
                    const parts = [];
                    if (sN > 0) parts.push(`${sN} statik`);
                    if (qN > 0) parts.push(`${qN} klip DTW`);
                    if (stN > 0) parts.push(`${stN} urutan langkah`);
                    countEl.textContent = `(${parts.join(", ")})`;
                    countEl.style.color = "#059669";
                } else {
                    countEl.textContent = "(0 sampel — sedia dirakam)";
                    countEl.style.color = "#d97706";
                }
            }
        }

        syncChipCountsAndActive();
        if (refreshListHighlight) {
            document.querySelectorAll("#sample-list .sample-row").forEach(row => {
                const isAct = row.dataset.selectLabel === label;
                row.classList.toggle("active-target", isAct);
            });
        }
    }

    function stepSign(delta) {
        const labels = getKnownLabels();
        if (!labels.length) return;
        const curr = getActiveLabel();
        const idx = labels.indexOf(curr);
        const nextIdx = idx === -1 ? 0 : (idx + delta + labels.length) % labels.length;
        setActiveLabel(labels[nextIdx]);
        const activeChip = document.querySelector(`.sign-chip[data-label="${CSS.escape(labels[nextIdx])}"]`);
        activeChip?.scrollIntoView({ behavior: "smooth", inline: "center", block: "nearest" });
    }

    // ---------- Recording ----------
    function captureFeatureVector(label) {
        const useFace = enableFaceAi && !isHandOnlyLabel(label);
        return buildFeatureVector(latestLandmarks, useFace ? latestFaceContext : null);
    }

    function captureDynamicFeatureVector(label = null) {
        const useFace = enableFaceAi && (!label || !isHandOnlyLabel(label));
        return buildMultimodalVector(
            allHandsLandmarks,
            allHandsHandednesses,
            useFace ? latestFaceContext : null,
            latestPoseContext,
            prevPrimaryWristForVec
        );
    }

    async function saveSamples(label, samples) {
        const data = await postJson(ROUTES.store, { label, samples });
        renderCounts(data.counts, data.total_all, data.sequence_counts);
        classifierStale = true;
        return data;
    }

    async function recordOne() {
        if (busy) return;
        const label = getActiveLabel();
        if (!label) {
            setMsg("Sila taip atau pilih isyarat sasaran terlebih dahulu.", "err");
            $("sign-search-input").focus();
            return;
        }
        if (!latestLandmarks) {
            setMsg("Tiada tangan dikesan. Pastikan tangan jelas di hadapan kamera.", "err");
            return;
        }
        busy = true;
        setRecordButtons(false);
        try {
            const vec = captureFeatureVector(label);
            const faceNote = (vec.length >= 78 && vec[77] > 0.5) ? " + Wajah" : "";
            const data = await saveSamples(label, [vec]);
            setMsg(`✔ Sampel "${data.label}"${faceNote} dirakam (jumlah statik: ${data.counts[data.label]}).`, "ok");
        } catch (e) {
            setMsg("✖ " + e.message, "err");
        } finally {
            busy = false;
            setRecordButtons(running);
        }
    }

    const sleep = ms => new Promise(r => setTimeout(r, ms));

    async function runCountdown(overrideSeconds = null) {
        const seconds = Math.max(0, Number(overrideSeconds ?? ($("cfg-countdown").value || 0)));
        if (seconds <= 0) return;
        const cd = $("studio-countdown");
        cd.style.display = "flex";
        for (let i = seconds; i > 0; i--) {
            cd.textContent = i;
            for (let step = 0; step < 10; step++) {
                await sleep(100);
                if (!running) throw new Error("Kamera ditutup.");
                if (cancelRequested) throw new Error("Rakaman dibatalkan.");
            }
        }
        cd.style.display = "none";
    }

    async function recordBurst() {
        if (busy) return;
        const label = getActiveLabel();
        if (!label) {
            setMsg("Sila taip atau pilih isyarat sasaran terlebih dahulu.", "err");
            $("sign-search-input").focus();
            return;
        }

        const count = Math.max(5, Math.min(200, Number($("cfg-burst-count").value || 30)));
        const intervalMs = Math.max(30, Number($("cfg-burst-interval").value || 100));

        busy = true;
        cancelRequested = false;
        setRecordButtons(false);
        $("btn-cancel-record").style.display = "";
        const cd = $("studio-countdown");
        const track = $("burst-progress"), fill = $("burst-fill");

        try {
            await runCountdown();

            const samples = [];
            track.style.display = "block";
            fill.style.width = "0%";
            const deadline = performance.now() + Math.max(15000, count * intervalMs * 3);
            let lastCapturedTime = -1;

            while (samples.length < count) {
                if (!running) throw new Error("Kamera ditutup.");
                if (cancelRequested) throw new Error("Rakaman dibatalkan.");
                if (performance.now() > deadline) break;

                if (latestLandmarks && lastVideoTime !== lastCapturedTime) {
                    lastCapturedTime = lastVideoTime;
                    samples.push(captureFeatureVector(label));
                    fill.style.width = (samples.length / count * 100) + "%";
                    setMsg(`Merakam "${label}": ${samples.length} / ${count} — gerakkan sedikit sudut tangan untuk variasi`);
                } else if (!latestLandmarks) {
                    setMsg(`Menunggu tangan di kamera... (${samples.length} / ${count})`, "err");
                }
                await sleep(intervalMs);
            }

            if (!samples.length) throw new Error("Tiada tangan dikesan sepanjang rakaman.");
            const hasFaceSamples = samples.some(s => s.length >= 78 && s[77] > 0.5);
            setMsg(`Menyimpan ${samples.length} sampel statik...`);
            const data = await saveSamples(label, samples);
            setMsg(`✔ ${data.saved} sampel statik "${data.label}"${hasFaceSamples ? " (+Konteks Wajah)" : ""} disimpan (jumlah: ${data.counts[data.label]}).`, "ok");
        } catch (e) {
            setMsg("✖ " + e.message, "err");
        } finally {
            cd.style.display = "none";
            track.style.display = "none";
            $("btn-cancel-record").style.display = "none";
            busy = false;
            cancelRequested = false;
            setRecordButtons(running);
        }
    }

    async function recordSequence() {
        if (busy) return;
        const label = getActiveLabel();
        if (!label) {
            setMsg("Sila taip atau pilih isyarat sasaran terlebih dahulu.", "err");
            $("sign-search-input").focus();
            return;
        }
        if (!latestLandmarks && Number($("cfg-countdown").value || 0) === 0) {
            setMsg("Tiada tangan dikesan. Sila angkat tangan ke hadapan kamera.", "err");
            return;
        }

        const seqMode = $("cfg-seq-mode")?.value || "auto";
        const targetFrames = Math.max(10, Math.min(90, Number($("cfg-seq-frames").value || 30)));
        busy = true;
        cancelRequested = false;
        setRecordButtons(false);
        $("btn-cancel-record").style.display = "";
        const cd = $("studio-countdown");
        const track = $("burst-progress"), fill = $("burst-fill");

        try {
            await runCountdown();
            cd.style.display = "flex";
            cd.textContent = seqMode === "auto" ? "SEDIA!" : "GERAK!";
            await sleep(300);
            cd.style.display = "none";

            let capturedFrames = [];
            track.style.display = "block";
            fill.style.width = "0%";
            let lastCaptured = -1;

            if (seqMode === "auto") {
                const maxAutoMs = Math.max(1800, Math.min(3600, targetFrames * 65));
                const recSeg = new MotionSegmenter({ maxClipMs: maxAutoMs, minActiveFrames: 8, pauseMs: 280 });
                const deadline = performance.now() + 14000;
                setMsg(`Auto Mula–Gerak–Tamat "${label}": Mulakan isyarat & berhenti sekejap selepas tamat!`);

                while (performance.now() <= deadline) {
                    if (!running) throw new Error("Kamera ditutup.");
                    if (cancelRequested) throw new Error("Rakaman dibatalkan.");

                    const nowMs = performance.now();
                    if (lastVideoTime !== lastCaptured || !latestLandmarks) {
                        if (latestLandmarks) lastCaptured = lastVideoTime;
                        const vec = latestLandmarks ? captureDynamicFeatureVector(label) : null;
                        const res = recSeg.pushFrame({
                            frameVec: vec,
                            handsLm: allHandsLandmarks,
                            primaryHand: latestLandmarks,
                            faceContext: enableFaceAi ? latestFaceContext : null,
                            timestampMs: nowMs,
                        });

                        if (res.event === "started") {
                            setMsg(`Merakam gerakan "${label}" — teruskan isyarat`);
                        } else if (res.state === "CAPTURING") {
                            const pct = Math.min(95, Math.round(((res.durationMs || (nowMs - (recSeg.captureStartMs || nowMs))) / maxAutoMs) * 100));
                            fill.style.width = Math.max(15, pct) + "%";
                            setMsg(`Merakam "${label}" (${recSeg.clip.length} frame) — berhenti sekejap untuk tamat`);
                        } else if (res.event === "too_short") {
                            fill.style.width = "0%";
                            setMsg(`Gerakan terlalu singkat (<8 frame). Cuba ulang isyarat "${label}"!`, "err");
                        } else if (res.event === "completed" && Array.isArray(res.clip)) {
                            capturedFrames = res.clip;
                            fill.style.width = "100%";
                            break;
                        }
                    }
                    await sleep(25);
                }
            } else {
                const deadline = performance.now() + 12000;
                while (capturedFrames.length < targetFrames) {
                    if (!running) throw new Error("Kamera ditutup.");
                    if (cancelRequested) throw new Error("Rakaman dibatalkan.");
                    if (performance.now() > deadline) break;

                    if (latestLandmarks && lastVideoTime !== lastCaptured) {
                        lastCaptured = lastVideoTime;
                        capturedFrames.push(captureDynamicFeatureVector(label));
                        fill.style.width = (capturedFrames.length / targetFrames * 100) + "%";
                        setMsg(`Merakam klip DTW "${label}": frame ${capturedFrames.length} / ${targetFrames} (Teruskan gerakan!)`);
                    } else if (!latestLandmarks) {
                        setMsg(`Menunggu tangan... (${capturedFrames.length} / ${targetFrames})`, "err");
                    }
                    await sleep(25);
                }
            }

            if (capturedFrames.length < 8) {
                throw new Error("Gerakan terlalu pendek (<8 frame) atau tiada gerakan dikesan dalam masa had.");
            }

            const cleanedFrames = trimIdleSequenceFrames(capturedFrames, 8);
            const hasFaceFrames = cleanedFrames.some(f => f.length >= 78 && f[77] > 0.55);
            const hasTwoHands = cleanedFrames.some(f => f.length >= 146 && f[141] >= 0.5);
            const hasPose = cleanedFrames.some(f => f.length >= 146 && f[145] >= 0.5);
            setMsg(`Menyimpan klip gerakan (${cleanedFrames.length} frame 146-ciri) untuk "${label}"...`);
            const data = await postJson(ROUTES.store, {
                sample_type: "sequence",
                label,
                frames: cleanedFrames,
            });

            renderCounts(data.counts, data.total_all, data.sequence_counts || {});
            classifierStale = true;
            const tags = [
                hasTwoHands ? "2 Tangan" : null,
                hasPose ? "Bahu" : null,
                hasFaceFrames ? "Wajah" : null,
            ].filter(Boolean).join(" + ");
            setMsg(`✔ Klip gerakan "${label}" (${cleanedFrames.length}f${tags ? " • " + tags : ""}) berjaya disimpan!`, "ok");
        } catch (e) {
            setMsg("✖ " + e.message, "err");
        } finally {
            cd.style.display = "none";
            track.style.display = "none";
            $("btn-cancel-record").style.display = "none";
            busy = false;
            cancelRequested = false;
            setRecordButtons(running);
        }
    }

    // ---------- Modal Delete System ----------
    let pendingDeleteBody = null;

    function openDeleteModal(title, desc, body) {
        pendingDeleteBody = body;
        $("delete-modal-title").textContent = title;
        $("delete-modal-desc").textContent = desc;
        const confirmBtn = $("btn-confirm-delete");
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fa-solid fa-trash-can"></i> Ya, Padam';
        $("delete-modal").showModal();
    }

    function closeDeleteModal() {
        const dlg = $("delete-modal");
        if (dlg.open) dlg.close();
        pendingDeleteBody = null;
    }

    function showListMsg(text, type = "success") {
        const el = $("sample-list-msg");
        if (!el) return;
        el.textContent = text;
        el.style.display = "block";
        el.style.background = type === "success" ? "#dcfce7" : "#fee2e2";
        el.style.color = type === "success" ? "#059669" : "#d63031";
        setTimeout(() => { el.style.display = "none"; }, 5000);
    }

    async function executeDelete() {
        if (!pendingDeleteBody) return;
        const confirmBtn = $("btn-confirm-delete");
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memadam...';

        try {
            const data = await postJson(ROUTES.clear, pendingDeleteBody);
            renderCounts(data.counts, data.total_all, data.sequence_counts || {});
            classifierStale = true;
            closeDeleteModal();
            showListMsg("✔ Sampel berjaya dipadam.", "success");
            setMsg("✔ Sampel berjaya dipadam dari pangkalan data.", "ok");
        } catch (e) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fa-solid fa-trash-can"></i> Cuba Lagi';
            showListMsg("✖ " + e.message, "err");
            setMsg("✖ Ralat memadam: " + e.message, "err");
        }
    }

    window.confirmDelete = function(label) {
        openDeleteModal(
            `Padam Isyarat "${label}"?`,
            `Semua rekod sampel statik & klip DTW bagi isyarat "${label}" akan dipadam daripada pangkalan data.`,
            { label: label }
        );
    };

    window.confirmDeleteAll = function() {
        openDeleteModal(
            "Padam SEMUA Sampel?",
            "Tindakan ini akan memadam SEMUA rekod sampel dalam pangkalan data. Tindakan ini tidak boleh dikembalikan.",
            { all: true }
        );
    };

    // ---------- Live Test (KNN + DTW + HUD Overlay) ----------
    const classifier = new KnnClassifier(5);
    const smoother = new PredictionSmoother(7);
    let testing = false;
    let classifierStale = true;

    function updateTestDisplay(labelText, confText, widthPct) {
        $("test-label").textContent = labelText;
        $("test-conf").textContent = confText;
        $("test-bar").style.width = widthPct;

        $("hud-test-label").textContent = labelText;
        $("hud-test-conf").textContent = confText;
        $("hud-test-bar").style.width = widthPct;
    }

    async function loadClassifier() {
        $("test-info").textContent = "Memuatkan dataset...";
        setStudioLoading(true, "LOADING DATASET...");
        try {
            const resp = await fetch(ROUTES.dataset, { headers: { Accept: "application/json" } });
            if (!resp.ok) throw new Error("Gagal memuatkan dataset (" + resp.status + ").");
            const data = await resp.json();
            classifier.load(data.samples || []);
            dynamicEngine.setTemplates(data.sequences || []);
            stepClassifier.load(data.steps || []);
            classifierStale = false;
            const stepLabelCount = (data.steps || []).length;
            $("test-info").textContent = `${classifier.size} statik (${classifier.labels.length} label), ${dynamicEngine.size} klip DTW, ${stepLabelCount} urutan langkah`;
        } finally {
            setStudioLoading(false);
        }
    }

    async function startTest() {
        try {
            if (classifierStale) await loadClassifier();
            if (classifier.labels.length === 0 && dynamicEngine.templateLabels.length === 0 && studioCameraMode === "static") {
                $("test-info").textContent = "Pangkalan data belum mempunyai sampel untuk diuji.";
                return;
            }
            smoother.reset();
            segmenter.reset();
            lastDynamicTestMatch = { label: null, text: "", pct: "0%", time: 0 };
            testing = true;
            $("hud-live-test").style.display = "flex";
            if ($("live-tester-strip")) $("live-tester-strip").style.display = "flex";
            $("btn-toggle-test").classList.add("active");
            $("btn-toggle-test").innerHTML = '<i class="fa-solid fa-stop"></i> Henti uji';
        } catch (e) {
            $("test-info").textContent = e.message;
        }
    }

    function stopTest() {
        testing = false;
        segmenter.reset();
        $("hud-live-test").style.display = "none";
        if ($("live-tester-strip")) $("live-tester-strip").style.display = "none";
        $("btn-toggle-test").classList.remove("active");
        $("btn-toggle-test").innerHTML = '<i class="fa-solid fa-play"></i> Uji cuba';
        updateTestDisplay("—", "0%", "0%");
    }

    function runTest() {
        const activeFaceCtx = enableFaceAi ? latestFaceContext : null;
        const nowMs = performance.now();

        // =========================================================================
        // MODE 0: STEP-BY-STEP SEQUENCE TESTER (Urutan Langkah)
        // =========================================================================
        if (studioCameraMode === "steps") {
            const targetLbl = getActiveLabel();
            const vec146 = latestLandmarks ? captureDynamicFeatureVector(liveTestMode === "target-locked" ? targetLbl : null) : null;
            const res = stepClassifier.update(vec146, {
                targetLabel: targetLbl,
                mode: liveTestMode,
                nowMs,
            });
            const hudDtw = $("hud-dtw-buffer");
            if (hudDtw) {
                hudDtw.textContent = res.totalSteps > 0
                    ? `Langkah ${Math.min(res.currentStep, res.totalSteps)}/${res.totalSteps}`
                    : "Tiada templat";
            }
            if (res.completed) {
                const conf = Math.max(60, res.confidence || 85);
                lastDynamicTestMatch = {
                    label: res.label,
                    text: `${conf}% (Selesai ${res.totalSteps}/${res.totalSteps})`,
                    pct: `${conf}%`,
                    time: nowMs,
                };
                $("test-info").textContent = `Urutan langkah ${res.label} lengkap (${res.stepNames.join(" -> ")})`;
                updateTestDisplay(lastDynamicTestMatch.label, lastDynamicTestMatch.text, lastDynamicTestMatch.pct);
                return;
            }
            if (lastDynamicTestMatch.label && (nowMs - lastDynamicTestMatch.time) < 2000) {
                updateTestDisplay(lastDynamicTestMatch.label, lastDynamicTestMatch.text, lastDynamicTestMatch.pct);
                return;
            }
            if (res.status === "no_templates") {
                updateTestDisplay("—", "Belum ada urutan langkah dirakam", "0%");
                return;
            }
            const nextName = res.stepNames[res.currentStep] || `Langkah ${res.currentStep + 1}`;
            const pills = res.stepNames.map((n, i) => (i < res.currentStep ? `${n} ✓` : (i === res.currentStep ? `[${n}]` : n))).join(" → ");
            const pct = res.totalSteps > 0 ? `${Math.round((res.currentStep / res.totalSteps) * 100)}%` : "0%";
            $("test-info").textContent = `Kemajuan: ${pills}`;
            updateTestDisplay(`${res.currentStep}/${res.totalSteps}`, `Seterusnya: ${nextName} (${res.confidence}%)`, pct);
            return;
        }

        // =========================================================================
        // MODE A: DYNAMIC CAMERA TESTER (Auto Mula–Gerak–Tamat Dual-Mode Engine)
        // =========================================================================
        if (studioCameraMode === "dynamic") {
            const vec146 = latestLandmarks ? captureDynamicFeatureVector(liveTestMode === "target-locked" ? getActiveLabel() : null) : null;
            const segRes = segmenter.pushFrame({
                frameVec: vec146,
                handsLm: allHandsLandmarks,
                primaryHand: latestLandmarks,
                faceContext: activeFaceCtx,
                timestampMs: nowMs,
            });

            const hudDtw = $("hud-dtw-buffer");
            if (hudDtw) {
                hudDtw.textContent = segRes.state === "CAPTURING"
                    ? `Rakam (${segmenter.clip.length}f)`
                    : (liveTestMode === "open-set" ? "Uji: Open-Set" : `Simulasi: ${getActiveLabel() || "?"}`);
            }

            if (segRes.event === "started") {
                updateTestDisplay("...", "Mengesan gerakan (berhenti sekejap untuk nilai)", "40%");
                return;
            }

            if (segRes.event === "too_short") {
                lastDynamicTestMatch = { label: "...", text: "Gerakan terlalu singkat (<8f)", pct: "20%", time: nowMs };
                updateTestDisplay(lastDynamicTestMatch.label, lastDynamicTestMatch.text, lastDynamicTestMatch.pct);
                return;
            }

            if (segRes.event === "completed") {
                const evalRes = dynamicEngine.evaluateClip({
                    mode: liveTestMode,
                    targetLabel: getActiveLabel(),
                    clipFrames: segRes.clip,
                    handsHistory: segRes.handsHistory,
                    faceHistory: segRes.faceHistory,
                    faceAiEnabled: enableFaceAi,
                });
                const conf = Math.max(0, Math.min(100, Math.round(evalRes.confidence || 0)));
                $("test-info").textContent = evalRes.hint;

                if (evalRes.matched) {
                    lastDynamicTestMatch = {
                        label: evalRes.label,
                        text: `${conf}% (${evalRes.source.toUpperCase()})`,
                        pct: `${conf}%`,
                        time: nowMs,
                    };
                } else if (evalRes.status === "mirror") {
                    lastDynamicTestMatch = { label: "-", text: "Mod cermin (tiada klip)", pct: "0%", time: nowMs };
                } else if (evalRes.status === "need_face") {
                    lastDynamicTestMatch = { label: "-", text: "AI wajah diperlukan", pct: "0%", time: nowMs };
                } else {
                    lastDynamicTestMatch = {
                        label: evalRes.label || "-",
                        text: `${conf}% (tidak sepadan)`,
                        pct: `${conf}%`,
                        time: nowMs,
                    };
                }
                updateTestDisplay(lastDynamicTestMatch.label, lastDynamicTestMatch.text, lastDynamicTestMatch.pct);
                return;
            }

            if (segRes.state === "CAPTURING") {
                const prog = Math.min(75, 35 + Math.round((segRes.durationMs || 0) / 45));
                updateTestDisplay("...", `Merakam gerakan (${segmenter.clip.length}f)`, `${prog}%`);
                return;
            }

            if (lastDynamicTestMatch.label && (nowMs - lastDynamicTestMatch.time) < 2200) {
                updateTestDisplay(lastDynamicTestMatch.label, lastDynamicTestMatch.text, lastDynamicTestMatch.pct);
                return;
            }

            if (!latestLandmarks) {
                updateTestDisplay("—", "Tunjukkan tangan di kamera untuk ujian dinamik", "0%");
            } else if (liveTestMode === "target-locked") {
                updateTestDisplay("…", `Simulasi Pelajar [${getActiveLabel()}] — Lakukan gerakan & jeda`, "15%");
            } else {
                updateTestDisplay("…", "Open-Set Sedia — Buat isyarat gerakan & berhenti sekejap", "15%");
            }
            return;
        }

        if (!latestLandmarks) {
            smoother.push(null);
            updateTestDisplay("—", "0%", "0%");
            return;
        }

        // =========================================================================
        // MODE B: STATIC CAMERA TESTER (100% Isolated to Static KNN Signs)
        // Never triggers DTW sequences; requires steady hand posture.
        // =========================================================================
        if (!motionTracker.isStill(0.085)) {
            updateTestDisplay("…", "Tahan tangan pegun (Mod Statik)...", "25%");
            return;
        }

        if (isPinkyUpShape(latestLandmarks) && classifier.labels.includes("I")) {
            updateTestDisplay("I", "95% (Isyarat I pegun)", "95%");
            smoother.push("I");
            return;
        }

        const queryVec = buildFeatureVector(latestLandmarks, activeFaceCtx);
        const { label, confidence, usedFace } = classifier.predict(queryVec, null, activeFaceCtx);
        const stable = smoother.push(confidence >= 40 ? label : null);
        const faceBoost = (stable && !isHandOnlyLabel(stable) && activeFaceCtx?.hasFace)
            ? evaluateFaceSignBoost(stable, activeFaceCtx)
            : { bonus: 0 };
        const faceBadge = (usedFace || faceBoost.bonus !== 0) ? " + wajah" : " (statik)";
        updateTestDisplay(stable ?? "?", confidence + "%" + faceBadge, confidence + "%");
    }

    // ---------- Flexible Layout & Camera Toolbar Controls ----------
    function setWorkspaceLayout(mode) {
        const isWide = mode === "wide";
        $("studio-grid").classList.toggle("layout-wide", isWide);
        $("btn-layout-wide").classList.toggle("active", isWide);
        $("btn-layout-split").classList.toggle("active", !isWide);
        try { localStorage.setItem("gs_ai_studio_layout", mode); } catch {}
    }

    $("btn-layout-split").addEventListener("click", () => setWorkspaceLayout("split"));
    $("btn-layout-wide").addEventListener("click", () => setWorkspaceLayout("wide"));
    try {
        if (localStorage.getItem("gs_ai_studio_layout") === "wide") setWorkspaceLayout("wide");
    } catch {}

    // Aspect Ratio Switcher
    document.querySelectorAll("[data-ratio]").forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelectorAll("[data-ratio]").forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            stage.classList.remove("ratio-16-9", "ratio-1-1", "ratio-auto");
            const r = btn.dataset.ratio;
            if (r === "16-9") stage.classList.add("ratio-16-9");
            else if (r === "1-1") stage.classList.add("ratio-1-1");
            else if (r === "auto") stage.classList.add("ratio-auto");
        });
    });

    // Cover vs Contain Fit
    $("btn-toggle-fit").addEventListener("click", () => {
        fitContain = !fitContain;
        stage.classList.toggle("fit-contain", fitContain);
        $("btn-toggle-fit").classList.toggle("active", fitContain);
        $("fit-label").textContent = fitContain ? "Muat" : "Penuh";
    });

    // Mirror Toggle
    function toggleMirror() {
        isMirrored = !isMirrored;
        stage.classList.toggle("unmirrored", !isMirrored);
        $("btn-toggle-mirror").classList.toggle("active", isMirrored);
    }
    $("btn-toggle-mirror").addEventListener("click", toggleMirror);

    // Skeleton & Joint Numbers
    $("btn-toggle-skeleton").addEventListener("click", () => {
        showSkeleton = !showSkeleton;
        $("btn-toggle-skeleton").classList.toggle("active", showSkeleton);
        if (!showSkeleton) ctx.clearRect(0, 0, canvas.width, canvas.height);
    });

    $("btn-toggle-joints").addEventListener("click", () => {
        showJointNumbers = !showJointNumbers;
        $("btn-toggle-joints").classList.toggle("active", showJointNumbers);
    });

    // 1 vs 2 Hands
    $("btn-toggle-hands").addEventListener("click", async () => {
        numHands = numHands === 1 ? 2 : 1;
        $("hands-label").textContent = `${numHands} Tangan`;
        $("btn-toggle-hands").classList.toggle("active", numHands === 2);
        if (handLandmarker) {
            try { await handLandmarker.setOptions({ numHands }); } catch (e) { console.warn(e); }
        }
    });

    // Face AI (Spatial Anchor + Expression Blendshapes) Toggle
    $("btn-toggle-face").addEventListener("click", () => {
        enableFaceAi = !enableFaceAi;
        $("btn-toggle-face").classList.toggle("active", enableFaceAi);
        const hudFace = $("hud-face");
        if (enableFaceAi) {
            if (running && !faceLandmarker) initFaceModel();
            if (hudFace) hudFace.style.display = "";
        } else {
            latestFaceLandmarks = null;
            latestFaceBlendshapes = null;
            latestFaceContext = null;
            if (hudFace) hudFace.style.display = "none";
        }
        updateActiveSignInfo(false);
    });

    // Fullscreen Toggle
    $("btn-fullscreen").addEventListener("click", () => {
        if (!document.fullscreenElement) {
            stage.requestFullscreen?.().catch(e => console.warn(e));
        } else {
            document.exitFullscreen?.();
        }
    });
    document.addEventListener("fullscreenchange", () => {
        const isFs = Boolean(document.fullscreenElement);
        $("fs-icon").className = isFs ? "fa-solid fa-minimize" : "fa-solid fa-maximize";
    });

    // Camera Device Picker
    $("camera-device-select").addEventListener("change", () => {
        if (running) startCamera(true);
    });
    $("btn-refresh-devices").addEventListener("click", populateCameraDevices);

    // Recording Settings Panel Toggle & Inputs
    $("btn-toggle-rec-settings").addEventListener("click", () => {
        const panel = $("rec-settings-panel");
        const open = panel.style.display === "none";
        panel.style.display = open ? "block" : "none";
        $("rec-settings-chevron").className = open ? "fa-solid fa-chevron-up" : "fa-solid fa-chevron-down";
    });
    ["cfg-countdown", "cfg-burst-count", "cfg-seq-frames", "cfg-burst-interval", "cfg-seq-mode"].forEach(id => {
        $(id)?.addEventListener("input", updateRecSummary);
        $(id)?.addEventListener("change", updateRecSummary);
    });
    $("cfg-confidence").addEventListener("input", async e => {
        const pct = Number(e.target.value);
        $("cfg-conf-val").textContent = pct + "%";
        minConfidence = pct / 100;
        if (handLandmarker) {
            try {
                await handLandmarker.setOptions({
                    minHandDetectionConfidence: minConfidence,
                    minTrackingConfidence: Math.max(0.35, minConfidence - 0.05),
                });
            } catch (err) { console.warn(err); }
        }
    });

    // ---------- Wire Up Core Actions ----------
    $("btn-start-camera").addEventListener("click", () => startCamera(false));
    $("btn-stop-camera").addEventListener("click", stopCamera);
    $("btn-record-one").addEventListener("click", recordOne);
    $("btn-record-burst").addEventListener("click", recordBurst);
    $("btn-record-sequence").addEventListener("click", recordSequence);
    $("btn-cancel-record").addEventListener("click", () => { cancelRequested = true; });
    $("btn-toggle-test").addEventListener("click", () => (testing ? stopTest() : startTest()));
    $("btn-reload-model").addEventListener("click", async () => {
        classifierStale = true;
        try { await loadClassifier(); } catch (e) { $("test-info").textContent = e.message; }
    });
    $("btn-clear-all").addEventListener("click", window.confirmDeleteAll);
    $("btn-confirm-delete").addEventListener("click", executeDelete);
    $("delete-modal").addEventListener("close", () => { pendingDeleteBody = null; });

    // Prev / Next Sign Steppers
    $("btn-prev-sign").addEventListener("click", () => stepSign(-1));
    $("btn-next-sign").addEventListener("click", () => stepSign(1));

    // Dedicated Static vs Dynamic vs Urutan Langkah Camera Mode Switcher
    $("btn-cam-mode-static")?.addEventListener("click", () => setStudioCameraMode("static"));
    $("btn-cam-mode-dynamic")?.addEventListener("click", () => setStudioCameraMode("dynamic"));
    $("btn-cam-mode-steps")?.addEventListener("click", () => setStudioCameraMode("steps"));

    // Step-by-Step Recorder Logic (2..6 steps)
    function renderDraftSteps() {
        const list = $("steps-draft-list");
        if (!list) return;
        if (!draftSteps.length) {
            list.innerHTML = '<span style="font-size: 0.82rem; color: var(--text-muted);">Belum ada langkah ditangkap. Tekan "Tangkap Langkah" atau Space.</span>';
        } else {
            list.innerHTML = draftSteps.map((st, idx) =>
                `<span class="step-pill"><span class="step-pill-num">${idx + 1}.</span> ${escapeHtml(st.name)} <i class="fa-solid fa-check" style="font-size:0.7rem;"></i></span>`
            ).join('<span style="color:#94a3b8; font-weight:700;">→</span>');
        }
        if ($("lbl-next-step-num")) $("lbl-next-step-num").textContent = Math.min(6, draftSteps.length + 1);
        if ($("btn-capture-step")) $("btn-capture-step").disabled = !running || busy || draftSteps.length >= 6;
        if ($("btn-undo-step")) $("btn-undo-step").disabled = busy || draftSteps.length === 0;
        if ($("btn-reset-steps")) $("btn-reset-steps").disabled = busy || draftSteps.length === 0;
        if ($("btn-save-steps")) $("btn-save-steps").disabled = busy || draftSteps.length < 2;
    }

    async function captureOneStep() {
        if (busy || !running) return;
        if (draftSteps.length >= 6) {
            setMsg("Maksimum 6 langkah bagi setiap urutan.", "err");
            return;
        }
        const label = getActiveLabel();
        if (!label) {
            setMsg("Sila pilih isyarat sasaran dahulu.", "err");
            return;
        }
        busy = true;
        cancelRequested = false;
        renderDraftSteps();
        try {
            if ($("chk-step-countdown")?.checked) {
                await runCountdown(2);
            }
            if (!latestLandmarks) {
                setMsg("✖ Tiada tangan dikesan dalam kamera. Tunjukkan tangan dan cuba lagi.", "err");
                return;
            }
            const vec = captureDynamicFeatureVector(label);
            if (!vec) {
                setMsg("✖ Gagal mengekstrak ciri tangan.", "err");
                return;
            }
            const rawName = ($("input-step-name")?.value || "").trim();
            const stepName = rawName || `Langkah ${draftSteps.length + 1}`;
            draftSteps.push({ name: stepName, f: vec });
            if ($("input-step-name")) $("input-step-name").value = "";
            setMsg(`✔ ${stepName} ditangkap (${draftSteps.length}/6).`, "ok");
        } catch (e) {
            setMsg("✖ " + e.message, "err");
        } finally {
            const cd = $("studio-countdown");
            if (cd) cd.style.display = "none";
            busy = false;
            renderDraftSteps();
        }
    }

    async function saveDraftSteps() {
        if (busy || draftSteps.length < 2) return;
        const label = getActiveLabel();
        if (!label) {
            setMsg("Sila pilih isyarat sasaran dahulu.", "err");
            return;
        }
        busy = true;
        renderDraftSteps();
        try {
            const data = await postJson(ROUTES.store, {
                sample_type: "steps",
                label,
                steps: draftSteps,
            });
            draftSteps = [];
            classifierStale = true;
            renderCounts(data.counts, data.total_all, data.sequence_counts, data.step_counts);
            await loadClassifier();
            setMsg(`✔ Urutan langkah untuk ${label} (${data.steps_count} langkah) berjaya disimpan!`, "ok");
        } catch (e) {
            setMsg("✖ Gagal simpan urutan langkah: " + e.message, "err");
        } finally {
            busy = false;
            renderDraftSteps();
        }
    }

    $("btn-capture-step")?.addEventListener("click", captureOneStep);
    $("btn-undo-step")?.addEventListener("click", () => { draftSteps.pop(); renderDraftSteps(); });
    $("btn-reset-steps")?.addEventListener("click", () => { draftSteps = []; renderDraftSteps(); });
    $("btn-save-steps")?.addEventListener("click", saveDraftSteps);
    $("btn-clear-steps")?.addEventListener("click", async () => {
        const label = getActiveLabel();
        if (!label || !confirm(`Padam semua sampel Urutan Langkah untuk ${label}? (Klip DTW & Statik tidak akan dipadam.)`)) return;
        try {
            const data = await postJson(ROUTES.clear, { label, sample_type: "steps" });
            classifierStale = true;
            renderCounts(data.counts, data.total_all, data.sequence_counts, data.step_counts);
            await loadClassifier();
            setMsg(`✔ Semua urutan langkah untuk ${label} telah dipadam.`, "ok");
        } catch (e) {
            setMsg("✖ Gagal memadam urutan langkah: " + e.message, "err");
        }
    });

    // Dual-Mode Live Model Tester Switcher (Open-Set vs Simulasi Pelajar)
    function setLiveTestMode(mode) {
        liveTestMode = mode === "target-locked" ? "target-locked" : "open-set";
        $("btn-test-mode-open")?.classList.toggle("active", liveTestMode === "open-set");
        $("btn-test-mode-target")?.classList.toggle("active", liveTestMode === "target-locked");
        segmenter.reset();
        lastDynamicTestMatch = { label: null, text: "", pct: "0%", time: 0 };
        if (studioCameraMode === "dynamic" && $("live-tester-desc")) {
            $("live-tester-desc").textContent = liveTestMode === "open-set"
                ? "Mod Open-Set: Mengesan semua templat DTW & J/Z sebaik sahaja gerakan tamat."
                : "Simulasi Pelajar (Target-Locked): Menguji isyarat sasaran mengikut peraturan ketat murid & Mod Cermin.";
        }
    }
    $("btn-test-mode-open")?.addEventListener("click", () => setLiveTestMode("open-set"));
    $("btn-test-mode-target")?.addEventListener("click", () => setLiveTestMode("target-locked"));

    // Category Filter for Quick Sign Chips
    $("category-chip-filter").addEventListener("change", () => {
        applyChipAndDropdownFilters();
    });

    // Quick Sign Chips Click (Event Delegation)
    $("quick-sign-chips").addEventListener("click", e => {
        const chip = e.target.closest(".sign-chip");
        if (chip && chip.dataset.label) {
            setActiveLabel(chip.dataset.label);
        }
    });

    // Sample List Click: Select Target Sign (and auto-switch camera mode if needed) OR Delete
    $("sample-list").addEventListener("click", e => {
        const delBtn = e.target.closest("[data-delete-label]");
        if (delBtn) {
            e.stopPropagation();
            window.confirmDelete(delBtn.dataset.deleteLabel);
            return;
        }
        const row = e.target.closest("[data-select-label]");
        if (row && row.dataset.selectLabel) {
            const clickedLbl = row.dataset.selectLabel;
            if (!isLabelForCurrentMode(clickedLbl)) {
                setStudioCameraMode(studioCameraMode === "static" ? "dynamic" : "static");
            }
            setActiveLabel(clickedLbl);
        }
    });

    // Sample List Search & Filter Buttons
    $("sample-filter-input").addEventListener("input", () => renderCounts(null, null));
    document.querySelectorAll("[data-list-filter]").forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelectorAll("[data-list-filter]").forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            listFilterMode = btn.dataset.listFilter;
            renderCounts(null, null);
        });
    });

    // Synchronize Search Input with Dropdown
    $("sign-search-input").addEventListener("input", () => updateActiveSignInfo(true));
    $("sign-select-dropdown").addEventListener("change", e => {
        if (e.target.value) {
            setActiveLabel(e.target.value);
        }
    });

    // Toggle New Sign Box
    $("btn-toggle-new-sign").addEventListener("click", () => {
        const box = $("new-sign-box");
        const isHidden = box.style.display === "none";
        box.style.display = isHidden ? "block" : "none";
        if (isHidden) $("new-sign-word").focus();
    });
    $("btn-close-new-sign").addEventListener("click", () => {
        $("new-sign-box").style.display = "none";
    });
    $("new-sign-word").addEventListener("input", e => {
        $("new-sign-label").value = e.target.value.trim().replace(/\s+/g, "_").toUpperCase();
    });

    // Save New Sign via AJAX
    $("btn-save-new-sign").addEventListener("click", async () => {
        const word = ($("new-sign-word").value || "").trim();
        const label = ($("new-sign-label").value || "").trim().toUpperCase();
        const categoryId = $("new-sign-category").value;
        const isStatic = $("new-sign-type").value === "1";

        if (!word || !label) {
            setMsg("Sila masukkan Perkataan dan Label AI untuk isyarat baru.", "err");
            return;
        }

        const btn = $("btn-save-new-sign");
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        try {
            await postJson(ROUTES.newSign, {
                word,
                label,
                category_id: categoryId,
                is_static: isStatic,
            });

            // Add to datalist, dropdown, and chips strip
            const opt = document.createElement("option");
            opt.value = label;
            opt.textContent = `${word} (Baru)`;
            $("sign-datalist").appendChild(opt);

            const dropOpt = document.createElement("option");
            dropOpt.value = label;
            dropOpt.dataset.static = isStatic ? "1" : "0";
            dropOpt.textContent = `${word} (${label})`;
            $("sign-select-dropdown").appendChild(dropOpt);

            const chip = document.createElement("button");
            chip.type = "button";
            chip.className = "sign-chip";
            chip.dataset.label = label;
            chip.dataset.cat = `cat-${categoryId}`;
            chip.dataset.static = isStatic ? "1" : "0";
            chip.innerHTML = `<span>${escapeHtml(word)}</span><span class="chip-count" data-chip-count="${escapeHtml(label)}">0</span>`;
            $("quick-sign-chips").appendChild(chip);

            signStaticMap[label] = isStatic;
            setStudioCameraMode(isStatic ? "static" : "dynamic");
            setActiveLabel(label);
            $("new-sign-word").value = "";
            $("new-sign-label").value = "";
            $("new-sign-box").style.display = "none";

            showListMsg(`✔ Isyarat "${word}" (${label}) berjaya didaftarkan!`, "success");
            setMsg(`✔ Isyarat "${word}" (${label}) kini sedia untuk rakaman sampel.`, "ok");
        } catch (e) {
            setMsg("✖ Ralat menambah isyarat: " + e.message, "err");
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Simpan';
        }
    });

    $("import-form").addEventListener("submit", () => {
        $("btn-import").disabled = true;
        $("btn-import").innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengimport...';
    });

    // Keyboard Shortcuts (Flexible Hands-Free Recording)
    document.addEventListener("keydown", e => {
        if (e.code === "Escape" && busy) {
            cancelRequested = true;
            return;
        }
        if (["INPUT", "SELECT", "TEXTAREA"].includes(document.activeElement?.tagName)) return;
        if (!running) return;

        if (e.code === "Space" && !busy && document.activeElement?.tagName !== "BUTTON") {
            e.preventDefault();
            if (studioCameraMode === "steps") {
                captureOneStep();
            } else if (studioCameraMode === "dynamic") {
                recordSequence();
            } else {
                recordOne();
            }
        } else if (e.key?.toUpperCase() === "B" && !busy && studioCameraMode === "static") {
            e.preventDefault();
            recordBurst();
        } else if (e.key?.toUpperCase() === "G" && !busy && studioCameraMode === "dynamic") {
            e.preventDefault();
            recordSequence();
        } else if (e.key?.toUpperCase() === "T") {
            e.preventDefault();
            testing ? stopTest() : startTest();
        } else if (e.key?.toUpperCase() === "M") {
            e.preventDefault();
            toggleMirror();
        }
    });

    window.addEventListener("pagehide", stopCamera);
    window.addEventListener("beforeunload", () => stream?.getTracks().forEach(t => t.stop()));

    // Feature-detected Popover polyfill for older browsers (modern-web-guidance)
    if (!Object.hasOwn(HTMLElement.prototype, "popover")) {
        import("https://unpkg.com/@oddbird/popover-polyfill@latest/dist/popover.min.js").catch(() => {});
    }

    try {
        const savedMode = localStorage.getItem("gs_ai_studio_cam_mode");
        setStudioCameraMode((savedMode === "dynamic" || savedMode === "steps" || savedMode === "static") ? savedMode : "steps");
    } catch {
        setStudioCameraMode("steps");
    }
    renderDraftSteps();
    renderCounts(counts, {{ $totalSamples }}, sequenceCounts, stepCounts);
    updateActiveSignInfo(true);
</script>
@endpush
@endsection

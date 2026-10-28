@extends('layouts.app', ['title' => 'Studio Latihan AI - MediaPipe'])

@push('styles')
<style>
    /* Flexible Workspace Grid */
    .studio-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .studio-header-actions {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .studio-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.95fr);
        gap: 1.5rem;
        align-items: start;
        transition: all 0.25s ease;
    }
    .studio-grid.layout-wide {
        grid-template-columns: 1fr;
    }
    .studio-grid.layout-wide .studio-side-col {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.5rem;
        align-items: start;
    }
    .studio-grid.layout-wide .studio-side-col > .card {
        margin-bottom: 0 !important;
    }
    @media (min-width: 980px) {
        .studio-grid:not(.layout-wide) .studio-main-col {
            position: sticky;
            top: 5.2rem;
        }
    }
    @media (max-width: 980px) {
        .studio-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Segmented Pill Controls */
    .seg-group {
        display: inline-flex;
        background: #f1f5f9;
        padding: 0.2rem;
        border-radius: var(--radius-sm);
        border: 1px solid rgba(108, 92, 231, 0.14);
        gap: 0.15rem;
    }
    .seg-btn {
        border: none;
        background: transparent;
        color: var(--text-muted);
        font-family: var(--font-main);
        font-size: 0.78rem;
        font-weight: 800;
        padding: 0.32rem 0.65rem;
        border-radius: 7px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .seg-btn:hover {
        color: var(--primary);
    }
    .seg-btn.active {
        background: #fff;
        color: var(--primary);
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
    }

    /* Camera Toolbar */
    .camera-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
        padding: 0.65rem 0.85rem;
        background: #f8fafc;
        border: 1px solid var(--border-light);
        border-radius: var(--radius-md);
        margin-bottom: 0.85rem;
    }
    .camera-toolbar-group {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        flex-wrap: wrap;
    }
    .cam-tool-btn {
        border: 1px solid rgba(108, 92, 231, 0.18);
        background: #fff;
        color: var(--text-dark);
        font-family: var(--font-main);
        font-size: 0.78rem;
        font-weight: 800;
        padding: 0.35rem 0.65rem;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.15s ease;
    }
    .cam-tool-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
    .cam-tool-btn.active {
        background: #eeebff;
        border-color: var(--primary);
        color: var(--primary-dark);
    }
    .cam-select-compact {
        padding: 0.34rem 0.6rem;
        font-size: 0.8rem;
        font-weight: 700;
        border-radius: 8px;
        border: 1px solid rgba(108, 92, 231, 0.2);
        background: #fff;
        color: var(--text-dark);
        font-family: var(--font-main);
        max-width: 210px;
    }

    /* Flexible Camera Stage */
    .studio-stage {
        position: relative;
        background: #0f172a;
        border-radius: var(--radius-md);
        overflow: hidden;
        aspect-ratio: 4/3;
        width: 100%;
        max-height: 72vh;
        margin-bottom: 0.9rem;
        border: 2px solid rgba(108, 92, 231, 0.15);
        transition: aspect-ratio 0.2s ease;
    }
    .studio-stage.ratio-16-9 { aspect-ratio: 16/9; }
    .studio-stage.ratio-1-1 { aspect-ratio: 1/1; max-width: 560px; margin-left: auto; margin-right: auto; }
    .studio-stage.ratio-auto { aspect-ratio: auto; min-height: 360px; height: 52vh; }
    .studio-stage:fullscreen {
        max-height: none;
        max-width: none;
        width: 100vw;
        height: 100vh;
        border-radius: 0;
        border: none;
    }

    .studio-stage video,
    .studio-stage canvas {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
        transition: transform 0.2s ease;
    }
    .studio-stage.unmirrored video,
    .studio-stage.unmirrored canvas {
        transform: none;
    }
    .studio-stage.fit-contain video,
    .studio-stage.fit-contain canvas {
        object-fit: contain;
    }
    .studio-stage canvas {
        pointer-events: none;
    }

    /* HUD Overlays */
    .studio-hud {
        position: absolute;
        top: 0.75rem;
        left: 0.75rem;
        right: 0.75rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.5rem;
        pointer-events: none;
        z-index: 4;
    }
    .hud-stack {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-wrap: wrap;
    }
    .hud-pill {
        padding: 0.32rem 0.75rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 800;
        background: rgba(15, 23, 42, 0.76);
        color: #fff;
        backdrop-filter: blur(6px);
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .hud-pill.ok { background: rgba(22, 163, 74, 0.88); }
    .hud-pill.warn { background: rgba(217, 119, 6, 0.88); }
    .hud-pill.target { background: rgba(108, 92, 231, 0.9); }

    /* Floating Live Test HUD on Video */
    .hud-live-test {
        position: absolute;
        bottom: 0.75rem;
        left: 0.75rem;
        right: 3.6rem;
        background: rgba(15, 23, 42, 0.82);
        backdrop-filter: blur(8px);
        color: #fff;
        padding: 0.6rem 1rem;
        border-radius: var(--radius-md);
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        z-index: 4;
        border: 1px solid rgba(255, 255, 255, 0.14);
    }
    .hud-live-label {
        font-family: var(--font-display);
        font-size: 1.5rem;
        font-weight: 900;
        color: #ffeaa7;
        min-width: 64px;
        text-align: center;
    }

    /* Fullscreen Quick Action Bar inside Stage */
    .stage-fs-controls {
        position: absolute;
        bottom: 0.85rem;
        right: 0.85rem;
        z-index: 5;
        display: flex;
        gap: 0.4rem;
    }
    .stage-icon-btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(15, 23, 42, 0.7);
        color: #fff;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(6px);
        transition: all 0.15s ease;
    }
    .stage-icon-btn:hover {
        background: var(--primary);
        border-color: var(--primary);
    }

    .studio-countdown {
        position: absolute;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 5.5rem;
        font-weight: 900;
        color: #ffeaa7;
        text-shadow: 0 6px 30px rgba(0, 0, 0, 0.75);
        font-family: var(--font-display);
        background: rgba(15, 23, 42, 0.25);
        z-index: 6;
        pointer-events: none;
    }

    /* Target Sign Selector & Chips */
    .target-selector-box {
        background: #f8fafc;
        padding: 0.9rem 1rem;
        border-radius: var(--radius-md);
        margin-bottom: 0.9rem;
        border: 1px solid var(--border-light);
    }
    .chip-scroll {
        display: flex;
        gap: 0.4rem;
        overflow-x: auto;
        padding-bottom: 0.35rem;
        margin-top: 0.65rem;
        scrollbar-width: thin;
    }
    .sign-chip {
        border: 1px solid rgba(108, 92, 231, 0.18);
        background: #fff;
        color: var(--text-dark);
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 800;
        cursor: pointer;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        transition: all 0.15s ease;
    }
    .sign-chip:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
    .sign-chip.active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }
    .sign-chip .chip-count {
        font-size: 0.7rem;
        padding: 0.05rem 0.38rem;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.08);
    }
    .sign-chip.active .chip-count {
        background: rgba(255, 255, 255, 0.25);
        color: #fff;
    }

    /* Flexible Recording Settings Panel */
    .rec-settings-box {
        background: #f8fafc;
        border: 1px solid var(--border-light);
        border-radius: var(--radius-md);
        padding: 0.85rem 1rem;
        margin-bottom: 0.85rem;
    }
    .rec-settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(145px, 1fr));
        gap: 0.75rem;
        margin-top: 0.65rem;
    }
    .rec-field label {
        display: block;
        font-size: 0.75rem;
        font-weight: 800;
        color: var(--text-muted);
        margin-bottom: 0.25rem;
    }
    .rec-field input[type="number"],
    .rec-field select {
        width: 100%;
        padding: 0.4rem 0.6rem;
        border-radius: 8px;
        border: 1px solid rgba(108, 92, 231, 0.22);
        font-family: var(--font-main);
        font-size: 0.85rem;
        font-weight: 800;
        background: #fff;
    }

    /* Recording Buttons */
    .studio-btn-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(165px, 1fr));
        gap: 0.65rem;
    }
    .studio-btn-row .btn {
        width: 100%;
        padding: 0.68rem 1rem;
        font-size: 0.9rem;
        justify-content: center;
    }
    .btn:disabled { opacity: 0.48; cursor: not-allowed; transform: none; box-shadow: none; }
    .btn-danger { background: #fee2e2; color: #dc2626; border: none; }
    .btn-danger:hover { background: #fecaca; }

    .studio-msg {
        margin-top: 0.75rem;
        font-size: 0.88rem;
        font-weight: 800;
        text-align: center;
        min-height: 1.4em;
        color: var(--text-muted);
        padding: 0.45rem 0.75rem;
        border-radius: var(--radius-sm);
        background: #f8fafc;
    }
    .studio-msg.ok { color: #15803d; background: #dcfce7; }
    .studio-msg.err { color: #b91c1c; background: #fee2e2; }

    .burst-track {
        height: 8px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
        margin-top: 0.65rem;
        display: none;
    }
    .burst-fill {
        height: 100%;
        width: 0;
        background: linear-gradient(90deg, #6c5ce7, #00cec9);
        transition: width 0.08s linear;
    }

    /* Live Test Result */
    .test-result {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.9rem 1rem;
        background: #f8fafc;
        border-radius: var(--radius-md);
        margin-top: 0.8rem;
        border: 1px solid var(--border-light);
    }
    .test-label {
        font-size: 2.1rem;
        font-weight: 900;
        font-family: var(--font-display);
        min-width: 88px;
        text-align: center;
        color: #5041c9;
        word-break: break-word;
        line-height: 1.1;
    }
    .test-bar {
        flex: 1;
        height: 11px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
    }
    .test-bar > div {
        height: 100%;
        width: 0;
        background: linear-gradient(90deg, #ff7675, #fdcb6e, #00b894);
        transition: width 0.15s;
    }

    /* Interactive Sample Rows */
    .sample-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 0.8rem;
        background: #f8fafc;
        border-radius: var(--radius-sm);
        font-size: 0.88rem;
        border: 1.5px solid transparent;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .sample-row:hover {
        background: #f1f5f9;
        border-color: rgba(108, 92, 231, 0.25);
    }
    .sample-row.active-target {
        background: #f3f0ff;
        border-color: var(--primary);
    }
    .sample-row.low .badge-static { background: #fef3c7 !important; color: #d97706 !important; }
    .icon-btn {
        border: none;
        background: transparent;
        color: #94a3b8;
        cursor: pointer;
        padding: 0.28rem 0.45rem;
        border-radius: 6px;
        transition: all 0.15s ease;
    }
    .icon-btn:hover { color: #dc2626; background: #fee2e2; }

    .kbd-tag {
        display: inline-block;
        padding: 0.1rem 0.4rem;
        font-size: 0.72rem;
        font-weight: 800;
        background: #e2e8f0;
        color: #334155;
        border-radius: 5px;
        font-family: monospace;
    }
</style>
@endpush

@section('content')
{{-- HEADER & WORKSPACE MODE SWITCHER --}}
<div class="studio-header">
    <div>
        <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
            <h1 style="font-size: 1.75rem; margin: 0;">Studio Kamera AI &amp; Latihan Data</h1>
            <span class="role-badge" style="background: #e0f2fe; color: #0284c7;">Studio Fleksibel</span>
        </div>
        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 0.2rem;">
            Rakam sampel sendi 3D (Statik &amp; Klip DTW), tukar kamera/nisbah skrin secara bebas, dan uji pengecaman masa nyata.
        </p>
    </div>

    <div class="studio-header-actions">
        {{-- Layout Mode Switcher --}}
        <div class="seg-group" title="Pilih susun atur ruang kerja">
            <button type="button" class="seg-btn active" id="btn-layout-split">
                <i class="fa-solid fa-table-columns"></i> Bersebelahan
            </button>
            <button type="button" class="seg-btn" id="btn-layout-wide">
                <i class="fa-solid fa-expand"></i> Fokus Kamera Lebar
            </button>
        </div>

        <a href="{{ route('admin.ai.export') }}" class="btn btn-primary btn-sm" id="btn-export">
            <i class="fa-solid fa-file-csv"></i> Eksport CSV
        </a>
    </div>
</div>

<div class="studio-grid" id="studio-grid">

    {{-- ================= LEFT / MAIN: FLEXIBLE CAMERA WORKSPACE ================= --}}
    <div class="studio-main-col">
        <div class="card" style="padding: 1.35rem; margin-bottom: 1.25rem;">

            {{-- 1. TARGET SIGN BAR (WITH STEPPER, CATEGORY FILTER & CHIPS) --}}
            <div class="target-selector-box">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.55rem; flex-wrap: wrap; gap: 0.5rem;">
                    <label for="sign-search-input" style="font-weight: 800; font-size: 0.88rem; display: flex; align-items: center; gap: 0.45rem; color: var(--text-dark);">
                        <i class="fa-solid fa-bullseye" style="color: var(--primary);"></i>
                        <span>Isyarat Sasaran Aktif:</span>
                        <strong id="active-sign-text" style="color: #6c5ce7; font-size: 0.95rem; background: #eeebff; padding: 0.1rem 0.55rem; border-radius: 6px;">A</strong>
                        <span id="active-sign-sample-count" style="font-size: 0.8rem; color: #16a34a; font-weight: 800;"></span>
                    </label>

                    <div style="display: flex; gap: 0.4rem; align-items: center;">
                        <button type="button" class="cam-tool-btn" id="btn-prev-sign" title="Isyarat Sebelumnya">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button type="button" class="cam-tool-btn" id="btn-next-sign" title="Isyarat Seterusnya">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                        <button type="button" class="cam-tool-btn" id="btn-toggle-new-sign" style="border-color: #6c5ce7; color: #6c5ce7;">
                            <i class="fa-solid fa-plus"></i> Isyarat Baru
                        </button>
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    {{-- Searchable / Custom Label Input --}}
                    <div style="flex: 1; min-width: 200px; position: relative;">
                        <input type="text"
                               id="sign-search-input"
                               class="form-input"
                               list="sign-datalist"
                               placeholder="Taip atau cari label (cth: A, B, TERIMA_KASIH)..."
                               value="A"
                               autocomplete="off"
                               style="padding: 0.5rem 0.8rem 0.5rem 2.1rem; font-weight: 800; font-size: 0.92rem; text-transform: uppercase;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem; pointer-events: none;"></i>
                    </div>

                    <datalist id="sign-datalist">
                        @foreach($categories as $cat)
                            @foreach($cat->signs as $s)
                                <option value="{{ $s->label }}">{{ $s->word }} ({{ $cat->name }})</option>
                            @endforeach
                        @endforeach
                    </datalist>

                    {{-- Module Filter for Quick Chips --}}
                    <select id="category-chip-filter" class="form-select" style="width: auto; max-width: 175px; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.75rem;" title="Tapis cip mengikut modul">
                        <option value="ALL">Semua Modul</option>
                        @foreach($categories as $cat)
                            <option value="cat-{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                        @endforeach
                    </select>

                    {{-- Quick Select Companion --}}
                    <select id="sign-select-dropdown" class="form-select" style="width: auto; max-width: 190px; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.75rem;" title="Pilih pantas isyarat">
                        <option value="">-- Pilih Pantas --</option>
                        @foreach($categories as $cat)
                            <optgroup label="{{ $cat->name }}">
                                @foreach($cat->signs as $s)
                                    <option value="{{ $s->label }}">{{ $s->word }} ({{ $s->label }})</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                {{-- One-Click Sign Chips Strip --}}
                <div class="chip-scroll" id="quick-sign-chips">
                    @foreach($categories as $cat)
                        @foreach($cat->signs as $s)
                            <button type="button"
                                    class="sign-chip"
                                    data-label="{{ strtoupper($s->label) }}"
                                    data-cat="cat-{{ $cat->id }}"
                                    title="{{ $s->word }} ({{ $cat->name }})">
                                <span>{{ $s->word }}</span>
                                <span class="chip-count" data-chip-count="{{ strtoupper($s->label) }}">0</span>
                            </button>
                        @endforeach
                    @endforeach
                </div>

                {{-- Motion Guidance Notice for I & J --}}
                <div id="sign-motion-hint" style="display: none; margin-top: 0.55rem; padding: 0.55rem 0.85rem; background: #e0f2fe; border: 1px solid #bae6fd; border-radius: var(--radius-sm); font-size: 0.8rem; color: #0369a1; line-height: 1.4;"></div>

                {{-- Inline New Sign Creator --}}
                <div id="new-sign-box" style="display: none; margin-top: 0.8rem; padding: 0.9rem; background: #ffffff; border-radius: var(--radius-md); border: 2px dashed #6c5ce7;">
                    <div style="font-weight: 800; font-size: 0.88rem; margin-bottom: 0.65rem; color: #5041c9; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fa-solid fa-plus-circle"></i> Daftar Isyarat Baru ke Kurikulum BIM</span>
                        <button type="button" class="icon-btn" id="btn-close-new-sign" style="font-size: 1.1rem; line-height: 1;" title="Tutup">&times;</button>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); gap: 0.65rem; align-items: flex-end;">
                        <div>
                            <label for="new-sign-word" style="font-size: 0.76rem; font-weight: 800; display: block; margin-bottom: 0.25rem;">Perkataan:</label>
                            <input type="text" id="new-sign-word" class="form-input" placeholder="cth: Makan" style="font-size: 0.85rem; padding: 0.42rem 0.65rem;">
                        </div>
                        <div>
                            <label for="new-sign-label" style="font-size: 0.76rem; font-weight: 800; display: block; margin-bottom: 0.25rem;">Label AI:</label>
                            <input type="text" id="new-sign-label" class="form-input" placeholder="cth: MAKAN" style="font-size: 0.85rem; padding: 0.42rem 0.65rem; text-transform: uppercase;">
                        </div>
                        <div>
                            <label for="new-sign-category" style="font-size: 0.76rem; font-weight: 800; display: block; margin-bottom: 0.25rem;">Kategori:</label>
                            <select id="new-sign-category" class="form-select" style="font-size: 0.85rem; padding: 0.42rem 0.65rem;">
                                @foreach($allCategories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="new-sign-type" style="font-size: 0.76rem; font-weight: 800; display: block; margin-bottom: 0.25rem;">Jenis:</label>
                            <select id="new-sign-type" class="form-select" style="font-size: 0.85rem; padding: 0.42rem 0.65rem;">
                                <option value="1">Statik (Pegun)</option>
                                <option value="0">Dinamik (Gerakan)</option>
                            </select>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary btn-sm" id="btn-save-new-sign" style="width: 100%; height: 36px;">
                                <i class="fa-solid fa-check"></i> Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. FLEXIBLE CAMERA CONTROLS TOOLBAR --}}
            <div class="camera-toolbar">
                <div class="camera-toolbar-group">
                    <i class="fa-solid fa-video" style="color: var(--secondary); font-size: 0.9rem;"></i>
                    <select id="camera-device-select" class="cam-select-compact" title="Pilih peranti kamera">
                        <option value="">Kamera Lalai (Auto)</option>
                    </select>
                    <button type="button" class="cam-tool-btn" id="btn-refresh-devices" title="Imbas semula senarai kamera">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>

                <div class="camera-toolbar-group">
                    {{-- Aspect Ratio Selector --}}
                    <div class="seg-group" title="Nisbah Skrin Kamera">
                        <button type="button" class="seg-btn active" data-ratio="4-3">4:3</button>
                        <button type="button" class="seg-btn" data-ratio="16-9">16:9</button>
                        <button type="button" class="seg-btn" data-ratio="1-1">1:1</button>
                        <button type="button" class="seg-btn" data-ratio="auto">Auto</button>
                    </div>

                    {{-- Fit Mode --}}
                    <button type="button" class="cam-tool-btn" id="btn-toggle-fit" title="Tukar mod paparan Penuh (Cover) atau Muat (Contain)">
                        <i class="fa-solid fa-compress"></i> <span id="fit-label">Penuh</span>
                    </button>

                    {{-- Mirror --}}
                    <button type="button" class="cam-tool-btn active" id="btn-toggle-mirror" title="Pantulan Cermin (Pintasan: M)">
                        <i class="fa-solid fa-arrows-left-right"></i> Cermin
                    </button>

                    {{-- Skeleton & Joint Numbers --}}
                    <button type="button" class="cam-tool-btn active" id="btn-toggle-skeleton" title="Papar/Sembunyi Rangka Sendi">
                        <i class="fa-solid fa-hand-sparkles"></i> Rangka
                    </button>
                    <button type="button" class="cam-tool-btn" id="btn-toggle-joints" title="Papar Indeks Sendi (0-20)">
                        <i class="fa-solid fa-hashtag"></i> 0-20
                    </button>

                    {{-- 1 vs 2 Hands --}}
                    <button type="button" class="cam-tool-btn" id="btn-toggle-hands" title="Bilangan tangan dikesan">
                        <i class="fa-solid fa-hands"></i> <span id="hands-label">1 Tangan</span>
                    </button>

                    {{-- Face AI (Spatial Anchor + Expression Blendshapes) --}}
                    <button type="button" class="cam-tool-btn active" id="btn-toggle-face" title="Pengesan Wajah & Ekspresi BIM (Zon Dahi/Mata/Pipi/Mulut & Emosi)">
                        <i class="fa-solid fa-face-smile"></i> AI Wajah
                    </button>
                </div>
            </div>

            {{-- 3. LIVE CAMERA VIEWPORT STAGE --}}
            <div class="studio-stage" id="studio-stage">
                <video id="studio-video" autoplay muted playsinline style="display: none;"></video>
                <canvas id="studio-canvas"></canvas>

                {{-- Top HUD --}}
                <div class="studio-hud" id="studio-hud" style="display: none;">
                    <div class="hud-stack">
                        <span class="hud-pill warn" id="hud-hand">Tiada tangan</span>
                        <span class="hud-pill" id="hud-face" style="background: rgba(14, 116, 144, 0.85);">Wajah: Memuat...</span>
                        <span class="hud-pill target">Sasaran: <span id="hud-target-label">A</span></span>
                    </div>
                    <div class="hud-stack">
                        <span class="hud-pill" id="hud-res">—</span>
                        <span class="hud-pill" id="hud-fps">0 FPS</span>
                    </div>
                </div>

                {{-- Countdown Overlay --}}
                <div class="studio-countdown" id="studio-countdown">3</div>

                {{-- Floating Live Prediction Overlay (Active during Uji Model) --}}
                <div class="hud-live-test" id="hud-live-test">
                    <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1;">
                        <div class="hud-live-label" id="hud-test-label">—</div>
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 800; margin-bottom: 0.25rem;">
                                <span>Padanan AI Langsung</span>
                                <span id="hud-test-conf">0%</span>
                            </div>
                            <div class="test-bar" style="height: 8px; background: rgba(255,255,255,0.2);">
                                <div id="hud-test-bar"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bottom-Right Floating Stage Buttons (Fullscreen & Quick Stop) --}}
                <div class="stage-fs-controls">
                    <button type="button" class="stage-icon-btn" id="btn-fullscreen" title="Skrin Penuh (Fullscreen)">
                        <i class="fa-solid fa-maximize" id="fs-icon"></i>
                    </button>
                </div>

                {{-- Camera Closed Placeholder --}}
                <div id="studio-placeholder" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff; padding: 1.5rem; text-align: center; z-index: 3;">
                    <div style="font-size: 3rem; margin-bottom: 0.4rem; color: var(--secondary);">📹</div>
                    <div style="font-weight: 800; font-size: 1.05rem; margin-bottom: 0.25rem;">Studio Kamera AI Sedia</div>
                    <p style="color: #94a3b8; font-size: 0.85rem; max-width: 340px; margin-bottom: 1rem;">
                        Pilih peranti kamera di atas atau terus buka kamera untuk mula merakam dan menguji isyarat.
                    </p>
                    <button type="button" class="btn btn-secondary" id="btn-start-camera">
                        <i class="fa-solid fa-play"></i> Buka Kamera Studio
                    </button>
                </div>
            </div>

            {{-- 4. FLEXIBLE RECORDING PARAMETERS BAR --}}
            <div class="rec-settings-box">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                    <button type="button" id="btn-toggle-rec-settings" style="border: none; background: transparent; font-family: var(--font-main); font-weight: 800; font-size: 0.84rem; color: var(--text-dark); cursor: pointer; display: inline-flex; align-items: center; gap: 0.45rem;">
                        <i class="fa-solid fa-sliders" style="color: var(--primary);"></i>
                        <span>Tetapan Rakaman Fleksibel</span>
                        <span id="rec-summary-pill" style="font-size: 0.75rem; color: var(--primary); background: #eeebff; padding: 0.1rem 0.5rem; border-radius: 999px;">
                            Detik: 3s &bull; Statik: 30 &bull; Gerakan: 30f
                        </span>
                        <i class="fa-solid fa-chevron-down" id="rec-settings-chevron" style="font-size: 0.75rem; color: var(--text-muted);"></i>
                    </button>

                    <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap; font-size: 0.75rem; color: var(--text-muted);">
                        <span>Pintasan:</span>
                        <span><kbd class="kbd-tag">Space</kbd> 1x</span>
                        <span><kbd class="kbd-tag">B</kbd> Statik</span>
                        <span><kbd class="kbd-tag">G</kbd> Gerakan</span>
                        <span><kbd class="kbd-tag">T</kbd> Uji</span>
                    </div>
                </div>

                <div id="rec-settings-panel" style="display: none;">
                    <div class="rec-settings-grid">
                        <div class="rec-field">
                            <label for="cfg-countdown">Kiraan Detik (Countdown)</label>
                            <select id="cfg-countdown">
                                <option value="0">0 saat (Terus Rakam)</option>
                                <option value="1">1 saat (Pantas)</option>
                                <option value="3" selected>3 saat (Standard)</option>
                                <option value="5">5 saat (Jarak Jauh)</option>
                            </select>
                        </div>
                        <div class="rec-field">
                            <label for="cfg-burst-count">Bil. Sampel Statik (Burst)</label>
                            <input type="number" id="cfg-burst-count" min="5" max="200" step="5" value="30">
                        </div>
                        <div class="rec-field">
                            <label for="cfg-seq-frames">Frame Klip Gerakan (DTW)</label>
                            <input type="number" id="cfg-seq-frames" min="10" max="90" step="5" value="30">
                        </div>
                        <div class="rec-field">
                            <label for="cfg-burst-interval">Selang Statik (Kelajuan)</label>
                            <select id="cfg-burst-interval">
                                <option value="50">50ms (Sangat Pantas)</option>
                                <option value="100" selected>100ms (Normal)</option>
                                <option value="180">180ms (Lebih Variasi)</option>
                            </select>
                        </div>
                        <div class="rec-field">
                            <label for="cfg-confidence">Sensitiviti Tangan: <span id="cfg-conf-val">55%</span></label>
                            <input type="range" id="cfg-confidence" min="30" max="85" step="5" value="55" style="width: 100%; accent-color: var(--primary); margin-top: 0.35rem;">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 5. RECORDING & LIVE ACTION BUTTONS --}}
            <div class="studio-btn-row">
                <button type="button" class="btn btn-primary" id="btn-record-one" disabled>
                    <i class="fa-solid fa-record-vinyl"></i> Rakam 1 Sampel
                </button>
                <button type="button" class="btn btn-accent" id="btn-record-burst" disabled>
                    <i class="fa-solid fa-burst"></i> <span id="lbl-record-burst">Rakam Statik (30)</span>
                </button>
                <button type="button" class="btn btn-secondary" id="btn-record-sequence" disabled style="background: linear-gradient(135deg, #0984e3, #6c5ce7); color: #fff;">
                    <i class="fa-solid fa-film"></i> <span id="lbl-record-seq">Rakam Gerakan (30f)</span>
                </button>
                <button type="button" class="btn btn-danger" id="btn-cancel-record" style="display: none;">
                    <i class="fa-solid fa-xmark"></i> Batal Rakaman
                </button>
                <button type="button" class="btn btn-outline" id="btn-stop-camera" style="display: none;">
                    <i class="fa-solid fa-stop"></i> Tutup Kamera
                </button>
            </div>

            <div class="burst-track" id="burst-progress"><div class="burst-fill" id="burst-fill"></div></div>
            <div class="studio-msg" id="record-status">Sedia untuk merakam data.</div>
        </div>

        {{-- LIVE MODEL TESTER CARD --}}
        <div class="card" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.8rem; flex-wrap: wrap;">
                <div>
                    <h3 style="font-size: 1.15rem; margin: 0;">
                        <i class="fa-solid fa-flask" style="color: #6c5ce7;"></i> Uji Model AI Secara Langsung
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0.15rem 0 0;">
                        Keputusan juga dipaparkan terus pada skrin kamera di atas apabila ujian aktif.
                    </p>
                </div>
                <div style="display: flex; gap: 0.45rem;">
                    <button type="button" class="cam-tool-btn" id="btn-reload-model" title="Muat semula dataset AI terkini">
                        <i class="fa-solid fa-rotate"></i> Segar Semula Model
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" id="btn-toggle-test" disabled>
                        <i class="fa-solid fa-play"></i> Mula Uji
                    </button>
                </div>
            </div>

            <div class="test-result">
                <div class="test-label" id="test-label">—</div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.3rem;">
                        <span>Keyakinan Pengecaman</span><span id="test-conf">0%</span>
                    </div>
                    <div class="test-bar"><div id="test-bar"></div></div>
                    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.4rem;" id="test-info">Model belum dimuatkan.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= RIGHT / SIDE: DATASET STATS, LIST & CSV IMPORT ================= --}}
    <div class="studio-side-col">

        {{-- TOTAL SAMPLES SUMMARY CARD --}}
        <div class="card" style="padding: 1.35rem; margin-bottom: 1.25rem; background: linear-gradient(135deg, #6c5ce7 0%, #5041c9 100%); color: #fff;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.4px;">Ringkasan Dataset Pangkalan Data</span>
                <i class="fa-solid fa-database" style="opacity: 0.75;"></i>
            </div>
            <div style="display: flex; gap: 1.25rem; align-items: baseline; margin: 0.45rem 0; flex-wrap: wrap;">
                <div style="font-size: 2.6rem; font-weight: 900; color: #ffeaa7; font-family: var(--font-display); line-height: 1;" id="stat-total-samples">
                    {{ $totalSamples }}
                </div>
                <div style="font-size: 0.82rem; line-height: 1.5;">
                    <div><span id="stat-total-static" style="font-weight: 800; color: #55efc4;">{{ $totalStatic }}</span> sampel statik (KNN)</div>
                    <div><span id="stat-total-seq" style="font-weight: 800; color: #ffeaa7;">{{ $totalSequence }}</span> klip gerakan (DTW)</div>
                </div>
            </div>
            <div style="font-size: 0.76rem; opacity: 0.9;">
                Klik mana-mana baris isyarat di bawah untuk terus menjadikannya sasaran rakaman kamera.
            </div>
        </div>

        {{-- SAMPLES PER SIGN INTERACTIVE LIST --}}
        <div class="card" style="padding: 1.35rem; margin-bottom: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; gap: 0.5rem; flex-wrap: wrap;">
                <h3 style="font-size: 1.15rem; margin: 0;">Senarai Sampel Isyarat</h3>
                <button type="button" class="btn btn-danger btn-sm" id="btn-clear-all" @if(!$totalSamples) style="display:none" @endif>
                    <i class="fa-solid fa-trash"></i> Padam Semua
                </button>
            </div>

            {{-- Filter & Search inside Dataset List --}}
            <div style="display: flex; gap: 0.45rem; margin-bottom: 0.75rem; flex-wrap: wrap;">
                <input type="text"
                       id="sample-filter-input"
                       class="form-input"
                       placeholder="Tapis label sampel..."
                       style="flex: 1; min-width: 130px; padding: 0.38rem 0.7rem; font-size: 0.82rem;">
                <div class="seg-group">
                    <button type="button" class="seg-btn active" data-list-filter="all">Semua</button>
                    <button type="button" class="seg-btn" data-list-filter="low">&lt;50</button>
                    <button type="button" class="seg-btn" data-list-filter="seq">DTW</button>
                </div>
            </div>

            <div id="sample-list-msg" style="display: none; padding: 0.55rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.82rem; font-weight: 800; margin-bottom: 0.75rem;"></div>
            <div style="max-height: 410px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.38rem; padding-right: 0.2rem;" id="sample-list"></div>

            <p style="font-size: 0.76rem; color: var(--text-muted); margin-top: 0.75rem;">
                <i class="fa-solid fa-lightbulb" style="color: #f39c12;"></i> Sasarkan <strong>50+ sampel statik</strong> atau <strong>5+ klip DTW</strong> setiap isyarat daripada pelbagai sudut.
            </p>
        </div>

        {{-- IMPORT CSV CARD --}}
        <div class="card" style="padding: 1.35rem;">
            <h3 style="font-size: 1.1rem; margin-bottom: 0.4rem;">
                <i class="fa-solid fa-file-import" style="color: #00b894;"></i> Import Dataset CSV
            </h3>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 0.75rem;">
                Menyokong kolum <code>label, lm0_x … lm20_z</code> atau <code>label, x0 … z20</code>.
            </p>
            <form method="POST" action="{{ route('admin.ai.import') }}" enctype="multipart/form-data" id="import-form">
                @csrf
                <input type="file" name="dataset" id="import-file" accept=".csv,text/csv" class="form-input" required style="margin-bottom: 0.6rem; padding: 0.5rem; font-size: 0.85rem;">
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <select name="mode" id="import-mode" class="form-select" style="flex: 1; min-width: 150px; padding: 0.45rem 0.7rem; font-size: 0.85rem;">
                        <option value="append">Tambah kepada data sedia ada</option>
                        <option value="replace">Ganti label yang sama</option>
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm" id="btn-import">
                        <i class="fa-solid fa-upload"></i> Import
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>

{{-- CUSTOM DELETE CONFIRMATION MODAL --}}
<div id="delete-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 1rem;">
    <div style="max-width: 420px; width: 100%; background: #fff; padding: 1.8rem; text-align: center; border-radius: var(--radius-lg); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="width: 56px; height: 56px; background: #fee2e2; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 1rem;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 id="delete-modal-title" style="margin: 0 0 0.5rem; font-size: 1.3rem;">Sahkan Pemadaman</h3>
        <p id="delete-modal-desc" style="color: var(--text-muted); font-size: 0.9rem; margin: 0 0 1.5rem; line-height: 1.5;">Adakah anda pasti mahu memadam sampel ini?</p>
        <div style="display: flex; gap: 0.8rem; justify-content: center;">
            <button type="button" class="btn btn-outline" id="btn-cancel-delete" style="flex: 1; justify-content: center;">Batal</button>
            <button type="button" class="btn btn-danger" id="btn-confirm-delete" style="flex: 1; background: #dc2626; color: #fff; justify-content: center;">
                <i class="fa-solid fa-trash-can"></i> Ya, Padam
            </button>
        </div>
    </div>
</div>

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
        KnnClassifier,
        PredictionSmoother,
        HandMotionTracker,
        SequenceClassifier,
        SequenceBuffer
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
    let visionRuntimePromise = null;
    let initPromise = null;
    let faceInitPromise = null;
    let stream = null;
    let running = false;
    let latestLandmarks = null;
    let allHandsLandmarks = [];
    let enableFaceAi = true;
    let latestFaceLandmarks = null;
    let latestFaceBlendshapes = null;
    let latestFaceContext = null;
    let faceFrameTick = 0;
    let lastHandTs = -1;
    let lastFaceTs = -1;
    let busy = false;
    let cancelRequested = false;

    // Flexible UI State
    let showSkeleton = true;
    let showJointNumbers = false;
    let isMirrored = true;
    let fitContain = false;
    let numHands = 1;
    let minConfidence = 0.55;
    let listFilterMode = "all";

    const motionTracker = new HandMotionTracker(45);
    const seqClassifier = new SequenceClassifier(30);
    const seqBuffer = new SequenceBuffer(45);
    let counts = @json((object) $sampleCounts);
    let sequenceCounts = @json((object) $sequenceCounts);

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

    function setRecordButtons(enabled) {
        $("btn-record-one").disabled = !enabled;
        $("btn-record-burst").disabled = !enabled;
        $("btn-record-sequence").disabled = !enabled;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
    }

    function updateRecSummary() {
        const cd = Number($("cfg-countdown").value || 0);
        const burst = Math.max(5, Math.min(200, Number($("cfg-burst-count").value || 30)));
        const seq = Math.max(10, Math.min(90, Number($("cfg-seq-frames").value || 30)));
        $("rec-summary-pill").innerHTML = `Detik: ${cd}s &bull; Statik: ${burst} &bull; Gerakan: ${seq}f`;
        $("lbl-record-burst").textContent = `Rakam Statik (${burst})`;
        $("lbl-record-seq").textContent = `Rakam Gerakan (${seq}f)`;
    }

    function syncChipCountsAndActive() {
        const active = getActiveLabel();
        document.querySelectorAll(".sign-chip").forEach(chip => {
            const lbl = chip.dataset.label;
            chip.classList.toggle("active", lbl === active);
            const badge = chip.querySelector("[data-chip-count]");
            if (badge) {
                const totalForSign = (counts[lbl] || 0) + (sequenceCounts[lbl] || 0);
                badge.textContent = totalForSign;
            }
        });
    }

    function renderCounts(newCounts, total, newSeqCounts = null) {
        if (newCounts) counts = newCounts;
        if (newSeqCounts) sequenceCounts = newSeqCounts;

        const sumStatic = Object.values(counts).reduce((a, b) => a + Number(b), 0);
        const sumSeq = Object.values(sequenceCounts).reduce((a, b) => a + Number(b), 0);
        $("stat-total-samples").textContent = total ?? (sumStatic + sumSeq);
        $("stat-total-static").textContent = sumStatic;
        $("stat-total-seq").textContent = sumSeq;

        const allLabels = [...new Set([...Object.keys(counts), ...Object.keys(sequenceCounts)])].sort();
        $("btn-clear-all").style.display = allLabels.length ? "" : "none";

        const searchQ = ($("sample-filter-input")?.value || "").trim().toUpperCase();
        const active = getActiveLabel();

        const filtered = allLabels.filter(label => {
            if (searchQ && !label.includes(searchQ)) return false;
            const staticN = counts[label] || 0;
            const seqN = sequenceCounts[label] || 0;
            if (listFilterMode === "low") return (staticN + seqN) < MIN_RECOMMENDED && seqN < 5;
            if (listFilterMode === "seq") return seqN > 0;
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
                const totalN = staticN + seqN;
                const isLow = totalN < MIN_RECOMMENDED && seqN < 5;
                const isActive = label === active;
                return `
                <div class="sample-row ${isLow ? "low" : ""} ${isActive ? "active-target" : ""}" data-select-label="${escapeHtml(label)}" title="Klik untuk pilih ${escapeHtml(label)} sebagai sasaran">
                    <span style="display:flex; align-items:center; gap:0.45rem;">
                        <i class="fa-solid ${isActive ? 'fa-circle-dot' : 'fa-hand'}" style="color: ${isActive ? 'var(--primary)' : '#94a3b8'}; font-size: 0.8rem;"></i>
                        <strong>${escapeHtml(label)}</strong>
                    </span>
                    <span style="display:flex; align-items:center; gap:0.35rem; flex-wrap:wrap;">
                        ${staticN > 0 ? `<span class="role-badge badge-static" style="background: #e0f2fe; color: #0284c7;">${staticN} statik</span>` : ''}
                        ${seqN > 0 ? `<span class="role-badge" style="background: #fef3c7; color: #d97706;"><i class="fa-solid fa-film"></i> ${seqN} DTW</span>` : ''}
                        <button type="button" class="icon-btn" data-delete-label="${escapeHtml(label)}" title="Padam sampel ${escapeHtml(label)}">
                            <i class="fa-solid fa-trash-can" style="pointer-events: none;"></i>
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

    async function initModel() {
        if (handLandmarker) return handLandmarker;
        if (initPromise) return initPromise;

        initPromise = (async () => {
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
                setMsg("Kamera dibuka! Memuatkan pengesan sendi & wajah AI...");
                initModel();
            } else {
                if (enableFaceAi && !faceLandmarker) initFaceModel();
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
        latestFaceLandmarks = null;
        latestFaceBlendshapes = null;
        latestFaceContext = null;
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

            // Interleaved FaceLandmarker detection (every 2nd frame for smooth FPS)
            if (enableFaceAi && faceLandmarker && (++faceFrameTick % 2) === 0) {
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
                latestLandmarks = res.landmarks[0];
            } else {
                latestLandmarks = null;
                allHandsLandmarks = [];
            }

            // Compute spatial Hand-to-Face anchor & expression context
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

            // Draw subtle BIM face anchor dots & active hand-to-face link
            if (showSkeleton && enableFaceAi && latestFaceLandmarks && latestFaceContext?.hasFace) {
                drawFaceAnchors(ctx, latestFaceLandmarks, canvas.width, canvas.height, latestFaceContext);
            }

            if (latestLandmarks) {
                motionTracker.push(latestLandmarks);
                seqBuffer.push(buildFeatureVector(latestLandmarks, enableFaceAi ? latestFaceContext : null));

                if (showSkeleton) {
                    allHandsLandmarks.forEach((lm, handIdx) => drawSkeleton(lm, handIdx));
                }

                // Draw motion trail if J motion is detected OR when moving pinky
                const jCheck = motionTracker.detectJMotion();
                const isPinky = isPinkyUpShape(latestLandmarks);
                if (jCheck.matched || (isPinky && !motionTracker.isStill(0.04))) {
                    const trail = motionTracker.getTrail();
                    if (trail.length > 1) {
                        ctx.save();
                        ctx.beginPath();
                        ctx.strokeStyle = jCheck.matched ? "#2ed573" : "#ffeaa7";
                        ctx.lineWidth = 5;
                        ctx.shadowColor = jCheck.matched ? "#2ed573" : "#fdcb6e";
                        ctx.shadowBlur = 10;
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

            if (testing) runTest();

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
        ctx.strokeStyle = handIdx === 0 ? "#00cec9" : "#a29bfe";
        ctx.lineWidth = 3;
        ctx.fillStyle = handIdx === 0 ? "#ff7675" : "#ffeaa7";
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
            ctx.beginPath();
            ctx.arc(px, py, 4, 0, 2 * Math.PI);
            ctx.fill();

            if (showJointNumbers) {
                ctx.save();
                ctx.translate(px, py);
                if (isMirrored) ctx.scale(-1, 1);
                ctx.font = "bold 11px Nunito, sans-serif";
                ctx.fillStyle = "#ffffff";
                ctx.strokeStyle = "rgba(15,23,42,0.85)";
                ctx.lineWidth = 2.5;
                ctx.strokeText(String(idx), 6, -5);
                ctx.fillText(String(idx), 6, -5);
                ctx.restore();
            }
        });
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
                hintEl.innerHTML = '<i class="fa-solid fa-face-smile"></i> <strong>Mod Hibrid Tangan + Wajah (78 ciri):</strong> Pastikan wajah kelihatan dalam kamera supaya kedudukan tangan pada zon muka &amp; ekspresi BIM turut dirakam.';
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
                if (sN > 0 || qN > 0) {
                    const parts = [];
                    if (sN > 0) parts.push(`${sN} statik`);
                    if (qN > 0) parts.push(`${qN} klip DTW`);
                    countEl.textContent = `(${parts.join(", ")})`;
                    countEl.style.color = "#16a34a";
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
            const faceNote = vec.length >= 78 ? " + Wajah" : "";
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

    async function runCountdown() {
        const seconds = Math.max(0, Number($("cfg-countdown").value || 0));
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
            const hasFaceSamples = samples.some(s => s.length >= 78);
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
            cd.textContent = "GERAK!";
            await sleep(350);
            cd.style.display = "none";

            const capturedFrames = [];
            track.style.display = "block";
            fill.style.width = "0%";
            let lastCaptured = -1;
            const deadline = performance.now() + 12000;

            while (capturedFrames.length < targetFrames) {
                if (!running) throw new Error("Kamera ditutup.");
                if (cancelRequested) throw new Error("Rakaman dibatalkan.");
                if (performance.now() > deadline) break;

                if (latestLandmarks && lastVideoTime !== lastCaptured) {
                    lastCaptured = lastVideoTime;
                    capturedFrames.push(captureFeatureVector(label));
                    fill.style.width = (capturedFrames.length / targetFrames * 100) + "%";
                    setMsg(`Merakam klip DTW "${label}": frame ${capturedFrames.length} / ${targetFrames} (Teruskan gerakan!)`);
                } else if (!latestLandmarks) {
                    setMsg(`Menunggu tangan... (${capturedFrames.length} / ${targetFrames})`, "err");
                }
                await sleep(25);
            }

            if (capturedFrames.length < 8) {
                throw new Error("Gerakan terlalu pendek (<8 frame) atau tangan hilang daripada kamera.");
            }

            const hasFaceFrames = capturedFrames.some(f => f.length >= 78);
            setMsg(`Menyimpan klip gerakan (${capturedFrames.length} frame) untuk "${label}"...`);
            const data = await postJson(ROUTES.store, {
                sample_type: "sequence",
                label,
                frames: capturedFrames,
            });

            renderCounts(data.counts, data.total_all, data.sequence_counts || {});
            classifierStale = true;
            setMsg(`✔ Klip gerakan "${label}" (${capturedFrames.length} frame${hasFaceFrames ? " + Konteks Wajah" : ""}) berjaya disimpan!`, "ok");
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
        $("delete-modal").style.display = "flex";
    }

    function closeDeleteModal() {
        $("delete-modal").style.display = "none";
        pendingDeleteBody = null;
    }

    function showListMsg(text, type = "success") {
        const el = $("sample-list-msg");
        if (!el) return;
        el.textContent = text;
        el.style.display = "block";
        el.style.background = type === "success" ? "#dcfce7" : "#fee2e2";
        el.style.color = type === "success" ? "#16a34a" : "#dc2626";
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
        const resp = await fetch(ROUTES.dataset, { headers: { Accept: "application/json" } });
        if (!resp.ok) throw new Error("Gagal memuatkan dataset (" + resp.status + ").");
        const data = await resp.json();
        classifier.load(data.samples || []);
        seqClassifier.load(data.sequences || []);
        classifierStale = false;
        $("test-info").textContent = `${classifier.size} sampel statik (${classifier.labels.length} label), ${seqClassifier.size} klip gerakan DTW (${seqClassifier.labels.join(", ") || "tiada"})`;
    }

    async function startTest() {
        try {
            if (classifierStale) await loadClassifier();
            if (classifier.labels.length === 0 && seqClassifier.labels.length === 0) {
                $("test-info").textContent = "Pangkalan data belum mempunyai sampel untuk diuji.";
                return;
            }
            smoother.reset();
            seqBuffer.reset();
            testing = true;
            $("hud-live-test").style.display = "flex";
            $("btn-toggle-test").innerHTML = '<i class="fa-solid fa-stop"></i> Henti Uji';
        } catch (e) {
            $("test-info").textContent = e.message;
        }
    }

    function stopTest() {
        testing = false;
        $("hud-live-test").style.display = "none";
        $("btn-toggle-test").innerHTML = '<i class="fa-solid fa-play"></i> Mula Uji';
        updateTestDisplay("—", "0%", "0%");
    }

    function runTest() {
        if (!latestLandmarks) {
            smoother.push(null);
            updateTestDisplay("—", "0%", "0%");
            return;
        }

        // 1. Direct Disambiguation for Pinky-Up shape: I (static) vs J (dynamic hook)
        const isPinky = isPinkyUpShape(latestLandmarks);
        if (isPinky && (classifier.labels.includes("I") || classifier.labels.includes("J") || seqClassifier.labels.includes("J"))) {
            const jCheck = motionTracker.detectJMotion();
            if (jCheck && jCheck.matched) {
                const conf = jCheck.confidence || 95;
                updateTestDisplay("J", conf + "% (Cangkuk J 🎬)", conf + "%");
                smoother.push("J");
                return;
            } else {
                updateTestDisplay("I", "95% (Isyarat I Pegun)", "95%");
                smoother.push("I");
                return;
            }
        }

        const activeFaceCtx = enableFaceAi ? latestFaceContext : null;

        // 2. Dynamic sequence DTW check when hand is moving
        if (seqClassifier.size > 0 && !motionTracker.isStill(0.05) && seqBuffer.length >= 15) {
            const seqPred = seqClassifier.predict(seqBuffer.getFrames(), activeFaceCtx);
            if (seqPred.confidence >= 60) {
                const dtwBadge = seqPred.usedFace ? "DTW + Wajah 👤" : "DTW 🎬";
                updateTestDisplay(seqPred.label, `${seqPred.confidence}% (${dtwBadge})`, seqPred.confidence + "%");
                return;
            }
        }

        // 3. Static KNN fallback (Multimodal 78-feature fusion when face context applies)
        const queryVec = buildFeatureVector(latestLandmarks, activeFaceCtx);
        const { label, confidence, usedFace } = classifier.predict(queryVec, motionTracker, activeFaceCtx);
        const stable = smoother.push(confidence >= 40 ? label : null);
        const faceBoost = (stable && !isHandOnlyLabel(stable) && activeFaceCtx?.hasFace)
            ? evaluateFaceSignBoost(stable, activeFaceCtx)
            : { bonus: 0 };
        const faceBadge = (usedFace || faceBoost.bonus !== 0) ? " + Wajah 👤" : "";
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
    ["cfg-countdown", "cfg-burst-count", "cfg-seq-frames", "cfg-burst-interval"].forEach(id => {
        $(id).addEventListener("input", updateRecSummary);
        $(id).addEventListener("change", updateRecSummary);
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
    $("btn-cancel-delete").addEventListener("click", closeDeleteModal);
    $("btn-confirm-delete").addEventListener("click", executeDelete);
    $("delete-modal").addEventListener("click", e => {
        if (e.target === $("delete-modal")) closeDeleteModal();
    });

    // Prev / Next Sign Steppers
    $("btn-prev-sign").addEventListener("click", () => stepSign(-1));
    $("btn-next-sign").addEventListener("click", () => stepSign(1));

    // Category Filter for Quick Sign Chips
    $("category-chip-filter").addEventListener("change", e => {
        const val = e.target.value;
        document.querySelectorAll(".sign-chip").forEach(chip => {
            chip.style.display = (val === "ALL" || chip.dataset.cat === val) ? "" : "none";
        });
    });

    // Quick Sign Chips Click (Event Delegation)
    $("quick-sign-chips").addEventListener("click", e => {
        const chip = e.target.closest(".sign-chip");
        if (chip && chip.dataset.label) {
            setActiveLabel(chip.dataset.label);
        }
    });

    // Sample List Click: Select Target Sign OR Delete
    $("sample-list").addEventListener("click", e => {
        const delBtn = e.target.closest("[data-delete-label]");
        if (delBtn) {
            e.stopPropagation();
            window.confirmDelete(delBtn.dataset.deleteLabel);
            return;
        }
        const row = e.target.closest("[data-select-label]");
        if (row && row.dataset.selectLabel) {
            setActiveLabel(row.dataset.selectLabel);
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
            dropOpt.textContent = `${word} (${label})`;
            $("sign-select-dropdown").appendChild(dropOpt);

            const chip = document.createElement("button");
            chip.type = "button";
            chip.className = "sign-chip";
            chip.dataset.label = label;
            chip.dataset.cat = `cat-${categoryId}`;
            chip.innerHTML = `<span>${escapeHtml(word)}</span><span class="chip-count" data-chip-count="${escapeHtml(label)}">0</span>`;
            $("quick-sign-chips").appendChild(chip);

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
            recordOne();
        } else if (e.key?.toUpperCase() === "B" && !busy) {
            e.preventDefault();
            recordBurst();
        } else if (e.key?.toUpperCase() === "G" && !busy) {
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

    updateRecSummary();
    renderCounts(counts, {{ $totalSamples }}, sequenceCounts);
    updateActiveSignInfo(true);
</script>
@endpush
@endsection

@extends('layouts.app', ['title' => 'Studio Latihan AI - MediaPipe'])

@push('styles')
<style>
    .studio-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 2rem; }
    @media (max-width: 900px) { .studio-grid { grid-template-columns: 1fr; } }

    .studio-stage { position: relative; background: #1e272e; border-radius: var(--radius-md); overflow: hidden; aspect-ratio: 4/3; margin-bottom: 1rem; }
    .studio-stage video, .studio-stage canvas { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
    .studio-hud { position: absolute; top: 0.8rem; left: 0.8rem; right: 0.8rem; display: flex; justify-content: space-between; gap: 0.5rem; pointer-events: none; }
    .hud-pill { padding: 0.35rem 0.8rem; border-radius: 999px; font-size: 0.8rem; font-weight: 800; background: rgba(15, 23, 42, 0.75); color: #fff; backdrop-filter: blur(6px); }
    .hud-pill.ok { background: rgba(22, 163, 74, 0.85); }
    .hud-pill.warn { background: rgba(217, 119, 6, 0.85); }
    .studio-countdown { position: absolute; inset: 0; display: none; align-items: center; justify-content: center; font-size: 6rem; font-weight: 900; color: #ffeaa7; text-shadow: 0 6px 30px rgba(0,0,0,0.6); font-family: var(--font-display); }
    .studio-btn-row { display: flex; gap: 0.8rem; flex-wrap: wrap; }
    .studio-btn-row .btn { flex: 1; min-width: 160px; justify-content: center; }
    .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
    .btn-danger { background: #fee2e2; color: #dc2626; border: none; }
    .btn-danger:hover { background: #fecaca; }

    .studio-msg { margin-top: 0.8rem; font-size: 0.9rem; font-weight: 800; text-align: center; min-height: 1.4em; color: var(--text-muted); }
    .studio-msg.ok { color: #16a34a; }
    .studio-msg.err { color: #dc2626; }

    .progress-track { height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; margin-top: 0.6rem; display: none; }
    .progress-fill { height: 100%; width: 0; background: linear-gradient(90deg, #6c5ce7, #00cec9); transition: width 0.1s linear; }

    .test-result { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: var(--radius-md); margin-top: 1rem; }
    .test-label { font-size: 2.2rem; font-weight: 900; font-family: var(--font-display); min-width: 90px; text-align: center; color: #5041c9; }
    .test-bar { flex: 1; height: 12px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
    .test-bar > div { height: 100%; width: 0; background: linear-gradient(90deg, #ff7675, #fdcb6e, #00b894); transition: width 0.15s; }

    .sample-row { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; padding: 0.5rem 0.8rem; background: #f8fafc; border-radius: var(--radius-sm); font-size: 0.9rem; }
    .sample-row.low .role-badge { background: #fef3c7; color: #d97706; }
    .icon-btn { border: none; background: transparent; color: #94a3b8; cursor: pointer; padding: 0.25rem 0.4rem; border-radius: 6px; }
    .icon-btn:hover { color: #dc2626; background: #fee2e2; }
</style>
@endpush

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
            <h1 style="font-size: 1.8rem; margin: 0;">Studio Latihan AI &amp; Pengumpulan Data</h1>
            <span class="role-badge" style="background: #e0f2fe; color: #0284c7;">Ahli Kumpulan C (ML/AI)</span>
        </div>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
            Rakam koordinat 21 titik sendi tangan (63 koordinat 3D), import dataset CSV, dan uji model secara langsung.
        </p>
    </div>

    <div style="display: flex; gap: 0.8rem;">
        <a href="{{ route('admin.ai.export') }}" class="btn btn-primary btn-sm" id="btn-export">
            <i class="fa-solid fa-file-csv"></i> Muat Turun Dataset (CSV)
        </a>
    </div>
</div>

<div class="studio-grid">

    {{-- LEFT: LIVE CAMERA RECORDER + TESTER --}}
    <div>
        <div class="card" style="margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.2rem; margin-bottom: 1rem;">
                <i class="fa-solid fa-camera" style="color: var(--secondary);"></i> Suapan Kamera &amp; Ekstraksi Titik Tangan
            </h3>

            {{-- TARGET SIGN SEARCH & SELECTOR --}}
            <div style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; border: 1px solid var(--border-light);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem; flex-wrap: wrap; gap: 0.5rem;">
                    <label for="sign-search-input" style="font-weight: 800; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; color: var(--text-dark);">
                        <i class="fa-solid fa-magnifying-glass" style="color: #6c5ce7;"></i>
                        <span>Isyarat Sasaran (Cari atau Taip Label):</span>
                    </label>
                    <button type="button" class="btn btn-outline btn-sm" id="btn-toggle-new-sign" style="font-size: 0.8rem; padding: 0.3rem 0.75rem; border-color: #6c5ce7; color: #6c5ce7; font-weight: 800;">
                        <i class="fa-solid fa-circle-plus"></i> + Label Isyarat Baru
                    </button>
                </div>

                <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
                    {{-- TYPABLE & SEARCHABLE INPUT --}}
                    <div style="flex: 1; min-width: 240px; position: relative;">
                        <input type="text"
                               id="sign-search-input"
                               class="form-input"
                               list="sign-datalist"
                               placeholder="Cari atau taip label isyarat (cth: A, B, C, MAKAN)..."
                               value="A"
                               autocomplete="off"
                               style="padding-left: 2.3rem; font-weight: 800; font-size: 1rem; text-transform: uppercase;">
                        <i class="fa-solid fa-hand" style="position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;"></i>
                    </div>

                    {{-- SUGGESTIONS DATALIST --}}
                    <datalist id="sign-datalist">
                        @foreach($categories as $cat)
                            @foreach($cat->signs as $s)
                                <option value="{{ $s->label }}">{{ $s->word }} (Kategori: {{ $cat->name }})</option>
                            @endforeach
                        @endforeach
                    </datalist>

                    {{-- QUICK SELECT DROPDOWN COMPANION --}}
                    <select id="sign-select-dropdown" class="form-select" style="width: auto; max-width: 200px; font-weight: 700; font-size: 0.9rem; padding: 0.45rem 0.8rem;" title="Pilih pantas daripada senarai">
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

                {{-- LIVE ACTIVE BADGE INFO --}}
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.6rem; font-size: 0.82rem; flex-wrap: wrap; gap: 0.4rem;">
                    <span id="active-sign-badge" style="color: var(--text-muted); font-weight: 700;">
                        Isyarat aktif dirakam: <strong id="active-sign-text" style="color: #6c5ce7; font-size: 1rem; background: #eeebff; padding: 0.15rem 0.5rem; border-radius: var(--radius-sm);">A</strong>
                        <span id="active-sign-sample-count" style="margin-left: 0.4rem; color: #16a34a; font-weight: 800;"></span>
                    </span>
                    <span style="color: var(--text-muted); font-size: 0.78rem;">
                        <i class="fa-solid fa-circle-info"></i> Taip apa jua teks untuk cipta label baru serta-merta.
                    </span>
                </div>

                {{-- MOTION GUIDANCE NOTICE FOR I & J --}}
                <div id="sign-motion-hint" style="display: none; margin-top: 0.6rem; padding: 0.6rem 0.9rem; background: #e0f2fe; border: 1px solid #bae6fd; border-radius: var(--radius-sm); font-size: 0.82rem; color: #0369a1; line-height: 1.4;"></div>

                {{-- INLINE NEW SIGN CREATOR --}}
                <div id="new-sign-box" style="display: none; margin-top: 0.9rem; padding: 1rem; background: #ffffff; border-radius: var(--radius-md); border: 2px dashed #6c5ce7; box-shadow: 0 4px 12px rgba(108, 92, 231, 0.08);">
                    <div style="font-weight: 800; font-size: 0.9rem; margin-bottom: 0.8rem; color: #5041c9; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fa-solid fa-plus-circle"></i> Tambah Isyarat Baru ke Sistem (BIM)</span>
                        <button type="button" class="icon-btn" id="btn-close-new-sign" style="font-size: 1.2rem; line-height: 1;" title="Tutup">&times;</button>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap: 0.8rem; align-items: flex-end;">
                        <div>
                            <label for="new-sign-word" style="font-size: 0.8rem; font-weight: 800; color: var(--text-dark); display: block; margin-bottom: 0.3rem;">Perkataan / Nama Isyarat:</label>
                            <input type="text" id="new-sign-word" class="form-input" placeholder="cth: Terima Kasih" style="font-size: 0.9rem; padding: 0.45rem 0.7rem;">
                        </div>
                        <div>
                            <label for="new-sign-label" style="font-size: 0.8rem; font-weight: 800; color: var(--text-dark); display: block; margin-bottom: 0.3rem;">Label AI (Huruf Besar):</label>
                            <input type="text" id="new-sign-label" class="form-input" placeholder="cth: TERIMA_KASIH" style="font-size: 0.9rem; padding: 0.45rem 0.7rem; text-transform: uppercase;">
                        </div>
                        <div>
                            <label for="new-sign-category" style="font-size: 0.8rem; font-weight: 800; color: var(--text-dark); display: block; margin-bottom: 0.3rem;">Kategori:</label>
                            <select id="new-sign-category" class="form-select" style="font-size: 0.9rem; padding: 0.45rem 0.7rem;">
                                @foreach($allCategories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary btn-sm" id="btn-save-new-sign" style="height: 38px; white-space: nowrap; padding: 0 1rem;">
                                <i class="fa-solid fa-check"></i> Simpan Isyarat
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="studio-stage">
                <video id="studio-video" autoplay muted playsinline style="display: none;"></video>
                <canvas id="studio-canvas"></canvas>

                <div class="studio-hud" id="studio-hud" style="display: none;">
                    <span class="hud-pill warn" id="hud-hand">Tiada tangan</span>
                    <span class="hud-pill" id="hud-fps">0 FPS</span>
                </div>
                <div class="studio-countdown" id="studio-countdown">3</div>

                <div id="studio-placeholder" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff;">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem; color: var(--secondary);">📹</div>
                    <p style="color: #a4b0be; font-size: 0.9rem; margin-bottom: 1rem;">Kamera belum diaktifkan.</p>
                    <button type="button" class="btn btn-secondary" id="btn-start-camera">
                        <i class="fa-solid fa-play"></i> Buka Kamera Studio
                    </button>
                </div>
            </div>

            {{-- RECORDING CONTROLS --}}
            <div class="studio-btn-row">
                <button type="button" class="btn btn-primary" id="btn-record-one" disabled>
                    <i class="fa-solid fa-record-vinyl"></i> Rakam 1 Sampel
                </button>
                <button type="button" class="btn btn-accent" id="btn-record-burst" disabled>
                    <i class="fa-solid fa-burst"></i> Rakam Statik (30 Sampel)
                </button>
                <button type="button" class="btn btn-secondary" id="btn-record-sequence" disabled style="background: linear-gradient(135deg, #0984e3, #6c5ce7); color: #fff;">
                    <i class="fa-solid fa-film"></i> Rakam Gerakan (30 Frame)
                </button>
                <button type="button" class="btn btn-outline" id="btn-stop-camera" style="display: none; flex: 0 0 auto; min-width: 0;">
                    <i class="fa-solid fa-stop"></i> Tutup
                </button>
            </div>

            <div class="progress-track" id="burst-progress"><div class="progress-fill" id="burst-fill"></div></div>
            <div class="studio-msg" id="record-status">Sedia untuk merakam data.</div>
        </div>

        {{-- LIVE TEST --}}
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <h3 style="font-size: 1.2rem; margin: 0;">
                    <i class="fa-solid fa-flask" style="color: #6c5ce7;"></i> Uji Model Secara Langsung
                </h3>
                <button type="button" class="btn btn-outline btn-sm" id="btn-toggle-test" disabled>
                    <i class="fa-solid fa-play"></i> Mula Uji
                </button>
            </div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.4rem;">
                Pengelas KNN dalam pelayar menggunakan semua sampel dalam pangkalan data. Buka kamera dahulu.
            </p>
            <div class="test-result">
                <div class="test-label" id="test-label">—</div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 800; color: var(--text-muted); margin-bottom: 0.3rem;">
                        <span>Keyakinan</span><span id="test-conf">0%</span>
                    </div>
                    <div class="test-bar"><div id="test-bar"></div></div>
                    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.4rem;" id="test-info">Model belum dimuatkan.</div>
                    <div style="font-size: 0.75rem; color: #5041c9; margin-top: 0.35rem; font-weight: 700;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Penjejak Gerakan AI Aktif: 'I' (pegun) &amp; 'J' (cangkuk) dibezakan secara automatik.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT: DATASET SUMMARY, IMPORT & COUNTS --}}
    <div>
        {{-- TOTAL SAMPLES CARD --}}
        <div class="card" style="margin-bottom: 1.5rem; background: linear-gradient(135deg, #6c5ce7 0%, #5041c9 100%); color: #fff;">
            <div style="font-size: 0.85rem; font-weight: 800; text-transform: uppercase;">Jumlah Sampel Dalam Pangkalan Data</div>
            <div style="display: flex; gap: 1.5rem; align-items: baseline; margin: 0.4rem 0;">
                <div style="font-size: 2.8rem; font-weight: 900; color: #ffeaa7; font-family: var(--font-display);" id="stat-total-samples">
                    {{ $totalSamples }}
                </div>
                <div style="font-size: 0.82rem; line-height: 1.4;">
                    <div><span id="stat-total-static" style="font-weight: 800; color: #55efc4;">{{ $totalStatic }}</span> sampel statik (63 nombor)</div>
                    <div><span id="stat-total-seq" style="font-weight: 800; color: #ffeaa7;">{{ $totalSequence }}</span> klip gerakan (30 frame DTW)</div>
                </div>
            </div>
            <div style="font-size: 0.78rem; opacity: 0.9;">
                Statik dinormalkan relatif pergelangan tangan; Gerakan disimpan urutan 30 bingkai dengan padanan DTW masa nyata.
            </div>
        </div>

        {{-- IMPORT CSV --}}
        <div class="card" style="margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.2rem; margin-bottom: 0.6rem;">
                <i class="fa-solid fa-file-import" style="color: #00b894;"></i> Import Dataset CSV
            </h3>
            <p style="color: var(--text-muted); font-size: 0.82rem; margin-bottom: 0.8rem;">
                Menyokong format <code>label, lm0_x … lm20_z</code> (MediaPipe recorder) dan <code>label, x0 … z20</code> (eksport studio ini).
            </p>
            <form method="POST" action="{{ route('admin.ai.import') }}" enctype="multipart/form-data" id="import-form">
                @csrf
                <input type="file" name="dataset" id="import-file" accept=".csv,text/csv" class="form-input" required style="margin-bottom: 0.6rem;">
                <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                    <select name="mode" id="import-mode" class="form-select" style="flex: 1; min-width: 160px;">
                        <option value="append">Tambah kepada data sedia ada</option>
                        <option value="replace">Ganti label yang sama</option>
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm" id="btn-import">
                        <i class="fa-solid fa-upload"></i> Import
                    </button>
                </div>
            </form>
        </div>

        {{-- SAMPLES PER SIGN LIST --}}
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; gap: 0.5rem;">
                <h3 style="font-size: 1.2rem; margin: 0;">Bilangan Sampel Mengikut Isyarat</h3>
                <button type="button" class="btn btn-danger btn-sm" id="btn-clear-all" @if(!$totalSamples) style="display:none" @endif>
                    <i class="fa-solid fa-trash"></i> Padam Semua
                </button>
            </div>
            <div id="sample-list-msg" style="display: none; padding: 0.6rem 0.8rem; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 800; margin-bottom: 0.8rem;"></div>
            <div style="max-height: 380px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.4rem;" id="sample-list"></div>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.8rem;">
                <i class="fa-solid fa-lightbulb"></i> Sasarkan sekurang-kurangnya 50 sampel setiap isyarat, dari pelbagai sudut &amp; jarak.
            </p>
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
    import { HandLandmarker, FilesetResolver } from "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/vision_bundle.mjs";
    import { normaliseLandmarks, isPinkyUpShape, KnnClassifier, PredictionSmoother, HandMotionTracker, SequenceClassifier, SequenceBuffer } from "{{ asset('js/gesture-ai.js') }}";

    const ROUTES = {
        store: "{{ route('admin.ai.sample.store') }}",
        newSign: "{{ route('admin.ai.sign.store') }}",
        clear: "{{ route('admin.ai.clear') }}",
        dataset: "{{ route('admin.ai.dataset') }}",
    };
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const MIN_RECOMMENDED = 50;

    const $ = id => document.getElementById(id);
    const video = $("studio-video");
    const canvas = $("studio-canvas");
    const ctx = canvas.getContext("2d");

    let handLandmarker = null;
    let stream = null;
    let running = false;
    let latestLandmarks = null;
    const motionTracker = new HandMotionTracker(45);
    const seqClassifier = new SequenceClassifier(30);
    const seqBuffer = new SequenceBuffer(45);
    let busy = false;           // true while recording / saving
    let counts = @json((object) $sampleCounts);
    let sequenceCounts = @json((object) $sequenceCounts);

    // ---------- UI helpers ----------
    function setMsg(text, type = "") {
        const el = $("record-status");
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

    function renderCounts(newCounts, total) {
        counts = newCounts || {};
        const list = $("sample-list");
        const allLabels = [...new Set([...Object.keys(counts), ...Object.keys(sequenceCounts)])].sort();
        $("stat-total-samples").textContent = total ?? (Object.values(counts).reduce((a, b) => a + b, 0) + Object.values(sequenceCounts).reduce((a, b) => a + b, 0));
        $("btn-clear-all").style.display = allLabels.length ? "" : "none";

        if (!allLabels.length) {
            list.innerHTML = '<p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 1.5rem 0;">Belum ada sampel dirakam. Mulakan rakaman pertama anda!</p>';
            return;
        }
        list.innerHTML = allLabels.map(label => {
            const staticN = counts[label] || 0;
            const seqN = sequenceCounts[label] || 0;
            const totalN = staticN + seqN;
            return `
            <div class="sample-row ${totalN < MIN_RECOMMENDED && seqN === 0 ? "low" : ""}">
                <strong>${escapeHtml(label)}</strong>
                <span style="display:flex; align-items:center; gap:0.4rem; flex-wrap:wrap;">
                    ${staticN > 0 ? `<span class="role-badge" style="background: #e0f2fe; color: #0284c7;">${staticN} statik</span>` : ''}
                    ${seqN > 0 ? `<span class="role-badge" style="background: #fef3c7; color: #d97706;"><i class="fa-solid fa-film"></i> ${seqN} klip DTW</span>` : ''}
                    <button type="button" class="icon-btn" onclick="window.confirmDelete('${escapeHtml(label)}')" title="Padam sampel ${escapeHtml(label)}">
                        <i class="fa-solid fa-trash-can" style="pointer-events: none;"></i>
                    </button>
                </span>
            </div>`;
        }).join("");
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

    // ---------- MediaPipe ----------
    async function initModel() {
        if (handLandmarker) return handLandmarker;
        setMsg("Memuatkan MediaPipe HandLandmarker...");
        const vision = await FilesetResolver.forVisionTasks("https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm");
        const options = delegate => ({
            baseOptions: {
                modelAssetPath: "https://storage.googleapis.com/mediapipe-models/hand_landmarker/hand_landmarker/float16/1/hand_landmarker.task",
                delegate,
            },
            runningMode: "VIDEO",
            numHands: 1,
            minHandDetectionConfidence: 0.6,
            minTrackingConfidence: 0.5,
        });
        try {
            handLandmarker = await HandLandmarker.createFromOptions(vision, options("GPU"));
        } catch (e) {
            console.warn("GPU delegate gagal, guna CPU:", e);
            handLandmarker = await HandLandmarker.createFromOptions(vision, options("CPU"));
        }
        return handLandmarker;
    }

    async function startCamera() {
        if (running) return;
        const btn = $("btn-start-camera");
        btn.disabled = true;
        try {
            await initModel();
            stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480, facingMode: "user" }, audio: false });
            video.srcObject = stream;
            await new Promise(res => (video.readyState >= 2 ? res() : video.addEventListener("loadeddata", res, { once: true })));
            await video.play();

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            video.style.display = "block";
            $("studio-placeholder").style.display = "none";
            $("studio-hud").style.display = "flex";
            $("btn-stop-camera").style.display = "";
            $("btn-toggle-test").disabled = false;
            setRecordButtons(true);
            setMsg("Kamera aktif. Tunjukkan isyarat tangan anda.", "ok");

            running = true;
            requestAnimationFrame(loop);
        } catch (e) {
            console.error(e);
            const msg = e.name === "NotAllowedError" ? "Akses kamera ditolak. Benarkan kamera dalam tetapan pelayar."
                : e.name === "NotFoundError" ? "Tiada kamera dijumpai."
                : e.name === "NotReadableError" ? "Kamera sedang digunakan oleh aplikasi lain."
                : "Gagal memulakan kamera/model: " + e.message;
            setMsg(msg, "err");
        } finally {
            btn.disabled = false;
        }
    }

    function stopCamera() {
        running = false;
        stopTest();
        stream?.getTracks().forEach(t => t.stop());
        stream = null;
        video.srcObject = null;
        video.style.display = "none";
        latestLandmarks = null;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        $("studio-placeholder").style.display = "flex";
        $("studio-hud").style.display = "none";
        $("btn-stop-camera").style.display = "none";
        $("btn-toggle-test").disabled = true;
        setRecordButtons(false);
        setMsg("Kamera ditutup.");
    }

    let lastVideoTime = -1, frames = 0, fpsTimer = performance.now();
    function loop() {
        if (!running) return;
        if (video.currentTime !== lastVideoTime && video.readyState >= 2) {
            lastVideoTime = video.currentTime;
            const res = handLandmarker.detectForVideo(video, performance.now());
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const hud = $("hud-hand");
            if (res.landmarks && res.landmarks.length > 0) {
                latestLandmarks = res.landmarks[0];
                motionTracker.push(latestLandmarks);
                seqBuffer.push(normaliseLandmarks(latestLandmarks));
                drawSkeleton(latestLandmarks);

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

                hud.textContent = "Tangan dikesan ✋";
                hud.className = "hud-pill ok";
            } else {
                latestLandmarks = null;
                hud.textContent = "Tiada tangan";
                hud.className = "hud-pill warn";
            }
            if (testing) runTest();

            frames++;
            const now = performance.now();
            if (now - fpsTimer >= 1000) {
                $("hud-fps").textContent = frames + " FPS";
                frames = 0; fpsTimer = now;
            }
        }
        requestAnimationFrame(loop);
    }

    function drawSkeleton(lm) {
        const w = canvas.width, h = canvas.height;
        ctx.strokeStyle = "#00cec9"; ctx.lineWidth = 3; ctx.fillStyle = "#ff7675";
        const connections = [
            [0,1],[1,2],[2,3],[3,4], [0,5],[5,6],[6,7],[7,8], [0,9],[9,10],[10,11],[11,12],
            [0,13],[13,14],[14,15],[15,16], [0,17],[17,18],[18,19],[19,20], [5,9],[9,13],[13,17]
        ];
        connections.forEach(([i, j]) => {
            ctx.beginPath(); ctx.moveTo(lm[i].x * w, lm[i].y * h); ctx.lineTo(lm[j].x * w, lm[j].y * h); ctx.stroke();
        });
        lm.forEach(p => { ctx.beginPath(); ctx.arc(p.x * w, p.y * h, 4, 0, 2 * Math.PI); ctx.fill(); });
    }

    function getActiveLabel() {
        const input = $("sign-search-input");
        return input ? (input.value || "").trim().toUpperCase() : "";
    }

    function updateActiveSignInfo() {
        const label = getActiveLabel();
        const textEl = $("active-sign-text");
        const countEl = $("active-sign-sample-count");
        if (textEl) textEl.textContent = label || "(Tiada)";

        const hintEl = $("sign-motion-hint");
        if (hintEl) {
            if (label === 'I') {
                hintEl.style.display = "block";
                hintEl.innerHTML = '<i class="fa-solid fa-hand-holding"></i> <strong>Panduan Isyarat I (Pegun):</strong> Jari kelengkeng tegak dan tangan dipegang diam tanpa bergerak. AI memerlukan kestabilan kedudukan untuk mengesahkan isyarat I.';
            } else if (label === 'J') {
                hintEl.style.display = "block";
                hintEl.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> <strong>Panduan Isyarat J (Dinamik):</strong> Bentuk asas jari sama seperti I, tetapi perlu melukis bentuk cangkuk "J" di udara (turun ke bawah lalu melengkung ke atas). AI menggunakan penjejak trajektori masa nyata untuk membezakannya secara automatik daripada I.';
            } else {
                hintEl.style.display = "none";
            }
        }

        if (!countEl) return;
        if (!label) {
            countEl.textContent = "";
            return;
        }
        const n = counts[label] || 0;
        if (n > 0) {
            countEl.textContent = `(${n} sampel sedia ada)`;
            countEl.style.color = "#16a34a";
        } else {
            countEl.textContent = "(0 sampel - label baru)";
            countEl.style.color = "#d97706";
        }
    }

    // ---------- Recording ----------
    async function saveSamples(label, samples) {
        const data = await postJson(ROUTES.store, { label, samples });
        renderCounts(data.counts, data.total_all);
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
        if (!latestLandmarks) { setMsg("Tiada tangan dikesan. Pastikan tangan jelas di hadapan kamera.", "err"); return; }
        busy = true; setRecordButtons(false);
        try {
            const data = await saveSamples(label, [normaliseLandmarks(latestLandmarks)]);
            setMsg(`✔ Sampel "${data.label}" dirakam (jumlah ${data.counts[data.label]}).`, "ok");
        } catch (e) {
            setMsg("✖ " + e.message, "err");
        } finally {
            busy = false; setRecordButtons(running);
        }
    }

    const sleep = ms => new Promise(r => setTimeout(r, ms));

    async function recordBurst(count = 30) {
        if (busy) return;
        const label = getActiveLabel();
        if (!label) {
            setMsg("Sila taip atau pilih isyarat sasaran terlebih dahulu.", "err");
            $("sign-search-input").focus();
            return;
        }
        busy = true; setRecordButtons(false);
        const cd = $("studio-countdown");
        const track = $("burst-progress"), fill = $("burst-fill");

        try {
            // 3-second countdown so the user can get their hand into position
            cd.style.display = "flex";
            for (let i = 3; i > 0; i--) { cd.textContent = i; await sleep(1000); if (!running) throw new Error("Kamera ditutup."); }
            cd.style.display = "none";

            const samples = [];
            track.style.display = "block"; fill.style.width = "0%";
            const deadline = performance.now() + 15000; // give up after 15 s without hand
            let lastCapturedTime = -1;

            while (samples.length < count) {
                if (!running) throw new Error("Kamera ditutup.");
                if (performance.now() > deadline) break;
                if (latestLandmarks && lastVideoTime !== lastCapturedTime) {
                    lastCapturedTime = lastVideoTime;
                    samples.push(normaliseLandmarks(latestLandmarks));
                    fill.style.width = (samples.length / count * 100) + "%";
                    setMsg(`Merakam "${label}": ${samples.length} / ${count} — gerakkan tangan sedikit untuk variasi`);
                } else if (!latestLandmarks) {
                    setMsg(`Menunggu tangan... (${samples.length} / ${count})`, "err");
                }
                await sleep(100);
            }

            if (!samples.length) throw new Error("Tiada tangan dikesan sepanjang rakaman.");
            setMsg(`Menyimpan ${samples.length} sampel...`);
            const data = await saveSamples(label, samples);
            setMsg(`✔ ${data.saved} sampel statik "${data.label}" disimpan (jumlah ${data.counts[data.label]}).`, "ok");
        } catch (e) {
            setMsg("✖ " + e.message, "err");
        } finally {
            cd.style.display = "none";
            track.style.display = "none";
            busy = false; setRecordButtons(running);
        }
    }

    // ---------- Frame-by-Frame Sequence Recording (30 frames) ----------
    async function recordSequence() {
        if (busy) return;
        const label = getActiveLabel();
        if (!label) {
            setMsg("Sila taip atau pilih isyarat sasaran terlebih dahulu.", "err");
            $("sign-search-input").focus();
            return;
        }
        if (!latestLandmarks) {
            setMsg("Tiada tangan dikesan. Sila angkat tangan ke hadapan kamera.", "err");
            return;
        }

        busy = true; setRecordButtons(false);
        const cd = $("studio-countdown");
        const track = $("burst-progress");
        const fill = $("burst-fill");

        try {
            cd.style.display = "flex";
            for (let i = 3; i > 0; i--) {
                cd.textContent = i;
                await sleep(1000);
                if (!running) throw new Error("Kamera ditutup.");
            }
            cd.textContent = "GERAK!";
            await sleep(400);
            cd.style.display = "none";

            const capturedFrames = [];
            track.style.display = "block";
            fill.style.width = "0%";
            const TARGET_FRAMES = 30;
            let lastCaptured = -1;
            const deadline = performance.now() + 10000;

            while (capturedFrames.length < TARGET_FRAMES) {
                if (!running) throw new Error("Kamera ditutup.");
                if (performance.now() > deadline) break;

                if (latestLandmarks && lastVideoTime !== lastCaptured) {
                    lastCaptured = lastVideoTime;
                    capturedFrames.push(normaliseLandmarks(latestLandmarks));
                    fill.style.width = (capturedFrames.length / TARGET_FRAMES * 100) + "%";
                    setMsg(`Merakam klip gerakan "${label}": frame ${capturedFrames.length} / ${TARGET_FRAMES} (Teruskan gerakan tangan!)`);
                } else if (!latestLandmarks) {
                    setMsg(`Menunggu tangan... (${capturedFrames.length} / ${TARGET_FRAMES})`, "err");
                }
                await sleep(25);
            }

            if (capturedFrames.length < 8) {
                throw new Error("Gerakan terlalu pendek atau tangan hilang daripada kamera.");
            }

            setMsg(`Menyimpan 1 klip urutan gerakan (${capturedFrames.length} frame) untuk "${label}"...`);
            const data = await postJson(ROUTES.store, {
                sample_type: 'sequence',
                label,
                frames: capturedFrames,
            });

            sequenceCounts = data.sequence_counts || {};
            renderCounts(data.counts, data.total_all);
            classifierStale = true;
            setMsg(`✔ Klip gerakan "${label}" (30 frame berturutan) berjaya disimpan ke pangkalan data!`, "ok");
        } catch (e) {
            setMsg("✖ " + e.message, "err");
        } finally {
            cd.style.display = "none";
            track.style.display = "none";
            busy = false; setRecordButtons(running);
        }
    }

    // ---------- Modal Delete System (100% Reliable) ----------
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
        if (type === "success") {
            el.style.background = "#dcfce7";
            el.style.color = "#16a34a";
        } else {
            el.style.background = "#fee2e2";
            el.style.color = "#dc2626";
        }
        setTimeout(() => { el.style.display = "none"; }, 5000);
    }

    async function executeDelete() {
        if (!pendingDeleteBody) return;
        const confirmBtn = $("btn-confirm-delete");
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memadam...';

        try {
            const data = await postJson(ROUTES.clear, pendingDeleteBody);
            renderCounts(data.counts, data.total_all);
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

    // Expose functions globally for direct onclick
    window.confirmDelete = function(label) {
        openDeleteModal(
            `Padam Isyarat "${label}"?`,
            `Semua rekod sampel bagi isyarat "${label}" akan dipadam daripada pangkalan data.`,
            { label: label }
        );
    };

    window.confirmDeleteAll = function() {
        openDeleteModal(
            "Padam SEMUA Sampel?",
            "Tindakan ini akan memadam SEMUA rekod isyarat dalam pangkalan data. Tindakan ini tidak boleh dikembalikan.",
            { all: true }
        );
    };

    // ---------- Live test (KNN) ----------
    const classifier = new KnnClassifier(5);
    const smoother = new PredictionSmoother(7);
    let testing = false;
    let classifierStale = true;

    async function loadClassifier() {
        $("test-info").textContent = "Memuatkan dataset...";
        const resp = await fetch(ROUTES.dataset, { headers: { Accept: "application/json" } });
        if (!resp.ok) throw new Error("Gagal memuatkan dataset (" + resp.status + ").");
        const data = await resp.json();
        classifier.load(data.samples || []);
        seqClassifier.load(data.sequences || []);
        classifierStale = false;
        $("test-info").textContent = `${classifier.size} sampel statik, ${seqClassifier.size} klip gerakan DTW (${seqClassifier.labels.join(", ") || "tiada"})`;
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
            $("btn-toggle-test").innerHTML = '<i class="fa-solid fa-stop"></i> Henti Uji';
        } catch (e) {
            $("test-info").textContent = e.message;
        }
    }

    function stopTest() {
        testing = false;
        $("btn-toggle-test").innerHTML = '<i class="fa-solid fa-play"></i> Mula Uji';
        $("test-label").textContent = "—";
        $("test-conf").textContent = "0%";
        $("test-bar").style.width = "0%";
    }

    function runTest() {
        if (!latestLandmarks) {
            smoother.push(null);
            $("test-label").textContent = "—";
            $("test-conf").textContent = "0%";
            $("test-bar").style.width = "0%";
            return;
        }

        // 1. Direct Disambiguation for Pinky-Up shape: I (static) vs J (dynamic hook)
        const isPinky = isPinkyUpShape(latestLandmarks);
        if (isPinky) {
            const jCheck = motionTracker.detectJMotion();
            if (jCheck && jCheck.matched) {
                const conf = jCheck.confidence || 95;
                $("test-label").textContent = "J";
                $("test-conf").textContent = conf + "% (Cangkuk J 🎬)";
                $("test-bar").style.width = conf + "%";
                smoother.push("J");
                return;
            } else {
                $("test-label").textContent = "I";
                $("test-conf").textContent = "95% (Isyarat I Pegun)";
                $("test-bar").style.width = "95%";
                smoother.push("I");
                return;
            }
        }

        // 2. If dynamic sequence templates exist and hand is in motion, test DTW sequence
        if (seqClassifier.size > 0 && !motionTracker.isStill(0.05) && seqBuffer.length >= 15) {
            const seqPred = seqClassifier.predict(seqBuffer.getFrames());
            if (seqPred.confidence >= 60) {
                $("test-label").textContent = seqPred.label;
                $("test-conf").textContent = seqPred.confidence + "% (DTW 🎬)";
                $("test-bar").style.width = seqPred.confidence + "%";
                return;
            }
        }

        // 3. Fallback to static KNN classifier for A, B, C, etc.
        const { label, confidence } = classifier.predict(normaliseLandmarks(latestLandmarks), motionTracker);
        const stable = smoother.push(confidence >= 40 ? label : null);
        $("test-label").textContent = stable ?? "?";
        $("test-conf").textContent = confidence + "%";
        $("test-bar").style.width = confidence + "%";
    }

    // ---------- Wire up ----------
    $("btn-start-camera").addEventListener("click", startCamera);
    $("btn-stop-camera").addEventListener("click", stopCamera);
    $("btn-record-one").addEventListener("click", recordOne);
    $("btn-record-burst").addEventListener("click", () => recordBurst(30));
    $("btn-record-sequence").addEventListener("click", recordSequence);
    $("btn-toggle-test").addEventListener("click", () => (testing ? stopTest() : startTest()));
    $("btn-clear-all").addEventListener("click", window.confirmDeleteAll);
    $("btn-cancel-delete").addEventListener("click", closeDeleteModal);
    $("btn-confirm-delete").addEventListener("click", executeDelete);
    $("delete-modal").addEventListener("click", e => {
        if (e.target === $("delete-modal")) closeDeleteModal();
    });

    // Synchronize Search Input with Dropdown & Active Info
    $("sign-search-input").addEventListener("input", updateActiveSignInfo);
    $("sign-select-dropdown").addEventListener("change", e => {
        if (e.target.value) {
            $("sign-search-input").value = e.target.value;
            updateActiveSignInfo();
        }
    });

    // Toggle New Sign Box
    $("btn-toggle-new-sign").addEventListener("click", () => {
        const box = $("new-sign-box");
        const isHidden = box.style.display === "none";
        box.style.display = isHidden ? "block" : "none";
        if (isHidden) {
            $("new-sign-word").focus();
        }
    });

    $("btn-close-new-sign").addEventListener("click", () => {
        $("new-sign-box").style.display = "none";
    });

    // Auto-fill label uppercase from word
    $("new-sign-word").addEventListener("input", e => {
        const val = e.target.value.trim().replace(/\s+/g, '_').toUpperCase();
        $("new-sign-label").value = val;
    });

    // Save New Sign via AJAX
    $("btn-save-new-sign").addEventListener("click", async () => {
        const word = ($("new-sign-word").value || "").trim();
        const label = ($("new-sign-label").value || "").trim().toUpperCase();
        const categoryId = $("new-sign-category").value;

        if (!word || !label) {
            alert("Sila masukkan Perkataan dan Label AI untuk isyarat baru.");
            return;
        }

        const btn = $("btn-save-new-sign");
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

        try {
            const data = await postJson(ROUTES.newSign, {
                word: word,
                label: label,
                category_id: categoryId
            });

            // Add to datalist
            const datalist = $("sign-datalist");
            const opt = document.createElement("option");
            opt.value = label;
            opt.textContent = `${word} (Kategori Baru)`;
            datalist.appendChild(opt);

            // Add to companion dropdown
            const dropdown = $("sign-select-dropdown");
            const dropOpt = document.createElement("option");
            dropOpt.value = label;
            dropOpt.textContent = `${word} (${label})`;
            dropdown.appendChild(dropOpt);

            // Set as active sign
            $("sign-search-input").value = label;
            updateActiveSignInfo();

            // Clear and hide creation form
            $("new-sign-word").value = "";
            $("new-sign-label").value = "";
            $("new-sign-box").style.display = "none";

            showListMsg(`✔ Isyarat "${word}" (${label}) berjaya didaftarkan!`, "success");
            setMsg(`✔ Isyarat "${word}" (${label}) kini sedia untuk rakaman sampel.`, "ok");
        } catch (e) {
            alert("Ralat menambah isyarat: " + e.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Simpan Isyarat';
        }
    });

    $("import-form").addEventListener("submit", () => {
        $("btn-import").disabled = true;
        $("btn-import").innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengimport...';
    });
    // Spacebar = record one sample (handy while holding a sign)
    document.addEventListener("keydown", e => {
        if (e.code === "Space" && running && !busy && !["INPUT", "SELECT", "TEXTAREA", "BUTTON"].includes(document.activeElement.tagName)) {
            e.preventDefault(); recordOne();
        }
    });
    window.addEventListener("beforeunload", () => stream?.getTracks().forEach(t => t.stop()));

    renderCounts(counts, {{ $totalSamples }});
    updateActiveSignInfo();
</script>
@endpush
@endsection

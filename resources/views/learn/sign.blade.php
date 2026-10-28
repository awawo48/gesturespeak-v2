@extends('layouts.app', ['title' => 'Belajar: '.$sign->word])

@push('styles')
<style>
    .practice-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-top: 1.5rem;
    }
    @media (max-width: 900px) {
        .practice-container {
            grid-template-columns: 1fr;
        }
    }
    .camera-box {
        position: relative;
        background: #1e272e;
        border-radius: var(--radius-lg);
        overflow: hidden;
        aspect-ratio: 4/3;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 4px solid var(--border-light);
        box-shadow: var(--shadow-md);
    }
    .camera-video, .camera-canvas {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1); /* Mirror camera for natural feel */
    }
    .camera-canvas {
        pointer-events: none;
    }
    .feedback-overlay {
        position: absolute;
        bottom: 12px;
        left: 12px;
        right: 12px;
        padding: 0.8rem 1rem;
        border-radius: var(--radius-md);
        background: rgba(0, 0, 0, 0.75);
        color: #fff;
        font-weight: 800;
        text-align: center;
        backdrop-filter: blur(8px);
        transition: all 0.3s ease;
    }
    .feedback-success {
        background: rgba(0, 184, 148, 0.9) !important;
        transform: scale(1.02);
    }
    .face-hud-pill {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 3;
        padding: 0.3rem 0.7rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 800;
        color: #fff;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(6px);
        display: none;
        align-items: center;
        gap: 0.35rem;
        pointer-events: none;
    }
    .face-hud-pill.ok {
        background: rgba(5, 150, 105, 0.88);
    }
    .face-hud-pill.warn {
        background: rgba(217, 119, 6, 0.85);
    }
    .face-toggle-btn {
        border: 1.5px solid #cbd5e1;
        background: #f8fafc;
        color: #475569;
        font-size: 0.76rem;
        font-weight: 800;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.2s ease;
    }
    .face-toggle-btn.active {
        background: #ecfdf5;
        border-color: #10b981;
        color: #047857;
    }
</style>
@endpush

@section('content')
{{-- TOP NAVIGATION BAR --}}
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.8rem;">
    <a href="{{ route('learn.category', $category) }}" class="btn btn-outline btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Modul {{ $category->name }}
    </a>

    <div style="font-weight: 800; color: var(--text-muted); font-size: 0.95rem;">
        Isyarat {{ $currentIndex }} daripada {{ $totalSigns }}
    </div>

    <div style="display: flex; gap: 0.5rem;">
        @if($prevSign)
            <a href="{{ route('learn.sign', [$category, $prevSign]) }}" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-chevron-left"></i> Sebelum
            </a>
        @endif
        @if($nextSign)
            <a href="{{ route('learn.sign', [$category, $nextSign]) }}" class="btn btn-primary btn-sm">
                Seterusnya <i class="fa-solid fa-chevron-right"></i>
            </a>
        @endif
    </div>
</div>

<div class="practice-container">

    {{-- LEFT COLUMN: DEMO & INSTRUCTIONS --}}
    <div>
        <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div>
                    <h1 style="font-size: 2.8rem; font-family: var(--font-display); color: var(--primary); margin: 0; line-height: 1;">
                        {{ $sign->word }}
                    </h1>
                    <div style="font-size: 1.1rem; color: var(--text-muted); margin-top: 0.3rem;">
                        {{ $sign->meaning_en ?? $sign->word }}
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.4rem;">
                    <button type="button" class="btn btn-accent btn-sm" onclick="speakWord('{{ $sign->word }}')" title="Dengar sebutan audio perkataan ini">
                        <i class="fa-solid fa-volume-high"></i> Sebut Perkataan
                    </button>
                    @if($progress->mastered)
                        <span class="star-pill" style="font-size: 0.85rem; padding: 0.2rem 0.6rem;">
                            ⭐ Dikuasai!
                        </span>
                    @endif
                </div>
            </div>

            {{-- VIDEO DEMO / FLASHCARD IMAGE --}}
            @if($sign->video && file_exists(public_path($sign->video)))
                <div style="border-radius: var(--radius-md); overflow: hidden; background: #000; margin-bottom: 1rem;">
                    <video controls autoplay loop muted playsinline style="width: 100%; max-height: 320px; display: block; object-fit: contain;">
                        <source src="{{ asset($sign->video) }}" type="video/mp4">
                        Pelayar anda tidak menyokong video HTML5.
                    </video>
                </div>
            @elseif($sign->image && file_exists(public_path($sign->image)))
                <div style="text-align: center; padding: 1.5rem; background: #f8fafc; border-radius: var(--radius-md); margin-bottom: 1rem;">
                    <img src="{{ asset($sign->image) }}" alt="{{ $sign->word }}" style="max-height: 220px; width: auto; object-fit: contain;">
                </div>
            @endif

            {{-- STEP BY STEP GUIDANCE --}}
            <div style="background: #f8fafc; border-radius: var(--radius-md); padding: 1rem 1.2rem; border-left: 4px solid var(--primary);">
                <div style="font-weight: 800; font-size: 0.95rem; margin-bottom: 0.3rem;">
                    <i class="fa-solid fa-hand-holding-hand" style="color: var(--primary);"></i> Panduan Gerak Isyarat:
                </div>
                <p style="font-size: 0.95rem; color: var(--text-dark); margin: 0;">
                    {{ $sign->instructions ?? 'Tonton video di atas dan tiru kedudukan jari dengan cermat di hadapan kamera.' }}
                </p>
            </div>
        </div>
    </div>

    {{-- RIGHT COLUMN: REAL-TIME AI CAMERA STUDIO --}}
    <div>
        <div class="card" style="padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <h2 style="font-size: 1.4rem; margin: 0;">
                    <i class="fa-solid fa-camera" style="color: var(--secondary);"></i> Praktis Kamera AI
                </h2>
                <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap;">
                    <button type="button" id="btn-toggle-face" class="face-toggle-btn active" title="Aktifkan/Matikan Pengesan Wajah & Ekspresi BIM untuk ketepatan lebih tinggi">
                        <i class="fa-solid fa-face-smile"></i> AI Wajah
                    </button>
                    <span id="ai-status-badge" style="font-size: 0.8rem; font-weight: 800; background: #edf2f7; color: var(--text-muted); padding: 0.2rem 0.6rem; border-radius: var(--radius-full);">
                        Kamera Ditutup
                    </span>
                </div>
            </div>

            @if($sign->label === 'J')
                <div style="background: rgba(108, 92, 231, 0.1); border: 1px solid rgba(108, 92, 231, 0.3); padding: 0.6rem 1rem; border-radius: var(--radius-md); margin-bottom: 0.8rem; font-size: 0.85rem; color: #5041c9; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <span>Panduan Gerakan J: Angkat kelengkeng tegak, kemudian lukis bentuk cangkuk "J" di udara! Jejak neon akan muncul di skrin.</span>
                </div>
            @elseif(!$sign->is_static)
                <div style="background: rgba(9, 132, 227, 0.1); border: 1px solid rgba(9, 132, 227, 0.3); padding: 0.6rem 1rem; border-radius: var(--radius-md); margin-bottom: 0.8rem; font-size: 0.85rem; color: #0984e3; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-film"></i>
                    <span>Isyarat Pergerakan (Dinamik): Buka kamera AI dan tiru pergerakan tangan <strong>{{ $sign->word }}</strong> mengikut video panduan!</span>
                </div>
            @endif

            <div class="camera-box" id="camera-box">
                <video id="webcam" class="camera-video" autoplay muted playsinline style="display: none;"></video>
                <canvas id="output_canvas" class="camera-canvas"></canvas>

                <div id="face-hud-pill" class="face-hud-pill">
                    <span>😊 Wajah: Memuat...</span>
                </div>

                <div id="camera-placeholder" style="text-align: center; padding: 2rem; color: #fff; position: relative; z-index: 2;">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem; color: var(--secondary);">📷</div>
                    <div style="font-weight: 800; font-size: 1.1rem; margin-bottom: 0.4rem;">Sedia untuk berlatih?</div>
                    <p style="font-size: 0.85rem; color: #a4b0be; max-width: 280px; margin: 0 auto 1rem;">
                        Tekan butang di bawah untuk hidupkan kamera dan tunjukkan isyarat <strong>{{ $sign->word }}</strong>.
                    </p>
                    <div id="camera-error" style="display: none; background: rgba(220, 38, 38, 0.9); color: #fff; font-size: 0.8rem; font-weight: 700; padding: 0.6rem 0.9rem; border-radius: var(--radius-sm); max-width: 320px; margin: 0 auto 1rem; line-height: 1.4;"></div>
                    <button type="button" class="btn btn-secondary" id="btn-start-camera">
                        <i class="fa-solid fa-video"></i> Buka Kamera AI
                    </button>
                </div>

                <div id="feedback-overlay" class="feedback-overlay" style="display: none; z-index: 3;">
                    <span id="feedback-text">Arahkan tangan anda ke kamera...</span>
                </div>
            </div>

            {{-- CAMERA CONTROLS & CONFIDENCE METER --}}
            <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 1; margin-right: 1rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 800; margin-bottom: 0.2rem;">
                        <span>Ketepatan Padanan AI</span>
                        <span id="confidence-pct">0%</span>
                    </div>
                    <div class="progress-track" style="height: 8px;">
                        <div id="confidence-bar" class="progress-fill" style="width: 0%; background: var(--secondary);"></div>
                    </div>
                </div>

                <button type="button" id="btn-stop-camera" class="btn btn-outline btn-sm" style="display: none;">
                    <i class="fa-solid fa-stop"></i> Tutup
                </button>
            </div>

            {{-- MANUAL FALLBACK BUTTON --}}
            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px dashed var(--border-light); text-align: center;">
                <span style="font-size: 0.85rem; color: var(--text-muted); display: block; margin-bottom: 0.5rem;">
                    Tiada kamera atau mahu tandakan selesai secara manual?
                </span>
                <button type="button" class="btn btn-outline btn-sm" onclick="manualComplete()">
                    <i class="fa-solid fa-check"></i> Tandakan Isyarat Ini Sebagai Selesai
                </button>
            </div>

        </div>
    </div>

</div>

{{-- JAVASCRIPT: MEDIAPIPE HANDS + TTS + ASYNC PROGRESS --}}
@push('scripts')
{{-- MediaPipe Tasks Vision CDN --}}
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

    let handLandmarker = null;
    let faceLandmarker = null;
    let visionRuntimePromise = null;
    let initPromise = null;
    let faceInitPromise = null;
    let modelLoadFailed = false;
    let runningMode = "VIDEO";
    let webcamRunning = false;
    let isStartingCamera = false;
    let activeStream = null;
    let enableFaceAi = true;
    let latestFaceLandmarks = null;
    let latestFaceBlendshapes = null;
    let latestFaceContext = null;
    let faceFrameTick = 0;
    let lastHandTs = -1;
    let lastFaceTs = -1;

    const video = document.getElementById("webcam");
    const canvasElement = document.getElementById("output_canvas");
    const canvasCtx = canvasElement ? canvasElement.getContext("2d") : null;
    const placeholderEl = document.getElementById("camera-placeholder");
    const errorEl = document.getElementById("camera-error");
    const btnStart = document.getElementById("btn-start-camera");
    const btnStop = document.getElementById("btn-stop-camera");
    const btnToggleFace = document.getElementById("btn-toggle-face");
    const faceHudPill = document.getElementById("face-hud-pill");
    const feedbackOverlay = document.getElementById("feedback-overlay");
    const feedbackText = document.getElementById("feedback-text");
    const confidenceBar = document.getElementById("confidence-bar");
    const confidencePct = document.getElementById("confidence-pct");
    const badge = document.getElementById("ai-status-badge");

    const targetSign = @json(strtoupper($sign->label));
    const signWord = @json($sign->word);
    const isStaticSign = {{ $sign->is_static ? 'true' : 'false' }};
    let isMastered = {{ $progress->mastered ? 'true' : 'false' }};
    let consecutiveMatches = 0;

    const knn = new KnnClassifier(5);
    const smoother = new PredictionSmoother(5);
    const motionTracker = new HandMotionTracker(45);
    const seqClassifier = new SequenceClassifier(30);
    const seqBuffer = new SequenceBuffer(45);
    let mlReady = false;

    function showCameraError(msg) {
        if (!errorEl) return;
        if (msg) {
            errorEl.textContent = msg;
            errorEl.style.display = "block";
        } else {
            errorEl.textContent = "";
            errorEl.style.display = "none";
        }
    }

    async function loadDataset() {
        try {
            const resp = await fetch("{{ route('ai.public_dataset') }}", { headers: { Accept: "application/json" } });
            if (resp.ok) {
                const data = await resp.json();
                knn.load(data.samples || []);
                if (data.sequences) {
                    seqClassifier.load(data.sequences);
                }
                mlReady = knn.labels.includes(targetSign) || seqClassifier.labels.includes(targetSign);
                if (mlReady && badge && !webcamRunning) {
                    badge.innerText = seqClassifier.labels.includes(targetSign)
                        ? "Model AI Gerakan (DTW Aktif) 🎬"
                        : "Model AI Pintar (ML Aktif) 🤖";
                }
            }
        } catch (e) {
            console.warn("Gagal memuatkan dataset AI:", e);
        }
    }
    loadDataset();

    // Shared MediaPipe WASM Runtime + Hybrid Hand & Face Landmarkers
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

    async function initFaceMediaPipe() {
        if (faceLandmarker) return faceLandmarker;
        if (faceInitPromise) return faceInitPromise;

        faceInitPromise = (async () => {
            try {
                if (faceHudPill && webcamRunning && enableFaceAi) {
                    faceHudPill.style.display = "inline-flex";
                    faceHudPill.className = "face-hud-pill";
                    faceHudPill.innerText = "😊 Wajah: Memuat AI...";
                }
                const { mod, fileset } = await getVisionRuntime();
                const { FaceLandmarker } = mod;
                const faceOptions = delegate => ({
                    baseOptions: {
                        modelAssetPath: "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task",
                        delegate,
                    },
                    runningMode: runningMode,
                    numFaces: 1,
                    outputFaceBlendshapes: true,
                    minFaceDetectionConfidence: 0.5,
                    minTrackingConfidence: 0.5,
                });
                try {
                    faceLandmarker = await FaceLandmarker.createFromOptions(fileset, faceOptions("GPU"));
                } catch (gpuErr) {
                    console.warn("FaceLandmarker GPU gagal, guna CPU fallback:", gpuErr);
                    faceLandmarker = await FaceLandmarker.createFromOptions(fileset, faceOptions("CPU"));
                }
                return faceLandmarker;
            } catch (err) {
                console.warn("Gagal memuatkan FaceLandmarker (mod tangan sahaja diteruskan):", err);
                if (faceHudPill) {
                    faceHudPill.innerText = "Wajah: Mod Tangan Sahaja";
                    faceHudPill.className = "face-hud-pill warn";
                }
                return null;
            } finally {
                faceInitPromise = null;
            }
        })();

        return faceInitPromise;
    }

    async function initMediaPipe() {
        if (handLandmarker) return handLandmarker;
        if (initPromise) return initPromise;

        initPromise = (async () => {
            try {
                if (badge && !webcamRunning) badge.innerText = "Memuatkan Model AI...";
                const { mod, fileset } = await getVisionRuntime();
                const { HandLandmarker } = mod;
                const options = delegate => ({
                    baseOptions: {
                        modelAssetPath: "https://storage.googleapis.com/mediapipe-models/hand_landmarker/hand_landmarker/float16/1/hand_landmarker.task",
                        delegate,
                    },
                    runningMode: runningMode,
                    numHands: 1,
                    minHandDetectionConfidence: 0.5,
                    minTrackingConfidence: 0.5,
                });

                try {
                    handLandmarker = await HandLandmarker.createFromOptions(fileset, options("GPU"));
                } catch (gpuErr) {
                    console.warn("GPU delegate gagal, guna CPU fallback:", gpuErr);
                    handLandmarker = await HandLandmarker.createFromOptions(fileset, options("CPU"));
                }

                if (enableFaceAi && !faceLandmarker) {
                    initFaceMediaPipe();
                }

                modelLoadFailed = false;
                if (badge) {
                    if (webcamRunning) {
                        badge.innerText = enableFaceAi ? "Mengimbas Tangan + Wajah 🟢" : "Sedang Mengimbas Tangan 🟢";
                        badge.style.background = "#dcfce7";
                        badge.style.color = "#16a34a";
                    } else {
                        badge.innerText = "Model AI Bersedia";
                    }
                }
                return handLandmarker;
            } catch (e) {
                console.error("Gagal memuatkan MediaPipe:", e);
                modelLoadFailed = true;
                if (badge) {
                    badge.innerText = webcamRunning ? "Kamera Aktif (Tanpa AI)" : "Model AI Luar Talian";
                }
                return null;
            } finally {
                initPromise = null;
            }
        })();

        return initPromise;
    }

    // Start Webcam immediately on button click (do not block camera opening on CDN download)
    async function startCamera() {
        if (webcamRunning || isStartingCamera) return;
        showCameraError("");

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            const insecureMsg = "Pelayar menyekat akses kamera. Sila buka laman ini melalui http://localhost (atau https://) dan benarkan akses kamera.";
            showCameraError(insecureMsg);
            return;
        }

        isStartingCamera = true;
        if (btnStart) {
            btnStart.disabled = true;
            btnStart.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Membuka Kamera...';
        }

        try {
            let stream;
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: "user" },
                    audio: false,
                });
            } catch (constraintErr) {
                if (constraintErr.name === "OverconstrainedError" || constraintErr.name === "ConstraintNotSatisfiedError") {
                    stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                } else {
                    throw constraintErr;
                }
            }

            activeStream = stream;
            video.style.display = "block";
            video.srcObject = stream;

            try {
                await video.play();
            } catch (playErr) {
                console.warn("Autoplay notice:", playErr);
            }

            if (video.readyState < 2) {
                await Promise.race([
                    new Promise(res => video.addEventListener("loadeddata", res, { once: true })),
                    new Promise(res => setTimeout(res, 1500)),
                ]);
            }

            canvasElement.width = video.videoWidth || 640;
            canvasElement.height = video.videoHeight || 480;
            if (placeholderEl) placeholderEl.style.display = "none";
            if (btnStop) btnStop.style.display = "inline-flex";
            if (faceHudPill) faceHudPill.style.display = enableFaceAi ? "inline-flex" : "none";
            if (feedbackOverlay) {
                feedbackOverlay.className = "feedback-overlay";
                feedbackOverlay.style.display = "block";
            }

            motionTracker.reset();
            seqBuffer.reset();
            smoother.reset();
            consecutiveMatches = 0;
            lastVideoTime = -1;
            webcamRunning = true;

            if (handLandmarker) {
                if (enableFaceAi && !faceLandmarker) initFaceMediaPipe();
                if (badge) {
                    badge.innerText = enableFaceAi ? "Mengimbas Tangan + Wajah 🟢" : "Sedang Mengimbas Tangan 🟢";
                    badge.style.background = "#dcfce7";
                    badge.style.color = "#16a34a";
                }
                if (feedbackText) feedbackText.innerText = "Arahkan tangan anda ke dalam kotak kamera...";
            } else {
                if (badge) {
                    badge.innerText = "Memuatkan AI Tangan & Wajah...";
                    badge.style.background = "#fef3c7";
                    badge.style.color = "#d97706";
                }
                if (feedbackText) feedbackText.innerText = "Kamera aktif! Memuatkan pengesan AI...";
                initMediaPipe();
            }

            window.requestAnimationFrame(predictWebcam);
        } catch (err) {
            console.error("Kamera tidak dapat diakses:", err);
            activeStream?.getTracks().forEach(t => t.stop());
            activeStream = null;
            video.srcObject = null;
            video.style.display = "none";

            const msg = err.name === "NotAllowedError" || err.name === "PermissionDeniedError"
                ? "Akses kamera ditolak. Sila klik ikon kunci/kamera di bar alamat pelayar dan pilih 'Allow' (Benarkan)."
                : err.name === "NotFoundError" || err.name === "DevicesNotFoundError"
                ? "Tiada kamera dijumpai pada peranti ini."
                : err.name === "NotReadableError" || err.name === "TrackStartError"
                ? "Kamera sedang digunakan oleh aplikasi atau tab lain. Sila tutup aplikasi tersebut dan cuba lagi."
                : "Gagal membuka kamera: " + (err.message || err.name);
            showCameraError(msg);
        } finally {
            isStartingCamera = false;
            if (btnStart) {
                btnStart.disabled = false;
                btnStart.innerHTML = '<i class="fa-solid fa-video"></i> Buka Kamera AI';
            }
        }
    }

    // Stop Webcam
    function stopCamera() {
        webcamRunning = false;
        latestFaceLandmarks = null;
        latestFaceBlendshapes = null;
        latestFaceContext = null;
        if (activeStream) {
            activeStream.getTracks().forEach(track => track.stop());
            activeStream = null;
        }
        if (video && video.srcObject) {
            video.srcObject.getTracks().forEach(track => track.stop());
            video.srcObject = null;
        }
        if (video) video.style.display = "none";
        if (canvasCtx && canvasElement) {
            canvasCtx.clearRect(0, 0, canvasElement.width, canvasElement.height);
        }
        if (placeholderEl) placeholderEl.style.display = "block";
        if (btnStop) btnStop.style.display = "none";
        if (faceHudPill) faceHudPill.style.display = "none";
        if (feedbackOverlay) feedbackOverlay.style.display = "none";
        if (confidencePct) confidencePct.innerText = "0%";
        if (confidenceBar) confidenceBar.style.width = "0%";
        if (badge) {
            badge.innerText = "Kamera Ditutup";
            badge.style.background = "#edf2f7";
            badge.style.color = "var(--text-muted)";
        }
    }

    if (btnToggleFace) {
        btnToggleFace.addEventListener("click", () => {
            enableFaceAi = !enableFaceAi;
            btnToggleFace.classList.toggle("active", enableFaceAi);
            if (enableFaceAi) {
                if (webcamRunning && !faceLandmarker) initFaceMediaPipe();
                if (faceHudPill && webcamRunning) faceHudPill.style.display = "inline-flex";
                if (badge && webcamRunning && handLandmarker) badge.innerText = "Mengimbas Tangan + Wajah 🟢";
            } else {
                latestFaceLandmarks = null;
                latestFaceBlendshapes = null;
                latestFaceContext = null;
                if (faceHudPill) faceHudPill.style.display = "none";
                if (badge && webcamRunning && handLandmarker) badge.innerText = "Sedang Mengimbas Tangan 🟢";
            }
        });
    }

    window.startCamera = startCamera;
    window.stopCamera = stopCamera;
    if (btnStart) btnStart.addEventListener("click", startCamera);
    if (btnStop) btnStop.addEventListener("click", stopCamera);
    window.addEventListener("pagehide", stopCamera);
    window.addEventListener("beforeunload", stopCamera);

    // Prediction Loop (Hand every frame + Face interleaved every 2nd frame)
    let lastVideoTime = -1;
    function predictWebcam() {
        if (!webcamRunning) return;

        if (!handLandmarker) {
            if (modelLoadFailed && feedbackText) {
                feedbackText.innerText = "Kamera aktif (Mod Cermin). Tiru isyarat di sebelah dan tekan selesai di bawah.";
            }
            window.requestAnimationFrame(predictWebcam);
            return;
        }

        if (video.readyState >= 2 && video.videoWidth > 0 && lastVideoTime !== video.currentTime) {
            lastVideoTime = video.currentTime;
            if (canvasElement.width !== video.videoWidth || canvasElement.height !== video.videoHeight) {
                canvasElement.width = video.videoWidth;
                canvasElement.height = video.videoHeight;
            }

            let results = null;
            try {
                const handTs = Math.max(performance.now(), lastHandTs + 1);
                lastHandTs = handTs;
                results = handLandmarker.detectForVideo(video, handTs);
            } catch (detectErr) {
                console.warn("Ralat imbasan frame:", detectErr);
            }

            if (enableFaceAi && faceLandmarker && (++faceFrameTick % 2) === 0) {
                try {
                    const faceTs = Math.max(performance.now(), lastFaceTs + 1);
                    lastFaceTs = faceTs;
                    const faceRes = faceLandmarker.detectForVideo(video, faceTs);
                    latestFaceLandmarks = faceRes?.faceLandmarks?.[0] || null;
                    latestFaceBlendshapes = faceRes?.faceBlendshapes?.[0]?.categories || null;
                } catch (faceErr) {
                    console.warn("Ralat imbasan wajah:", faceErr);
                }
            }

            const landmarks = (results && results.landmarks && results.landmarks.length > 0)
                ? results.landmarks[0]
                : null;

            latestFaceContext = enableFaceAi
                ? extractFaceContext(landmarks, latestFaceLandmarks, latestFaceBlendshapes)
                : null;

            if (faceHudPill) {
                if (!enableFaceAi) {
                    faceHudPill.style.display = "none";
                } else {
                    faceHudPill.style.display = "inline-flex";
                    if (!faceLandmarker) {
                        faceHudPill.innerText = "😊 Wajah: Memuat AI...";
                        faceHudPill.className = "face-hud-pill";
                    } else if (latestFaceContext?.hasFace) {
                        const zoneTag = (latestFaceContext.zone && latestFaceContext.zone !== "bebas")
                            ? ` • Zon ${latestFaceContext.zoneLabel}`
                            : "";
                        faceHudPill.innerText = `Wajah: ${latestFaceContext.expressionLabel}${zoneTag}`;
                        faceHudPill.className = "face-hud-pill ok";
                    } else {
                        faceHudPill.innerText = "Wajah: Tidak Kelihatan";
                        faceHudPill.className = "face-hud-pill warn";
                    }
                }
            }

            canvasCtx.save();
            canvasCtx.clearRect(0, 0, canvasElement.width, canvasElement.height);

            if (enableFaceAi && latestFaceLandmarks && latestFaceContext?.hasFace) {
                drawFaceAnchors(canvasCtx, latestFaceLandmarks, canvasElement.width, canvasElement.height, latestFaceContext);
            }

            if (landmarks) {
                const featVec = buildFeatureVector(landmarks, enableFaceAi ? latestFaceContext : null);
                motionTracker.push(landmarks);
                seqBuffer.push(featVec);
                drawHand(canvasCtx, landmarks, canvasElement.width, canvasElement.height);

                // Draw sparkling neon trajectory trail for J or dynamic movement signs
                if (targetSign === 'J' || (!isStaticSign && !motionTracker.isStill(0.035))) {
                    const trail = motionTracker.getTrail();
                    const jActive = targetSign === 'J' ? motionTracker.detectJMotion().matched : true;
                    if (trail.length > 1) {
                        canvasCtx.save();
                        canvasCtx.beginPath();
                        canvasCtx.strokeStyle = jActive ? "#2ed573" : "#ffeaa7";
                        canvasCtx.lineWidth = 6;
                        canvasCtx.lineCap = "round";
                        canvasCtx.lineJoin = "round";
                        canvasCtx.shadowColor = jActive ? "#2ed573" : "#fdcb6e";
                        canvasCtx.shadowBlur = 14;
                        trail.forEach((pt, i) => {
                            const tx = pt.x * canvasElement.width;
                            const ty = pt.y * canvasElement.height;
                            if (i === 0) canvasCtx.moveTo(tx, ty);
                            else canvasCtx.lineTo(tx, ty);
                        });
                        canvasCtx.stroke();
                        canvasCtx.restore();
                    }
                }

                // Evaluate static or dynamic BIM gesture (Hand + Face Hybrid)
                const evalResult = evaluateGesture(landmarks, targetSign);
                const displayConf = Math.max(0, Math.min(100, Math.round(evalResult.confidence || 0)));
                if (confidencePct) confidencePct.innerText = displayConf + "%";
                if (confidenceBar) confidenceBar.style.width = displayConf + "%";

                // J hook needs 2 frames, dynamic signs need 6 frames, static signs need 5 frames
                const targetFramesRequired = targetSign === 'J' ? 2 : (!isStaticSign ? 6 : 5);

                if (evalResult.matched) {
                    consecutiveMatches++;
                    if (feedbackText) {
                        const praisePrefix = evalResult.faceHintOk ? `${evalResult.faceHintOk} ` : "Bagus! ";
                        feedbackText.innerText = isStaticSign
                            ? `${praisePrefix}Tahan (${consecutiveMatches}/${targetFramesRequired})...`
                            : `${praisePrefix}Teruskan (${consecutiveMatches}/${targetFramesRequired})...`;
                    }
                    if (feedbackOverlay) feedbackOverlay.className = "feedback-overlay feedback-success";

                    if (consecutiveMatches >= targetFramesRequired) {
                        onGestureSuccess(displayConf);
                        consecutiveMatches = 0;
                    }
                } else {
                    consecutiveMatches = Math.max(0, consecutiveMatches - 1);
                    if (feedbackText) feedbackText.innerText = evalResult.hint || "Arahkan jari mengikut panduan...";
                    if (feedbackOverlay) feedbackOverlay.className = "feedback-overlay";
                }
            } else {
                if (feedbackText) feedbackText.innerText = "Arahkan tangan anda ke dalam kotak kamera...";
                if (feedbackOverlay) feedbackOverlay.className = "feedback-overlay";
                if (confidencePct) confidencePct.innerText = "0%";
                if (confidenceBar) confidenceBar.style.width = "0%";
            }
            canvasCtx.restore();
        }

        window.requestAnimationFrame(predictWebcam);
    }

    // Draw skeleton joints on canvas
    function drawHand(ctx, landmarks, w, h) {
        ctx.strokeStyle = "#00cec9";
        ctx.lineWidth = 4;
        ctx.fillStyle = "#ff7675";

        const connections = [
            [0,1],[1,2],[2,3],[3,4], // Thumb
            [0,5],[5,6],[6,7],[7,8], // Index
            [0,9],[9,10],[10,11],[11,12], // Middle
            [0,13],[13,14],[14,15],[15,16], // Ring
            [0,17],[17,18],[18,19],[19,20], // Pinky
            [5,9],[9,13],[13,17],[0,17] // Palm base
        ];

        connections.forEach(([i, j]) => {
            ctx.beginPath();
            ctx.moveTo(landmarks[i].x * w, landmarks[i].y * h);
            ctx.lineTo(landmarks[j].x * w, landmarks[j].y * h);
            ctx.stroke();
        });

        landmarks.forEach(p => {
            ctx.beginPath();
            ctx.arc(p.x * w, p.y * h, 5, 0, 2 * Math.PI);
            ctx.fill();
        });
    }

    // ML + DTW + Face Context + Heuristic Hybrid Classifier for BIM Signs
    function evaluateGesture(lm, target) {
        // Tips: Thumb(4), Index(8), Middle(12), Ring(16), Pinky(20)
        // PIPs: Index(6), Middle(10), Ring(14), Pinky(18)
        const isExtended = (tip, pip) => lm[tip].y < lm[pip].y;

        const indexUp = isExtended(8, 6);
        const middleUp = isExtended(12, 10);
        const ringUp = isExtended(16, 14);
        const pinkyUp = isExtended(20, 18);
        const thumbOut = Math.abs(lm[4].x - lm[2].x) > 0.05;

        const activeFaceCtx = enableFaceAi ? latestFaceContext : null;
        const faceBoost = (!isHandOnlyLabel(target) && activeFaceCtx?.hasFace)
            ? evaluateFaceSignBoost(target, activeFaceCtx)
            : null;

        // SPECIAL CASE 1: TARGET J (DYNAMIC HOOK)
        if (target === 'J') {
            const isPinky = isPinkyUpShape(lm);
            if (isPinky) {
                const jCheck = motionTracker ? motionTracker.detectJMotion() : { matched: false };
                if (jCheck && jCheck.matched) {
                    return {
                        matched: true,
                        confidence: jCheck.confidence || 96,
                        hint: "Hebat! Gerakan cangkuk J dikesan dengan sempurna! ⭐"
                    };
                }
                return {
                    matched: false,
                    confidence: 70,
                    hint: "Bagus! Sekarang lukis bentuk cangkuk J di udara (turun ke bawah lalu lengkung ke atas)."
                };
            }
            return {
                matched: false,
                confidence: 30,
                hint: "Tegakkan jari kelengkeng dahulu, kemudian buat pergerakan cangkuk J."
            };
        }

        // SPECIAL CASE 2: TARGET I (STATIC PINKY UP)
        if (target === 'I') {
            const isPinky = isPinkyUpShape(lm);
            if (isPinky) {
                const jCheck = motionTracker ? motionTracker.detectJMotion() : { matched: false };
                if (jCheck && jCheck.matched) {
                    return {
                        matched: false,
                        confidence: 60,
                        hint: "Ini isyarat J (cangkuk). Untuk isyarat I, tahan tangan pegun tanpa melukis cangkuk."
                    };
                }
                return {
                    matched: true,
                    confidence: 96,
                    hint: "Bagus! Tahan isyarat I pegun..."
                };
            }
            return {
                matched: false,
                confidence: 30,
                hint: "Tegakkan hanya jari kelengkeng ke atas untuk isyarat I."
            };
        }

        // 1. Dynamic Sequence DTW Classifier (if trained 30-frame sequence clips exist for this sign)
        if (seqClassifier.size > 0 && seqClassifier.labels.includes(target)) {
            if (faceBoost?.strictBlocked) {
                return {
                    matched: false,
                    confidence: 45,
                    hint: faceBoost.hintNeed || ("Dekatkan tangan ke kawasan wajah yang betul untuk " + signWord + "...")
                };
            }
            if (seqBuffer.length >= 12 && !motionTracker.isStill(0.035)) {
                const seqPred = seqClassifier.predict(seqBuffer.getFrames(), activeFaceCtx);
                if (seqPred.label === target && seqPred.confidence >= 55) {
                    return {
                        matched: true,
                        confidence: seqPred.confidence,
                        faceHintOk: faceBoost?.hintOk || null,
                        hint: faceBoost?.hintOk || ("Hebat! Gerakan AI mengesahkan isyarat " + signWord + " (" + seqPred.confidence + "%).")
                    };
                }
                return {
                    matched: false,
                    confidence: Math.max(25, seqPred.label === target ? seqPred.confidence : 35),
                    hint: faceBoost?.hintNeed || ("Teruskan pergerakan isyarat " + signWord + " mengikut video panduan...")
                };
            }
            return {
                matched: false,
                confidence: 25,
                hint: faceBoost?.hintNeed || ("Gerakkan tangan anda mengikut video untuk isyarat " + signWord + "...")
            };
        }

        // 2. Static KNN Classifier (Multimodal 78-feature fusion when face context is trained)
        if (knn.size > 0 && knn.labels.includes(target)) {
            const queryVec = buildFeatureVector(lm, activeFaceCtx);
            const pred = knn.predict(queryVec, motionTracker, activeFaceCtx);
            if (faceBoost?.strictBlocked) {
                return {
                    matched: false,
                    confidence: Math.max(30, Math.min(55, pred.confidence)),
                    hint: faceBoost.hintNeed || ("Arahkan tangan ke kawasan wajah yang betul bagi " + signWord + "...")
                };
            }
            if (pred.label === target && pred.confidence >= 50) {
                return {
                    matched: true,
                    confidence: pred.confidence,
                    faceHintOk: faceBoost?.hintOk || null,
                    hint: faceBoost?.hintOk || ("Bagus! Pengecaman AI mengesahkan isyarat " + signWord + " (" + pred.confidence + "%). Tahan...")
                };
            }
            return {
                matched: false,
                confidence: Math.max(25, pred.label === target ? pred.confidence : 35),
                hint: faceBoost?.hintNeed || ("Samakan bentuk jari dan kedudukan tangan mengikut panduan '" + signWord + "'...")
            };
        }

        // 3. Heuristic fallback for dynamic movement signs without custom DTW dataset yet
        if (!isStaticSign) {
            if (faceBoost?.strictBlocked) {
                return {
                    matched: false,
                    confidence: 45,
                    hint: faceBoost.hintNeed || ("Arahkan tangan ke kawasan wajah yang betul bagi '" + signWord + "'...")
                };
            }
            if (motionTracker && !motionTracker.isStill(0.035)) {
                const dynConf = Math.min(98, 86 + (faceBoost?.bonus || 0));
                return {
                    matched: true,
                    confidence: dynConf,
                    faceHintOk: faceBoost?.hintOk || null,
                    hint: faceBoost?.hintOk || "Bagus! Pergerakan tangan dikesan..."
                };
            }
            return {
                matched: false,
                confidence: 45,
                hint: faceBoost?.hintNeed || ("Tiru pergerakan tangan bagi '" + signWord + "' mengikut video panduan...")
            };
        }

        // 4. Heuristic geometry classifier fallback for static signs
        let matched = false;
        let confidence = 40;
        let hint = "Tiru kedudukan jari mengikut panduan...";

        switch(target) {
            case 'A':
            case '1':
                // Fist, thumb upright on side
                if (!indexUp && !middleUp && !ringUp && !pinkyUp && thumbOut) {
                    matched = true;
                    confidence = 92;
                } else {
                    hint = "Genggam jari, letakkan ibu jari di sisi jari telunjuk.";
                }
                break;
            case 'B':
                // Flat 4 fingers up, thumb tucked across palm
                if (indexUp && middleUp && ringUp && pinkyUp) {
                    matched = true;
                    confidence = 90;
                } else {
                    hint = "Tegakkan 4 jari rapat ke atas.";
                }
                break;
            case 'C':
                // Curved arch C-shape
                const cDist = Math.hypot(lm[4].x - lm[8].x, lm[4].y - lm[8].y);
                if (cDist > 0.1 && cDist < 0.25) {
                    matched = true;
                    confidence = 88;
                } else {
                    hint = "Lengkungkan jari menyerupai huruf C.";
                }
                break;
            case 'D':
                // Index pointing straight up, other fingers touch thumb
                if (indexUp && !middleUp && !ringUp && !pinkyUp) {
                    matched = true;
                    confidence = 94;
                } else {
                    hint = "Tegakkan hanya jari telunjuk ke atas.";
                }
                break;
            case 'L':
                // Index and thumb form 90 degree angle
                if (indexUp && thumbOut && !middleUp && !ringUp && !pinkyUp) {
                    matched = true;
                    confidence = 93;
                } else {
                    hint = "Bentuk huruf L dengan ibu jari dan jari telunjuk.";
                }
                break;
            case 'V':
            case '2':
                // Index and Middle up (Peace)
                if (indexUp && middleUp && !ringUp && !pinkyUp) {
                    matched = true;
                    confidence = 94;
                } else {
                    hint = "Buka jari telunjuk dan jari hantu membentuk V.";
                }
                break;
            case 'W':
            case '3':
                // Index, Middle, Ring up
                if (indexUp && middleUp && ringUp && !pinkyUp) {
                    matched = true;
                    confidence = 92;
                } else {
                    hint = "Tegakkan 3 jari tengah ke atas.";
                }
                break;
            case 'Y':
                // Thumb and Pinky extended (Shaka)
                if (thumbOut && pinkyUp && !indexUp && !middleUp && !ringUp) {
                    matched = true;
                    confidence = 95;
                } else {
                    hint = "Buka ibu jari dan kelengkeng keluar.";
                }
                break;
            case '5':
            case 'LIMA':
                // All 5 extended open palm
                if (indexUp && middleUp && ringUp && pinkyUp && thumbOut) {
                    matched = true;
                    confidence = 95;
                } else {
                    hint = "Buka kesemua 5 jari tangan.";
                }
                break;
            default:
                // General active hand presence check for other static signs
                if (lm && lm.length === 21) {
                    matched = true;
                    confidence = 85;
                }
        }

        // Apply BIM Face Zone & Expression verification/boost for contextual signs
        let faceHintOk = null;
        if (faceBoost) {
            if (faceBoost.strictBlocked) {
                matched = false;
                confidence = 45;
                hint = faceBoost.hintNeed || hint;
            } else {
                confidence = Math.min(98, Math.max(25, confidence + faceBoost.bonus));
                if (matched && faceBoost.hintOk) {
                    faceHintOk = faceBoost.hintOk;
                    hint = faceBoost.hintOk;
                } else if (!matched && faceBoost.hintNeed) {
                    hint = faceBoost.hintNeed;
                }
            }
        }

        return { matched, confidence, hint, faceHintOk };
    }

    // Trigger Success Event
    let isSaving = false;
    async function onGestureSuccess(conf) {
        if (isSaving) return;
        isSaving = true;

        speakWord("Tahniah! Isyarat anda betul!");

        // AJAX POST to save progress
        try {
            const resp = await fetch("{{ route('learn.progress.record', $sign) }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Accept": "application/json"
                },
                body: JSON.stringify({ confidence: conf, mastered: true })
            });
            const data = await resp.json();
            if (data.success) {
                // Update star counter on header
                const starNav = document.getElementById("nav-star-count");
                if (starNav) starNav.innerText = data.total_stars;

                if (data.stars_awarded > 0) {
                    alert("🌟 TAHNIAH! Anda berjaya menguasai isyarat " + signWord + "! +" + data.stars_awarded + " Bintang!");
                }
            }
        } catch (e) {
            console.error("Gagal menyimpan kemajuan:", e);
        } finally {
            setTimeout(() => { isSaving = false; }, 2000);
        }
    }

    window.manualComplete = function() {
        onGestureSuccess(90);
    };
</script>

<script>
    // Web Speech API (Text-to-Speech)
    function speakWord(text) {
        if (!('speechSynthesis' in window)) {
            console.log("Web Speech API tidak disokong.");
            return;
        }
        window.speechSynthesis.cancel();
        const utter = new SpeechSynthesisUtterance(text);
        utter.rate = 0.9;
        utter.pitch = 1.1;

        // Try to pick Malay or Indonesian voice if installed
        const voices = window.speechSynthesis.getVoices();
        const myVoice = voices.find(v => v.lang.startsWith('ms') || v.lang.startsWith('id') || v.lang.includes('Malay'));
        if (myVoice) utter.voice = myVoice;

        window.speechSynthesis.speak(utter);
    }
</script>
@endpush
@endsection

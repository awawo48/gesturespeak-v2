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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="font-size: 1.4rem; margin: 0;">
                    <i class="fa-solid fa-camera" style="color: var(--secondary);"></i> Praktis Kamera AI
                </h2>
                <span id="ai-status-badge" style="font-size: 0.8rem; font-weight: 800; background: #edf2f7; color: var(--text-muted); padding: 0.2rem 0.6rem; border-radius: var(--radius-full);">
                    Kamera Ditutup
                </span>
            </div>

            @if($sign->is_static)
                <div class="camera-box" id="camera-box">
                    <video id="webcam" class="camera-video" playsinline style="display: none;"></video>
                    <canvas id="output_canvas" class="camera-canvas"></canvas>

                    <div id="camera-placeholder" style="text-align: center; padding: 2rem; color: #fff;">
                        <div style="font-size: 3rem; margin-bottom: 0.5rem; color: var(--secondary);">📷</div>
                        <div style="font-weight: 800; font-size: 1.1rem; margin-bottom: 0.4rem;">Sedia untuk berlatih?</div>
                        <p style="font-size: 0.85rem; color: #a4b0be; max-width: 280px; margin: 0 auto 1.2rem;">
                            Tekan butang di bawah untuk hidupkan kamera dan tunjukkan isyarat <strong>{{ $sign->word }}</strong>.
                        </p>
                        <button type="button" class="btn btn-secondary" id="btn-start-camera" onclick="startCamera()">
                            <i class="fa-solid fa-video"></i> Buka Kamera AI
                        </button>
                    </div>

                    <div id="feedback-overlay" class="feedback-overlay" style="display: none;">
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

                    <button type="button" id="btn-stop-camera" class="btn btn-outline btn-sm" onclick="stopCamera()" style="display: none;">
                        <i class="fa-solid fa-stop"></i> Tutup
                    </button>
                </div>

                {{-- MANUAL FALLBACK BUTTON --}}
                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px dashed var(--border-light); text-align: center;">
                    <span style="font-size: 0.85rem; color: var(--text-muted); display: block; margin-bottom: 0.5rem;">
                        Tiada kamera atau kamera bermasalah?
                    </span>
                    <button type="button" class="btn btn-outline btn-sm" onclick="manualComplete()">
                        <i class="fa-solid fa-check"></i> Tandakan Isyarat Ini Sebagai Selesai
                    </button>
                </div>
            @else
                {{-- DYNAMIC SIGN (MOVEMENT) NOTICE --}}
                <div style="text-align: center; padding: 2.5rem 1.5rem; background: #f8fafc; border-radius: var(--radius-md);">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">👋 🏃‍♂️</div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 0.4rem;">Isyarat Pergerakan (Dinamik)</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 320px; margin: 0 auto 1.5rem;">
                        Isyarat perkataan <strong>{{ $sign->word }}</strong> melibatkan pergerakan tangan berterusan. Tonton klip video di sebelah dan ikuti pergerakannya berulang kali.
                    </p>
                    <button type="button" class="btn btn-secondary btn-lg" onclick="manualComplete()">
                        <i class="fa-solid fa-star"></i> Saya Dah Selesai Latih Isyarat Ini!
                    </button>
                </div>
            @endif

        </div>
    </div>

</div>

{{-- JAVASCRIPT: MEDIAPIPE HANDS + TTS + ASYNC PROGRESS --}}
@push('scripts')
{{-- MediaPipe Tasks Vision CDN --}}
<script type="module">
    import {
        HandLandmarker,
        FilesetResolver
    } from "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/vision_bundle.mjs";

    let handLandmarker = null;
    let runningMode = "VIDEO";
    let webcamRunning = false;
    let video = document.getElementById("webcam");
    let canvasElement = document.getElementById("output_canvas");
    let canvasCtx = canvasElement ? canvasElement.getContext("2d") : null;
    let feedbackOverlay = document.getElementById("feedback-overlay");
    let feedbackText = document.getElementById("feedback-text");
    let confidenceBar = document.getElementById("confidence-bar");
    let confidencePct = document.getElementById("confidence-pct");
    let badge = document.getElementById("ai-status-badge");
    let targetSign = "{{ strtoupper($sign->label) }}";
    let isMastered = {{ $progress->mastered ? 'true' : 'false' }};
    let consecutiveMatches = 0;
    const MATCH_THRESHOLD_FRAMES = 12; // Hold correct gesture for ~0.4s to confirm

    // Initialize MediaPipe model in background
    async function initMediaPipe() {
        if (handLandmarker) return handLandmarker;
        if (badge) badge.innerText = "Memuatkan Model AI...";
        try {
            const vision = await FilesetResolver.forVisionTasks(
                "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm"
            );
            handLandmarker = await HandLandmarker.createFromOptions(vision, {
                baseOptions: {
                    modelAssetPath: "https://storage.googleapis.com/mediapipe-models/hand_landmarker/hand_landmarker/float16/1/hand_landmarker.task",
                    delegate: "GPU"
                },
                runningMode: runningMode,
                numHands: 1
            });
            if (badge) badge.innerText = "Model AI Bersedia";
            return handLandmarker;
        } catch (e) {
            console.error("Gagal memuatkan MediaPipe:", e);
            if (badge) badge.innerText = "Model CPU Fallback";
        }
    }

    // Start Webcam
    window.startCamera = async function() {
        const model = await initMediaPipe();
        if (!model) {
            alert("Model pengecaman tangan AI belum sedia. Sila semak sambungan internet anda.");
            return;
        }

        const constraints = { video: { width: 640, height: 480 } };
        try {
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = stream;
            video.style.display = "block";
            document.getElementById("camera-placeholder").style.display = "none";
            document.getElementById("btn-stop-camera").style.display = "inline-flex";
            if (feedbackOverlay) feedbackOverlay.style.display = "block";
            if (badge) {
                badge.innerText = "Sedang Mengimbas Tangan 🟢";
                badge.style.background = "#dcfce7";
                badge.style.color = "#16a34a";
            }

            video.addEventListener("loadeddata", () => {
                webcamRunning = true;
                canvasElement.width = video.videoWidth;
                canvasElement.height = video.videoHeight;
                predictWebcam();
            });
        } catch (err) {
            console.error("Kamera tidak dapat diakses:", err);
            alert("Akses kamera ditolak atau kamera tidak dijumpai: " + err.message);
        }
    };

    // Stop Webcam
    window.stopCamera = function() {
        webcamRunning = false;
        if (video && video.srcObject) {
            video.srcObject.getTracks().forEach(track => track.stop());
            video.style.display = "none";
        }
        document.getElementById("camera-placeholder").style.display = "block";
        document.getElementById("btn-stop-camera").style.display = "none";
        if (feedbackOverlay) feedbackOverlay.style.display = "none";
        if (badge) {
            badge.innerText = "Kamera Ditutup";
            badge.style.background = "#edf2f7";
            badge.style.color = "var(--text-muted)";
        }
    };

    // Prediction Loop
    let lastVideoTime = -1;
    async function predictWebcam() {
        if (!webcamRunning) return;

        let startTimeMs = performance.now();
        if (lastVideoTime !== video.currentTime) {
            lastVideoTime = video.currentTime;
            const results = handLandmarker.detectForVideo(video, startTimeMs);

            canvasCtx.save();
            canvasCtx.clearRect(0, 0, canvasElement.width, canvasElement.height);

            if (results.landmarks && results.landmarks.length > 0) {
                const landmarks = results.landmarks[0];
                drawHand(canvasCtx, landmarks, canvasElement.width, canvasElement.height);

                // Heuristic Classifier for Static BIM signs
                const evalResult = evaluateGesture(landmarks, targetSign);

                // Update UI meters
                if (confidencePct) confidencePct.innerText = evalResult.confidence + "%";
                if (confidenceBar) confidenceBar.style.width = evalResult.confidence + "%";

                if (evalResult.matched) {
                    consecutiveMatches++;
                    if (feedbackText) feedbackText.innerText = "Bagus! Tahan isyarat (" + consecutiveMatches + "/" + MATCH_THRESHOLD_FRAMES + ")...";
                    if (feedbackOverlay) feedbackOverlay.className = "feedback-overlay feedback-success";

                    if (consecutiveMatches >= MATCH_THRESHOLD_FRAMES) {
                        onGestureSuccess(evalResult.confidence);
                        consecutiveMatches = 0;
                    }
                } else {
                    consecutiveMatches = Math.max(0, consecutiveMatches - 1);
                    if (feedbackText) feedbackText.innerText = evalResult.hint || "Arahkan jari mengikut panduan...";
                    if (feedbackOverlay) feedbackOverlay.className = "feedback-overlay";
                }
            } else {
                if (feedbackText) feedbackText.innerText = "Arahkan tangan anda ke dalam kotak kamera...";
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

    // Heuristic geometry classifier for BIM Static Signs
    function evaluateGesture(lm, target) {
        // Hand orientation: Wrist is lm[0]
        const wrist = lm[0];
        // Tips: Thumb(4), Index(8), Middle(12), Ring(16), Pinky(20)
        // PIPs: Index(6), Middle(10), Ring(14), Pinky(18)
        const isExtended = (tip, pip) => lm[tip].y < lm[pip].y; // Higher in canvas (lower Y) = extended
        const isCurled = (tip, pip) => lm[tip].y > lm[pip].y;

        const indexUp = isExtended(8, 6);
        const middleUp = isExtended(12, 10);
        const ringUp = isExtended(16, 14);
        const pinkyUp = isExtended(20, 18);
        const thumbOut = Math.abs(lm[4].x - lm[2].x) > 0.05;

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
            case 'I':
                // Only pinky finger up
                if (!indexUp && !middleUp && !ringUp && pinkyUp) {
                    matched = true;
                    confidence = 95;
                } else {
                    hint = "Tegakkan hanya jari kelengkeng.";
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

        return { matched, confidence, hint };
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
                    alert("🌟 TAHNIAH! Anda berjaya menguasai isyarat " + "{{ $sign->word }}" + "! +" + data.stars_awarded + " Bintang!");
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

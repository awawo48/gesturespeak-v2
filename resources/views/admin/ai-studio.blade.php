@extends('layouts.app', ['title' => 'Studio Latihan AI - MediaPipe'])

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.6rem;">
            <h1 style="font-size: 1.8rem; margin: 0;">Studio Latihan AI & Pengumpulan Data</h1>
            <span class="role-badge" style="background: #e0f2fe; color: #0284c7;">Ahli Kumpulan C (ML/AI)</span>
        </div>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
            Rakam koordinat 21 titik sendi tangan (63 koordinat 3D) untuk melatih model isyarat BIM.
        </p>
    </div>

    <div style="display: flex; gap: 0.8rem;">
        <a href="{{ route('admin.ai.export') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-file-csv"></i> Muat Turun Dataset (CSV)
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 2rem;">

    {{-- LEFT: LIVE CAMERA RECORDER --}}
    <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 1rem;">
            <i class="fa-solid fa-camera" style="color: var(--secondary);"></i> Suapan Kamera & Ekstraksi Titik Tangan
        </h3>

        {{-- TARGET SIGN SELECTOR --}}
        <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem; background: #f8fafc; padding: 0.8rem 1rem; border-radius: var(--radius-md);">
            <label style="font-weight: 800; font-size: 0.9rem; white-space: nowrap;">Isyarat Sasaran:</label>
            <select id="sign-select" class="form-select" style="padding: 0.4rem 0.8rem; font-size: 1rem; font-weight: 800;">
                @foreach($categories as $cat)
                    <optgroup label="{{ $cat->name }}">
                        @foreach($cat->signs as $s)
                            <option value="{{ $s->label }}">{{ $s->word }} (Label: {{ $s->label }})</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        <div style="position: relative; background: #1e272e; border-radius: var(--radius-md); overflow: hidden; aspect-ratio: 4/3; margin-bottom: 1rem;">
            <video id="studio-video" playsinline style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); display: none;"></video>
            <canvas id="studio-canvas" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></canvas>

            <div id="studio-placeholder" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem; color: var(--secondary);">📹</div>
                <p style="color: #a4b0be; font-size: 0.9rem; margin-bottom: 1rem;">Kamera belum diaktifkan.</p>
                <button type="button" class="btn btn-secondary" onclick="startStudioCamera()">
                    <i class="fa-solid fa-play"></i> Buka Kamera Studio
                </button>
            </div>
        </div>

        {{-- RECORDING CONTROLS --}}
        <div style="display: flex; gap: 0.8rem; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" id="btn-record-one" onclick="recordSample()" disabled style="flex: 1;">
                <i class="fa-solid fa-record-vinyl"></i> Rakam 1 Sampel
            </button>
            <button type="button" class="btn btn-accent" id="btn-record-burst" onclick="startBurstRecord(30)" disabled style="flex: 1;">
                <i class="fa-solid fa-burst"></i> Rakam Automatik (30 Sampel)
            </button>
        </div>

        <div id="record-status" style="margin-top: 0.8rem; font-size: 0.85rem; font-weight: 800; color: var(--text-muted); text-align: center;">
            Sedia untuk merakam data.
        </div>
    </div>

    {{-- RIGHT: DATASET SUMMARY & LIVE FEATURES --}}
    <div>
        {{-- TOTAL SAMPLES CARD --}}
        <div class="card" style="margin-bottom: 1.5rem; background: linear-gradient(135deg, #6c5ce7 0%, #5041c9 100%); color: #fff;">
            <div style="font-size: 0.85rem; font-weight: 800; text-transform: uppercase;">Jumlah Sampel Dalam Pangkalan Data</div>
            <div style="font-size: 2.8rem; font-weight: 900; color: #ffeaa7; font-family: var(--font-display);" id="stat-total-samples">
                {{ $totalSamples }}
            </div>
            <div style="font-size: 0.85rem; opacity: 0.9;">
                Setiap sampel mengandungi 63 nombor float (21 titik &times; 3D koordinat X, Y, Z).
            </div>
        </div>

        {{-- SAMPLES PER SIGN LIST --}}
        <div class="card">
            <h3 style="font-size: 1.2rem; margin-bottom: 1rem;">Bilangan Sampel Mengikut Isyarat</h3>
            <div style="max-height: 380px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.4rem;" id="sample-list">
                @forelse($sampleCounts as $lbl => $cnt)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.8rem; background: #f8fafc; border-radius: var(--radius-sm); font-size: 0.9rem;">
                        <strong>{{ $lbl }}</strong>
                        <span class="role-badge" style="background: #e0f2fe; color: #0284c7;">
                            {{ $cnt }} sampel
                        </span>
                    </div>
                @empty
                    <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 1.5rem 0;">
                        Belum ada sampel dirakam. Mulakan rakaman pertama anda!
                    </p>
                @endforelse
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script type="module">
    import {
        HandLandmarker,
        FilesetResolver
    } from "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/vision_bundle.mjs";

    let handLandmarker = null;
    let video = document.getElementById("studio-video");
    let canvas = document.getElementById("studio-canvas");
    let ctx = canvas.getContext("2d");
    let latestLandmarks = null;
    let isWebcamRunning = false;

    async function initModel() {
        if (handLandmarker) return handLandmarker;
        document.getElementById("record-status").innerText = "Memuatkan MediaPipe HandLandmarker...";
        const vision = await FilesetResolver.forVisionTasks(
            "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm"
        );
        handLandmarker = await HandLandmarker.createFromOptions(vision, {
            baseOptions: {
                modelAssetPath: "https://storage.googleapis.com/mediapipe-models/hand_landmarker/hand_landmarker/float16/1/hand_landmarker.task",
                delegate: "GPU"
            },
            runningMode: "VIDEO",
            numHands: 1
        });
        document.getElementById("record-status").innerText = "Model MediaPipe sedia.";
        return handLandmarker;
    }

    window.startStudioCamera = async function() {
        await initModel();
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } });
            video.srcObject = stream;
            video.style.display = "block";
            document.getElementById("studio-placeholder").style.display = "none";
            document.getElementById("btn-record-one").disabled = false;
            document.getElementById("btn-record-burst").disabled = false;

            video.addEventListener("loadeddata", () => {
                isWebcamRunning = true;
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                loopPredict();
            });
        } catch (e) {
            alert("Ralat kamera: " + e.message);
        }
    };

    let lastTime = -1;
    function loopPredict() {
        if (!isWebcamRunning) return;
        if (lastTime !== video.currentTime) {
            lastTime = video.currentTime;
            const res = handLandmarker.detectForVideo(video, performance.now());
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            if (res.landmarks && res.landmarks.length > 0) {
                latestLandmarks = res.landmarks[0];
                drawSkeleton(ctx, latestLandmarks, canvas.width, canvas.height);
                document.getElementById("record-status").innerText = "Tangan dikesan! Boleh merakam sampel.";
                document.getElementById("record-status").style.color = "#16a34a";
            } else {
                latestLandmarks = null;
                document.getElementById("record-status").innerText = "Arahkan tangan ke hadapan kamera...";
                document.getElementById("record-status").style.color = "var(--text-muted)";
            }
        }
        requestAnimationFrame(loopPredict);
    }

    function drawSkeleton(c, lm, w, h) {
        c.strokeStyle = "#00cec9";
        c.lineWidth = 3;
        c.fillStyle = "#ff7675";
        const connections = [
            [0,1],[1,2],[2,3],[3,4],
            [0,5],[5,6],[6,7],[7,8],
            [0,9],[9,10],[10,11],[11,12],
            [0,13],[13,14],[14,15],[15,16],
            [0,17],[17,18],[18,19],[19,20],
            [5,9],[9,13],[13,17],[0,17]
        ];
        connections.forEach(([i, j]) => {
            c.beginPath();
            c.moveTo(lm[i].x * w, lm[i].y * h);
            c.lineTo(lm[j].x * w, lm[j].y * h);
            c.stroke();
        });
        lm.forEach(p => {
            c.beginPath();
            c.arc(p.x * w, p.y * h, 4, 0, 2 * Math.PI);
            c.fill();
        });
    }

    // Normalise landmarks: wrist (lm[0]) as origin (0,0,0) and scale by hand size
    function extractFeatures(lm) {
        const wrist = lm[0];
        // Scale factor: distance between wrist and middle finger MCP (lm[9])
        const scale = Math.hypot(lm[9].x - wrist.x, lm[9].y - wrist.y) || 1;
        const features = [];
        lm.forEach(p => {
            features.push(Number(((p.x - wrist.x) / scale).toFixed(4)));
            features.push(Number(((p.y - wrist.y) / scale).toFixed(4)));
            features.push(Number(((p.z - wrist.z) / scale).toFixed(4)));
        });
        return features;
    }

    window.recordSample = async function() {
        if (!latestLandmarks) {
            alert("Tiada tangan dikesan. Pastikan tangan anda jelas di hadapan kamera.");
            return;
        }

        const label = document.getElementById("sign-select").value;
        const features = extractFeatures(latestLandmarks);

        try {
            const resp = await fetch("{{ route('admin.ai.sample.store') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Accept": "application/json"
                },
                body: JSON.stringify({ label: label, features: features })
            });
            const data = await resp.json();
            if (data.success) {
                document.getElementById("stat-total-samples").innerText = data.total_all;
                document.getElementById("record-status").innerText = "Sampel untuk " + label + " berjaya dirakam! (Jumlah: " + data.total_for_label + ")";
            }
        } catch (e) {
            console.error("Ralat simpan sampel:", e);
        }
    };

    window.startBurstRecord = async function(count) {
        let recorded = 0;
        document.getElementById("btn-record-burst").disabled = true;
        const interval = setInterval(async () => {
            if (latestLandmarks) {
                await window.recordSample();
                recorded++;
                document.getElementById("record-status").innerText = "Rakaman berterusan: " + recorded + " / " + count;
                if (recorded >= count) {
                    clearInterval(interval);
                    document.getElementById("btn-record-burst").disabled = false;
                    document.getElementById("record-status").innerText = "Rakaman " + count + " sampel selesai!";
                }
            }
        }, 150); // Record every 150ms
    };
</script>
@endpush
@endsection

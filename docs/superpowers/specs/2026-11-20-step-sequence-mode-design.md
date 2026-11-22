# Reka Bentuk Mod Baharu: Pengecaman Urutan Langkah (*Step-by-Step Sequence Mode*)

**Tarikh:** 2026-11-20  
**Skop:** Menambah mod baharu **"Urutan Langkah" (`sample_type = 'steps'`)** di AI Studio dan mengaktifkannya secara automatik di halaman belajar (`learn/sign.blade.php`) apabila sesuatu isyarat dinamik mempunyai sampel `steps` *(Pendekatan 1)*.  
**Fail Berkaitan:**
- `app/Models/GestureSample.php`
- `app/Http/Controllers/AiTrainingController.php`
- `public/js/gesture-ai.js`
- `resources/views/admin/ai-studio.blade.php`
- `resources/views/learn/sign.blade.php`

---

## 1. Tujuan & Rasional

Sistem sedia ada dalam `gesturespeak-v2` menyokong dua mod pengecaman:
1. **Statik (`sample_type = 'static'`)** — padanan 1 pose pegun menggunakan KNN.
2. **Dinamik DTW (`sample_type = 'sequence'`)** — rakaman klip jujukan berterusan (~30 bingkai) yang dinilai sekali gus selepas tangan berhenti bergerak menggunakan `MotionSegmenter` + `dtwDistance()`.

Bagi banyak isyarat dinamik BIM yang berbentuk peralihan pose jelas (contoh: `BUKU` = *tapak tangan rapat → tapak tangan terbuka*; `TERIMA KASIH` = *tangan di dagu → tangan ke depan*), pendekatan **Urutan Langkah (*Step-by-Step Checkpoints*)** memberi tiga kelebihan utama untuk kanak-kanak:
- **Tahan perbezaan kelajuan & jeda:** Kanak-kanak boleh buat perlahan atau berhenti sejenak di pertengahan tanpa gagal penilaian.
- **Maklum balas masa nyata langkah demi langkah:** Pelajar terus nampak *"Langkah 1 ✓ → Sekarang buat Langkah 2"* dan tahu bahagian mana yang sudah betul.
- **Rakaman lebih mudah di AI Studio:** Admin hanya perlu tangkap 2 hingga 6 pose penting mengikut urutan, berbanding mengulang rakaman klip gerakan penuh berkali-kali.

Mod ini dibina sebagai **mod ketiga yang berasingan** supaya semua data `static` dan `21` klip `sequence` (DTW) sedia ada kekal utuh dan terus berfungsi tanpa perubahan.

---

## 2. Matlamat, Bukan Matlamat & Anti-Matlamat

### Matlamat (*Goals*)
- Menyimpan sampel urutan pose dalam jadual `gesture_samples` sedia ada dengan `sample_type = 'steps'` **tanpa sebarang migrasi skema DB**.
- Menyokong **2 hingga 6 langkah berurutan** bagi setiap sampel `steps`, dengan setiap langkah menyimpan vektor multimodal `146`-ciri (tangan utama + konteks wajah/bahu/posisi + tangan kedua + hubungan dwi-tangan) serta nama langkah pilihan (cth: `"Langkah 1"` atau `"Rapat"`).
- Menyediakan tab mod ketiga **"Urutan Langkah"** di **AI Studio (`admin/ai-studio.blade.php`)** untuk menangkap langkah demi langkah, mengurus draf langkah (batal langkah terakhir / set semula / simpan), dan menguji secara langsung dalam *Live Tester* (Mod Terbuka & Simulasi Pelajar).
- Mengaktifkan enjin `StepSequenceClassifier` **secara automatik** di **Halaman Belajar (`learn/sign.blade.php`)** bagi mana-mana isyarat dinamik (`!is_static`) yang mempunyai sekurang-kurangnya `1` sampel `steps`, lengkap dengan penunjuk kemajuan visual (`Langkah 1 ✓ → Langkah 2`).
- Mengekalkan *fallback* penuh kepada enjin DTW / `BIM_FACE_RULES` / Mod Cermin sedia ada bagi isyarat dinamik yang mempunyai `0` sampel `steps`.

### Bukan Matlamat (*Non-Goals*)
- Menggantikan, membuang, atau mengubah tingkah laku mod `static` (KNN) atau mod `sequence` (DTW) sedia ada.
- Menambah kolum `recognition_mode` baharu pada jadual `signs` (pemilihan automatik berasaskan kewujudan sampel `steps` digunakan untuk fasa ini).
- Pengecaman ayat berterusan (*continuous sentence recognition*).
- Memasukkan data `steps` ke dalam eksport CSV statik (`AiTrainingController::exportCsv()` mesti kekal mengeksport `sample_type = 'static'` sahaja).

### Anti-Matlamat (*Anti-Goals*)
- Memecahkan `991` sampel statik, `21` klip `sequence` DTW, atau kontrak JSON sedia ada bagi `/api/ai/dataset` (`samples` dan `sequences` mesti kekal tidak berubah).
- Meluluskan langkah secara tidak sengaja akibat gegaran bingkai tunggal atau gerakan rawak — setiap langkah mesti melepasi ambang padanan untuk beberapa bingkai berturut-turut (*hold frames*).
- Menambah kebergantungan pelayan luaran / Python (`100%` dalam pelayar dikekalkan).

---

## 3. Kekangan (*Constraints*)

- **Keserasian DB:** Jadual `gesture_samples` sedia ada mempunyai `sample_type` (`string(20)`) dan `features` (`json`/`array`). Nilai `'steps'` muat terus tanpa migrasi.
- **Keserasian API:** `GET /api/ai/dataset` (dan `GET /admin/ai-studio/dataset`) menambah kunci baharu `'steps'` tanpa mengubah struktur `'samples'` dan `'sequences'`.
- **Prestasi pelayar:** Menggunakan gelung penglihatan berselang sedia ada (`HandLandmarker` `numHands: 2` setiap bingkai + `PoseLandmarker` & `FaceLandmarker` berselang-seli) supaya FPS kekal lancar (`>= 20 FPS`).
- **Konsistensi UI:** Mematuhi gaya visual Sign Cilik (`docs/UI-RULEBOOK.md`) di kedua-dua `ai-studio.blade.php` dan `learn/sign.blade.php`.

---

## 4. Pendekatan Yang Dipertimbangkan (*Approaches*)

| Pendekatan | Ringkasan | Tukar Ganti (*Trade-off*) | Status |
| :--- | :--- | :--- | :--- |
| **1. Mod `steps` di AI Studio + Aktif Automatik di Halaman Belajar *(Dipilih)*** | Tambah `sample_type = 'steps'`, tab "Urutan Langkah" di AI Studio, dan aktif automatik di `learn/sign.blade.php` jika isyarat ada rekod `steps`. | Sentuh 2 skrin UI (`ai-studio` & `sign`), tetapi sifar risiko pada DTW lama dan terus boleh uji pengalaman sebenar pelajar. | **Dipilih oleh pengguna** |
| **2. Mod `steps` di AI Studio Sahaja (*Sandbox*)** | Bina backend, enjin JS & UI AI Studio sahaja tanpa menyentuh `learn/sign.blade.php`. | Perubahan paling kecil, tetapi pelajar belum dapat cuba aliran langkah demi langkah di halaman belajar. | Ditolak |
| **3. Kolum Mod Eksplisit Per-Isyarat di DB `signs`** | Tambah kolum `recognition_mode` pada jadual `signs` + UI pemilihan mod per isyarat. | Kawalan manual penuh, tetapi perlukan migrasi DB dan UI tambahan yang berlebihan untuk fasa cubaan pertama. | Ditolak buat masa ini |

---

## 5. Seni Bina & Reka Bentuk Terperinci

### A. Format Data & Backend (`GestureSample.php` & `AiTrainingController.php`)

**Struktur JSON dalam `gesture_samples.features` apabila `sample_type = 'steps'`:**
```json
[
  { "name": "Langkah 1", "f": [/* 146 nombor ternormal */] },
  { "name": "Langkah 2", "f": [/* 146 nombor ternormal */] }
]
```

**Perubahan `App\Models\GestureSample`:**
- Tambah skop `scopeSteps($query)` (`where('sample_type', 'steps')`).
- Tambah pembantu `normaliseSteps(array $steps): array`:
  - Memastikan setiap langkah mempunyai `name` (teks dibersihkan, maks 40 aksara, lalai `"Langkah " . ($idx + 1)`) dan `f` yang dinormalkan melalui `GestureSample::normalise($step['f'])` (menyokong `63..146` ciri, sama seperti `normaliseSequence()`).

**Perubahan `App\Http\Controllers\AiTrainingController`:**
1. **`studio()`:** Menghantar `stepCounts` (`label => count` bagi `sample_type = 'steps'`) dan `totalSteps` ke paparan `admin.ai-studio`.
2. **`storeSample(Request $request)`:**
   - Menambah cabang `if ($sampleType === 'steps')`:
     - Pengesahan: `label` (`required|string|max:50`), `steps` (`required|array|min:2|max:6`), `steps.*.name` (`nullable|string|max:40`), `steps.*.f` (`required|array|min:63|max:146`), `steps.*.f.*` (`numeric`).
     - Simpan 1 rekod `GestureSample` dengan `sample_type = 'steps'` dan `features = GestureSample::normaliseSteps($data['steps'])`.
     - Pulangkan JSON dengan `step_counts`, `total_steps`, `steps_count` (bilangan langkah dalam sampel), bersama `counts`, `sequence_counts`, `total_all`.
3. **`dataset()`:**
   - Mengumpul rekod `sample_type === 'steps'` ke dalam array `$stepSamples` (`['label' => $s->label, 'steps' => $s->features]`) dan memulangkan:
     ```json
     { "samples": [...], "sequences": [...], "steps": [...] }
     ```
4. **`clearSamples(Request $request)`:**
   - Menambah parameter pilihan `sample_type` (`nullable|in:static,sequence,steps`). Apabila diberi bersama `label`, hanya memadam jenis sampel tersebut bagi label berkenaan supaya memadam `steps` tidak memusnahkan klip DTW (`sequence`) atau sampel `static`. Apabila `sample_type` tidak diberi, tingkah laku lama dikekalkan. Pulangkan juga `step_counts`.

---

### B. Enjin Pengecaman JS (`public/js/gesture-ai.js`)

**1. Fungsi Jarak Langkah `stepFrameDistance(frameA, frameB, options)`:**
Membandingkan bingkai kamera semasa (`146`-ciri) dengan satu langkah templat (`63..146`-ciri), mengambil minimum antara `mirror = false` dan `mirror = true` (untuk keserasian tangan kiri/kanan):
- **Bentuk Tangan Utama (`0..62`) + Geometri Anatomi (`15` ciri):** Guna `multimodalSqDist()` sedia ada.
- **Posisi Relatif Badan/Wajah (`63..66`) & Zon/Ekspresi Wajah (`67..76`):**
  - Jika kedua-dua bingkai mempunyai rujukan wajah (`f[77] > 0.55`): sertakan offset hidung (`63..66`), kedekatan zon wajah (`67..71`), dan ekspresi (`72..76`).
  - Jika wajah tiada tetapi kedua-dua bingkai mempunyai rujukan bahu (`f[77] >= 0.45`): sertakan offset relatif bahu (`63..66`) dengan pemberat sederhana supaya langkah yang mempunyai bentuk tangan sama tetapi posisi berbeza (contoh: *dekat dagu/dada* vs *ke depan*) masih boleh dibezakan dengan tepat.
- **Tangan Kedua & Hubungan Dwi-Tangan (`78..145`):**
  - Jika kedua-dua bingkai berdimensi `>= 145`: bandingkan kehadiran tangan kedua (`f[141]`), bentuk tangan kedua (`78..140` + geometri tangan kedua jika kedua-duanya aktif), serta jarak/offset antara dua pergelangan tangan (`142..144`). Penalti kecil dikenakan jika templat langkah memerlukan 2 tangan (`hasHand2 === 1`) tetapi pengguna hanya menunjukkan 1 tangan.

**2. Kelas Baharu `StepSequenceClassifier`:**
- `load(stepSamples)`: Memuatkan templat `steps` dikelompokkan mengikut `label`, dengan prapengiraan geometri tangan 1 & tangan 2 bagi setiap langkah.
- `hasTemplates(label)`: Mengembalikan `true` jika `label` mempunyai sekurang-kurangnya `1` sampel `steps`.
- `getStepMeta(label)`: Mengembalikan `{ totalSteps, stepNames }` daripada templat kanonik (templat terkini untuk label tersebut) bagi paparan UI.
- `reset(label = null)`: Menetapkan semula kemajuan langkah.
- **`update(features, options = { targetLabel, mode: 'target-locked' | 'open-set', nowMs })`:**
  - **Mod `target-locked` (lalai di `learn/sign.blade.php` & Simulasi Pelajar AI Studio):**
    - Menyemak bingkai semasa terhadap langkah `currentStep` bagi semua templat `targetLabel` (mengambil jarak terbaik; jika terdapat templat dengan bilangan langkah berbeza, setiap templat menjejak `currentStep` masing-masing dan UI memaparkan kemajuan templat yang paling jauh mara).
    - Menukar jarak kepada skor keyakinan `0..99`.
    - Apabila `confidence >= STEP_MATCH_THRESHOLD` (`56%`) selama `HOLD_FRAMES` (`3` bingkai berturut-turut, ~100–150ms):
      - Langkah semasa ditanda selesai, skornya direkod, `currentStep` mara ke langkah seterusnya, dan `lastAdvanceTime` dikemas kini.
      - Apabila langkah terakhir selesai: masuk tempoh *cooldown* (`700ms`), pulangkan `{ matched: true, completed: true, label, confidence: avgStepConf, currentStep: totalSteps, totalSteps, stepNames, status: 'completed' }`.
    - **Had Masa Set Semula (*Timeout Reset*):** Jika `currentStep > 0` dan pengguna tidak mara ke langkah seterusnya melebihi `STEP_TIMEOUT_MS` (`5000ms`), kemajuan diset semula ke `0` dengan `status: 'timeout_reset'` supaya pelajar boleh cuba semula dengan tenang.
    - **Toleransi Tangan Hilang Sekejap:** Jika tangan hilang kurang daripada `1200ms` (contohnya tangan keluar bingkai seketika semasa menukar pose), kemajuan `currentStep` dikekalkan; melebihi `1200ms` diset semula ke `0`.
  - **Mod `open-set` (lalai di Live Tester AI Studio):**
    - Menjalankan penjejak kemajuan selari bagi setiap label yang mempunyai sampel `steps`; label pertama yang melengkapkan langkah terakhirnya memenangi penilaian dan menetapkan semula semua penjejak dalam tempoh *cooldown*.

---

### C. Antara Muka AI Studio (`resources/views/admin/ai-studio.blade.php`)

1. **Penukar Mod Kamera (`#cam-mode-switcher`) — 3 Tab:**
   - Tab 1: **Statik (KNN)** (`static`) — sedia ada.
   - Tab 2: **Dinamik (DTW)** (`dynamic`) — sedia ada.
   - Tab 3 *(Baharu)*: **Urutan Langkah** (`steps`) — mengaktifkan `numHands: 2` dan `PoseLandmarker` (sama seperti mod Dinamik).
2. **Panel Rakaman Urutan Langkah (dipaparkan apabila mod `steps` aktif):**
   - Paparan senarai draf langkah semasa dalam bentuk pil/kad bernombor (cth: `1. Langkah 1 ✓`, `2. Langkah 2 ✓`, maks `6`).
   - Input teks ringkas pilihan untuk nama langkah seterusnya (lalai automatik `"Langkah N"`).
   - Butang **"Tangkap Langkah"** (menyokong kiraan detik 2 saat atau tangkapan terus + pintasan kekunci `Space`), **"Buang Terakhir"**, **"Set Semula"**, dan **"Simpan Urutan Langkah"** (aktif apabila `>= 2` langkah telah ditangkap).
   - Butang **"Padam Langkah Isyarat Ini"** yang memanggil `clearSamples` dengan `sample_type: 'steps'` sahaja.
3. **Penguji Langsung (*Live Tester*) AI Studio:**
   - Berkongsi togol **Mod Terbuka (*Open-Set*) / Simulasi Pelajar (*Target-Locked*)** sedia ada.
   - Semasa mod `steps` aktif, memaparkan bar pil kemajuan langkah secara langsung (`Langkah 1 ✓ → Langkah 2 …`) dan lencana keputusan apabila semua langkah lengkap.
4. **Ringkasan Statistik & Senarai Isyarat:**
   - Memaparkan jumlah sampel `steps` (`totalSteps`) dan lencana bilangan `steps` bagi setiap isyarat dalam senarai sisi.

---

### D. Antara Muka Halaman Belajar (`resources/views/learn/sign.blade.php`)

1. **Pengaktifan Automatik (`loadDataset()`):**
   - Memuatkan `data.steps` ke dalam `stepClassifier.load(data.steps)`.
   - Bagi isyarat dinamik (`!isStaticSign`):
     - Jika `stepClassifier.hasTemplates(targetLabel)` ialah `true` → aktifkan mod **Urutan Langkah** secara automatik.
     - Jika `false` → kekal 100% pada aliran `DynamicGestureEngine` (DTW / `BIM_FACE_RULES` / Mod Cermin) sedia ada.
2. **Maklum Balas Visual Mesra Kanak-Kanak:**
   - Lencana header kamera memaparkan: `"Kamera AI Urutan Langkah (N Langkah)"`.
   - Memaparkan jalur pil kemajuan langkah secara mendatar di bawah/atas bar status kamera:
     - Langkah selesai: hijau dengan tanda `✓`
     - Langkah aktif sekarang: disorot (*highlight*) dengan petunjuk teks *"Buat Langkah X..."*
     - Langkah belum tiba: kelabu lembut
   - Apabila semua langkah lengkap, terus memanggil `onGestureSuccess(conf)` sedia ada (bintang, bunyi tahniah, simpan kemajuan pelajar).

---

## 6. Pelan Pengesahan & Ujian (*Verification*)

1. **Ujian PHPUnit (`tests/Feature/`):**
   - `GestureSample::normaliseSteps()` menormalkan vektor `63..146` dan mengisi nama langkah lalai.
   - `POST /admin/ai-studio/sample` dengan `sample_type = 'steps'` menerima `2..6` langkah dan menolak `< 2` atau `> 6` langkah (`422`).
   - `GET /api/ai/dataset` memulangkan kunci `steps` sambil mengekalkan `samples` dan `sequences`.
   - `POST /admin/ai-studio/clear` dengan `sample_type = 'steps'` hanya memadam rekod `steps` bagi label tersebut tanpa menjejaskan rekod `sequence` atau `static`.
   - `GET /admin/ai-studio/export` kekal mengeksport `static` sahaja.
2. **Ujian Unit JS (Node ES Module test script terhadap `public/js/gesture-ai.js`):**
   - `StepSequenceClassifier`:
     - Meluluskan urutan 2-langkah dan 3-langkah mengikut turutan tepat (`1 → 2 → 3`).
     - Menolak urutan terbalik (`2 → 1`) atau lompat langkah (`1 → 3`).
     - Membezakan 2 langkah yang mempunyai bentuk tangan sama tetapi posisi berbeza (contoh: dagu vs depan melalui slot `63..66`) dan perbezaan 1-tangan vs 2-tangan.
     - Mengesahkan `HOLD_FRAMES` menapis bingkai rawak tunggal dan `STEP_TIMEOUT_MS` menetapkan semula langkah selepas tamat tempoh.
     - Mengesahkan kedua-dua mod `target-locked` dan `open-set`.
3. **Semakan Pelayar Langsung:**
   - Rakam 1 isyarat dinamik 2-langkah (cth: `BUKU` atau `TERIMA KASIH`) di AI Studio, uji dalam Live Tester, kemudian buka halaman belajar isyarat tersebut untuk mengesahkan pil kemajuan langkah + kelulusan automatik, dan buka satu lagi isyarat dinamik tanpa `steps` untuk mengesahkan mod DTW / Cermin tidak terjejas.

---

## 7. Keputusan Kejuruteraan Yang Ditinggalkan Kepada Pelaksana (*Builder Calls*)

- Nilai ambang penalaan awal `StepSequenceClassifier` (`STEP_MATCH_THRESHOLD = 56`, `HOLD_FRAMES = 3`, `STEP_TIMEOUT_MS = 5000`, `HAND_LOST_GRACE_MS = 1200`, `COOLDOWN_MS = 700`) dan pemberat relatif posisi bahu/wajah (`63..66`) vs bentuk tangan (`0..62`) yang boleh ditala berdasarkan ujian unit dan kamera sebenar.
- Kedudukan tepat & kelas CSS pil kemajuan langkah di `learn/sign.blade.php` dan panel draf langkah di `admin/ai-studio.blade.php` mengikut token warna sedia ada dalam `docs/UI-RULEBOOK.md`.
- Pilihan kiraan detik lalai (cth: `2s` atau `0s` tangkap terus dengan togol ringkas) dalam panel rakaman `steps` AI Studio.

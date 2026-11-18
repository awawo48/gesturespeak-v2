# Reka Bentuk Naik Taraf Pengecaman Bahasa Isyarat Dinamik (Sign Cilik / GestureSpeak V2)

**Tarikh:** 2026-11-18  
**Skop:** Naik taraf enjin pengecaman pergerakan dinamik dalam pelayar (`Pendekatan 1`)  
**Fail Berkaitan:**
- `public/js/gesture-ai.js`
- `resources/views/learn/sign.blade.php`
- `resources/views/admin/ai-studio.blade.php`
- `app/Models/GestureSample.php`
- `app/Http/Controllers/AiTrainingController.php`

---

## 1. Tujuan & Masalah Yang Diselesaikan

Pelajar di `gesturespeak-v2` menghadapi kesukaran apabila kamera perlu mengecam isyarat bahasa isyarat Malaysia (BIM) yang melibatkan pergerakan tangan (**56 daripada 91 isyarat kurikulum adalah dinamik**). Semakan terhadap kod semasa dan pangkalan data menunjukkan empat punca utama:

1. **Penilaian terputus pada penghujung gerakan (`sign.blade.php` baris 939–964):** `SequenceClassifier.predict()` hanya dipanggil apabila `!motionTracker.isStill(0.03)` sambil menuntut `consecutiveMatches >= 4`. Sebaik sahaja pengguna selesai melakukan gerakan penuh dan tangan mula berhenti, penilaian terus dibatalkan sebelum sempat mencapai 4 padanan berturut-turut.
2. **Penalti kelajuan terlalu sensitif (`gesture-ai.js` baris 1067 & 1071):** Jarak `dtwDistance()` mendarab perbezaan halaju trajektori `trajVel` dengan **`14.0`**, menyebabkan pergerakan yang sedikit lebih laju atau perlahan daripada klip rakaman menjatuhkan skor di bawah ambang `52%`.
3. **Had 1 tangan & ketiadaan rujukan bahu:** `sign.blade.php` hanya mengaktifkan `HandLandmarker` dengan `numHands: 1` dan bergantung pada koordinat skrin mutlak apabila wajah tiada. Isyarat BIM 2 tangan (contoh: `TOLONG`, `MAAF`, `BUKU`) serta perubahan jarak duduk tidak dinormalkan terhadap lebar bahu.
4. **Ketiadaan klip rujukan & fallback terlalu longgar:** Pangkalan data hanya mempunyai **21 klip `sequence`** merentasi 3 isyarat (`J: 9`, `Z: 3`, `ASSALAMUALAIKUM: 9`), manakala baki **53 isyarat dinamik (`0` klip)** kini lulus secara automatik asalkan tangan bergerak rawak (`!motionTracker.isStill(0.035)`).

Matlamat reka bentuk ini adalah menjadikan kamera pengecaman dinamik jauh lebih responsif, tepat, menyokong **2 tangan + rujukan bahu (*Pose*)**, kekal serasi dengan **21 klip sedia ada**, dan jujur kepada pengguna apabila sesuatu isyarat belum mempunyai klip latihan.

---

## 2. Matlamat, Bukan Matlamat & Anti-Matlamat

### Matlamat (*Goals*)
- Menyokong pengesanan **2 tangan serentak (`numHands: 2`)** dan penormalan badan berasaskan bahu (**`PoseLandmarker`**) bersama `FaceLandmarker` sedia ada dalam pelayar web.
- Menggantikan penilaian DTW bingkai-demi-bingkai yang terputus dengan **Mesin Keadaan Gerakan (*Motion State-Machine*: `IDLE -> CAPTURING -> EVALUATING -> COOLDOWN`)** yang menilai klip lengkap sebaik gerakan selesai.
- Mengkalibrasi semula fungsi jarak `dtwDistance()` supaya tahan perbezaan kelajuan pergerakan pengguna (termasuk kanak-kanak) tanpa meletupkan kos jarak.
- Melaksanakan **Pilihan A** untuk 53 isyarat dinamik dengan `0` klip: semakan gabungan **pergerakan + peraturan zon wajah/ekspresi (`BIM_FACE_RULES`)** jika ada, dan **mod cermin jujur (tanpa kelulusan palsu daripada gerakan rawak)** bagi baki isyarat tanpa klip mahupun peraturan muka.
- Menyelaraskan mod rakaman dinamik & penguji langsung dalam **AI Studio** (`ai-studio.blade.php`) supaya menggunakan enjin penormalan dan pengesanan Mula–Gerak–Tamat yang sama.

### Bukan Matlamat (*Non-Goals*)
- Pengecaman ayat berterusan (*continuous gloss-to-sentence translation*) tanpa jeda antara isyarat.
- Menjalankan atau menyambungkan pelayan inferens Python (`FastAPI` / PyTorch `realtime.py` di `dynamic_signs/`) ke Laravel.
- Menukar tingkah laku mod **Statik (`is_static = true`)** kecuali memastikan perkongsian modul dalam `gesture-ai.js` kekal serasi.

### Anti-Matlamat (*Anti-Goals*)
- Memerlukan pelayan tambahan selain pelayan web Laravel sedia ada (`100% client-side in browser` mesti dikekalkan).
- Meluluskan lambaian tangan rawak sebagai jawapan betul hanya kerana isyarat tiada sampel `sequence`.
- Memecahkan atau membuang `991` sampel statik dan `21` klip `sequence` yang sudah tersimpan di pangkalan data.
- Menurunkan kadar bingkai (*FPS*) kamera di bawah paras boleh guna (~15–20 FPS ke bawah) akibat menjalankan semua model MediaPipe secara berat pada setiap bingkai.

---

## 3. Kekangan (*Constraints*)

- **Kekal dalam pelayar:** Semua inferens penglihatan komputer berjalan dalam pelayar melalui `@mediapipe/tasks-vision@0.10.14` (sudah dimuatkan melalui CDN/WASM dalam `sign.blade.php` dan `ai-studio.blade.php`).
- **Keserasian skema pangkalan data (`gesture_samples`):** Tiada penghijrahan skema jadual yang merosakkan; kolum JSON `features` menyimpan array vektor per bingkai. Klip lama berdimensi `63` atau `78` per bingkai mesti kekal boleh dibaca dan dinilai bersama klip baharu v2.
- **Keserasian eksport CSV statik:** `AiTrainingController::exportCsv()` hanya mengeksport `sample_type = 'static'`; aliran ini tidak boleh terganggu.
- **Peranti sasaran:** Komputer riba / desktop pelajar biasa dengan webcam standard `640×480` (kebanyakan tiada GPU berkuasa tinggi).

---

## 4. Pendekatan Yang Dipertimbangkan (*Approaches*)

| Pendekatan | Ringkasan Teknikal | Kelebihan | Kekurangan / Tukar Ganti (*Trade-off*) | Status |
| :--- | :--- | :--- | :--- | :--- |
| **1. Naik Taraf Enjin Pelayar *(Dipilih)*** | *Motion State-Machine* (Mula–Gerak–Tamat) + `PoseLandmarker` & `numHands: 2` + Penalaan DTW & semakan fasa (`BIM_FACE_RULES`). | Berjalan 100% dalam pelayar; berkesan dengan **3–5 klip per isyarat**; serasi dengan 21 klip lama; boleh terus dilaksana. | Berasaskan padanan templat & peraturan fasa; kurang sesuai untuk ayat bersambung tanpa jeda. | **Dipilih oleh pengguna** |
| **2. Model Neural Dalam Pelayar (`ONNX Runtime Web`)** | Latih BiLSTM / 1D-TCN dari `dynamic_signs/train.py`, eksport `.onnx`, dan jalankan dalam JS. | Sangat tahan kelajuan berbeza & penapisan `idle`. | Perlukan **15–25 klip × 3+ orang** bagi setiap isyarat; perlu latih & eksport semula setiap kali isyarat ditambah. | Ditolak buat masa ini (boleh dibina di atas klip bersih dari Pendekatan 1 kelak) |
| **3. Pelayan AI Python (`FastAPI` + PyTorch)** | Hantar bingkai landmark ke pelayan Python berasingan untuk inferens `.pt`. | Mudah ubah kod Python terus. | Tambah infrastruktur 2 pelayan, kelewatan rangkaian, dan tetap perlu dataset besar. | Ditolak |

---

## 5. Seni Bina & Reka Bentuk Terperinci

### A. Ekstraksi Ciri Multimodal v2 (`146` ciri per bingkai) & Keserasian v1 (`63` / `78`)

**`PoseLandmarker` (Model Lite MediaPipe v0.10.14):**
- Menggunakan model `pose_landmarker_lite/float16/1/pose_landmarker_lite.task` daripada `@mediapipe/tasks-vision@0.10.14`.
- Mengambil 3 titik rujukan bahagian atas badan: Bahu Kiri (`pose[11]`), Bahu Kanan (`pose[12]`), dan Hidung (`pose[0]`).
- Mengira:
  - `shoulderCenter = ((L.x + R.x)/2, (L.y + R.y)/2, (L.z + R.z)/2)` *(hanya sah jika kedua-dua bahu dikesan dengan keterlihatan mencukupi)*.
  - `shoulderWidth = max(0.08, hypot(L.x - R.x, L.y - R.y))` sebagai skala global badan.

**Penormalan Tangan & Susun Atur Tangan Kiri/Kanan (Keputusan Kejuruteraan):**
- `HandLandmarker` diset kepada `numHands: 2` (di kedua-dua `sign.blade.php` dan lalai mod dinamik `ai-studio.blade.php`).
- Untuk mengelakkan tangan bertukar slot secara rawak antara bingkai apabila MediaPipe mengembalikan 2 tangan, guna `results.handedness` (`Left` / `Right`) apabila tersedia:
  - **Slot 1 (`0..62`) — Tangan Dominan / Utama:** Jika ada `Right`, atau tangan tunggal yang dikesan, atau tangan dengan halaju pergerakan tertinggi apabila `handedness` tidak konsisten; dinormalkan relatif kepada pergelangan tangan sendiri (kekal serasi 100% dengan vektor `63` sedia ada).
  - **Slot Wajah & Trajektori (`63..77`, `15` ciri):**
    - Jika `FaceLandmarker` aktif & wajah kelihatan: simpan koordinat relatif hidung + 5 kedekatan zon wajah + 5 skor ekspresi (`f[77] = 1.0`), sama seperti v1 supaya klip lama yang ada konteks wajah kekal sepadan.
    - Jika wajah tiada tetapi **`PoseLandmarker` bahu ada**: simpan koordinat pergelangan tangan & hujung telunjuk Slot 1 relatif kepada `shoulderCenter / shoulderWidth`, dan tandakan `f[77] = 0.5` (penanda rujukan bahu).
    - Jika kedua-dua wajah & bahu tiada: fallback ke trajektori skrin kamera seperti v1 (`f[77] = 0.25`).
  - **Slot 2 (`78..140`, `63` ciri) — Tangan Kedua:** Vektor `63` ternormal bagi tangan kedua jika wujud (`0` jika hanya 1 tangan).
  - **Slot Konteks Dwi-Tangan & Bahu (`141..145`, `5` ciri):**
    - `f[141]`: `hasHand2` (`1.0` jika 2 tangan dikesan pada bingkai ini, `0.0` jika tidak).
    - `f[142]`, `f[143]`: koordinat `(dx, dy)` pergelangan Tangan Kedua relatif kepada Tangan Utama yang diskalakan oleh `shoulderWidth` (atau `palmSpan`), penting untuk isyarat bercantum/bersilang seperti `TOLONG`, `MAAF`, `BUKU`.
    - `f[144]`: jarak Euclidean 2D antara pergelangan Tangan 1 dan Tangan 2 ternormal.
    - `f[145]`: flag `hasPose` (`1.0` jika bahu pose sah, `0.0` jika tidak).

*(Nota Keserasian: Dalam `GestureSample::normalise(array $features)` dan `AiTrainingController::storeSample()`, julat panjang vektor per bingkai diperluaskan kepada `63..146`. Untuk `features[0..62]`, penormalan tangan utama dikekalkan; untuk `features[78..140]`, jika ada, dinormalkan dengan cara pergelangan-tangan sama jika belum ternormal, dan `features[141..145]` diapit selamat `[-3.0, 3.0]`. Semua rekod `78`-ciri lama dalam DB terus berfungsi tanpa sebarang perubahan.)*

---

### B. Gelung Penglihatan Berselang (*Interleaved Vision Loop*) & Prestasi

Bagi mengekalkan kelancaran webcam di komputer riba pelajar tanpa membebankan CPU/GPU:
1. **`HandLandmarker` (`numHands: 2`)** berjalan pada **setiap bingkai kamera baru** (`lastVideoTime !== video.currentTime`).
2. **`FaceLandmarker`** dan **`PoseLandmarker (lite)`** berjalan secara **berselang-seli (*interleaved*)**:
   - Bingkai genap (`tick % 2 === 0`): jalankan `PoseLandmarker` (atau hanya diaktifkan pada skrin isyarat dinamik / mod dinamik AI Studio).
   - Bingkai ganjil (`tick % 2 === 1`): jalankan `FaceLandmarker` jika togol *AI Wajah* diaktifkan.
3. Kedua-dua `FaceLandmarker` dan `PoseLandmarker` menyokong GPU dengan *fallback* automatik ke CPU dan tidak menghalang kamera daripada dibuka jika muat turun model gagal (*graceful degradation*).

---

### C. Mesin Keadaan Gerakan (*Motion State-Machine*)

Menggantikan panggilan `SequenceClassifier.predict()` mentah pada setiap bingkai dalam `sign.blade.php` (dan mod ujian dinamik `ai-studio.blade.php`) dengan kelas baharu `DynamicGestureEngine` / `MotionSegmenter` dalam `gesture-ai.js`:

```text
[IDLE] ──(tenaga gerakan > START_THRESH selama 2 bingkai berturut-turut)──▶ [CAPTURING]
  ▲                                                                             │
  │                                  ┌───(gerakan tamat: diam >= PAUSE_MS       │
  │                                  │    ATAU durasi >= MAX_CLIP_MS            │
  │                                  │    ATAU tangan hilang selepas >= MIN_F)──┘
  │                                  ▼
[COOLDOWN (450ms)] ◀─────────── [EVALUATING (nilai klip 1 kali)]
```

**Parameter & Peraturan Fasa (Keputusan Kejuruteraan):**
- **Tenaga Gerakan Ternormal (*Normalized Motion Energy*):** Mengukur anjakan maksimum antara bingkai berturutan menggunakan koordinat ternormal (skala bahu apabila `Pose` aktif, atau skala tapak tangan apabila `Pose` tiada), menggabungkan tangan utama dan tangan kedua — bukan lagi pixel skrin mentah semata-mata.
- **Penimbal Pra-Rakaman (*Pre-roll Ring Buffer*):** Menyimpan `6` bingkai terakhir (~200ms) semasa keadaan `IDLE`. Apabila peralihan ke `CAPTURING` tercetus, `6` bingkai pra-rakaman ini dimasukkan di awal klip supaya permulaan gerakan tidak terpotong.
- **`START_THRESH`:** Anjakan ternormal per bingkai melebihi ambang mula selama `2` bingkai berturut-turut (menapis gegaran mikro tangan).
- **`PAUSE_MS`:** `280ms` berturut-turut di bawah `STOP_THRESH` dianggap gerakan sudah selesai (memberi ruang jeda singkat pertengahan kata berangkai).
- **`MIN_FRAMES`:** Sekurang-kurangnya `8` bingkai aktif (jika kurang daripada `8` bingkai, anggap tersentuh/bising dan kembali ke `IDLE` dengan maklum balas `"Gerakan terlalu pendek"`).
- **`MAX_CLIP_MS`:** `2400ms` (maksimum ~65 bingkai) sebelum dinilai secara paksa supaya pengguna yang menggerakkan tangan berulang-ulang (seperti `MAKAN` / `MINUM` / `BIRI-BIRI`) tetap mendapat penilaian serta-merta tanpa perlu menunggu selamanya.
- **`EVALUATING` Sekali Sahaja:** Sebaik masuk ke `EVALUATING`, klip diproses sekali (buang bingkai pegun di hujung jika panjang > 12 dengan mengekalkan 2 bingkai konteks, resample ke `30` bingkai), skor dikira sekali, UI dikemas kini, dan jika sepadan (`matched === true`), terus panggil `onGestureSuccess(conf)`. **Tidak lagi memerlukan `4` bingkai berturut-turut semasa masih bergerak!**

---

### D. Enjin Penilaian DTW, `J`/`Z` & Tingkah Laku Isyarat `0` Klip (Pilihan A)

**1. Penalaan `dtwDistance()` & `SequenceClassifier` dalam `gesture-ai.js`:**
- **Kurangkan penalti halaju trajektori:** Tukar pekali `dtv * dtv * 14.0` (baris 1067) kepada **`dtv * dtv * 1.8`** (serta kurangkan berat `velSq` daripada `1.6` kepada `0.9`) supaya pergerakan yang sedikit lebih pantas atau lambat daripada templat asal tidak dihukum melampau.
- **Sokongan dwi-tangan dalam `multimodalSqDist` / `dtwDistance`:**
  - Bandingkan Slot 2 (`78..140`) dan ciri hubungan dwi-tangan (`141..144`) **hanya jika kedua-dua klip ujian dan templat mempunyai ciri dwi-tangan (`f.length >= 145`)**, dengan semakan penalti kecil jika templat memerlukan 2 tangan (`hasHand2` tinggi sepanjang klip) tetapi pengguna hanya mengangkat 1 tangan.
  - Apabila membandingkan dengan `21` klip lama (`len === 78`), guna hanya dimensi `0..77` supaya keserasian penuh terpelihara.
- **Kalibrasi `confidence` DTW:** Penyahskalaan `distScore = clamp(1 - minDistance / 0.58, 0, 1)` (dinaikkan sedikit daripada `0.42` selepas penyingkiran letupan penalti `14.0`, dengan ambang lulus `>= 55%` pada penilaian akhir `EVALUATING` yang sudah bebas bunyi awal).

**2. Isyarat `J` dan `Z` (Dinamik Abjad):**
- Dinilai di akhir klip `CAPTURING` menggunakan keseluruhan jujukan bingkai yang dirakam (dan juga berpandukan jejak berterusan seperti `detectJMotion` / `detectZMotion` untuk mengekalkan kepekaan pantas bagi huruf J/Z). Memastikan jari mula (`isPinkyUpShape` bagi `J`, `isIndexPointingShape` bagi `Z`) hadir sepanjang bahagian utama klip sebelum meluluskan gerakan cangkuk/zig-zag.

**3. Laluan Isyarat Dinamik Dengan `0` Klip DTW (Mengikut Pilihan A — Disahkan):**
Bagi 53 isyarat dinamik yang belum mempunyai sebarang klip `sequence` dalam `GestureSample`:
1. **Penapisan Peraturan `BIM_FACE_RULES` (`"bebas"` & `"neutral"` Diabaikan):**
   - Zon generik `"bebas"` dan ekspresi `"neutral"` **diabaikan** semasa menyemak syarat lulus (dan turut dikecualikan daripada bonus positif dalam `evaluateFaceSignBoost()` supaya skor DTW seperti `ASSALAMUALAIKUM` tidak melambung secara buatan).
   - Hanya peraturan zon khusus (`dahi`, `mata`, `pipi`, `mulut`, `dagu`, `dada`) dan ekspresi nyata (`gembira`, `sedih`, `marah`, `terkejut`, `soalan`) dikira sebagai kekangan sah.
   - Jika suatu entri mempunyai kedua-dua zon khusus **dan** ekspresi nyata (contoh: `GEMBIRA`, `SUKA`, `MAAF`, `SELAMAT_PAGI`, `SIAPA`, `KENAPA`), **kedua-dua syarat mesti dipenuhi serentak (`zones && exprs`)** bersama julat anjakan gerakan aktif melepasi `MIN_DYNAMIC_TRAVEL`.
   - Jika tiada zon khusus dan tiada ekspresi nyata berbaki selepas penapisan (seperti `SELAMAT_TINGGAL`), isyarat tersebut masuk ke **Mod Cermin** sehingga klip DTW dirakam di AI Studio.
2. **Apabila AI Wajah Dimatikan atau Wajah Tidak Kelihatan:**
   - Bagi isyarat berpandukan peraturan `BIM_FACE_RULES` (`0` klip), jika `#btn-toggle-face` dimatikan oleh pelajar, model `FaceLandmarker` gagal dimuat, atau wajah tiada dalam bingkai kamera (`!latestFaceContext?.hasFace`):
   - **Jangan luluskan automatik.** Paparkan mesej panduan khusus *"Aktifkan AI Wajah dan tunjukkan wajah anda untuk menyemak isyarat ini (atau tekan Tandakan Selesai manual)"* dan tahan keyakinan di bawah ambang lulus.
3. **Jika TIADA klip DTW (`0` klip) DAN TIADA kekangan `BIM_FACE_RULES`:**
   - **Jangan luluskan dengan gerakan rawak.**
   - Kamera masih berfungsi sebagai **Mod Cermin Interaktif** (melukis rangka 2 tangan + bahu + jejak gerakan supaya pelajar boleh berlatih meniru video), sambil memaparkan lencana status jujur di header kamera: `"Mod Cermin (Klip AI belum dirakam)"` dan teks panduan: `"Berlatih ikut video di sebelah, kemudian tekan 'Tandakan Isyarat Ini Sebagai Selesai' di bawah."`

---

### E. Penyelarasan AI Studio (`admin/ai-studio.blade.php`)

- Mod kamera **Dinamik (DTW)** di AI Studio dinaik taraf supaya turut mengaktifkan `numHands: 2` secara lalai dan memuatkan `PoseLandmarker`.
- **Mod Rakaman Klip Gerakan:** Ditambah pilihan rakaman **Auto Mula–Gerak–Tamat** (lalai bagi mod dinamik) menggunakan `MotionSegmenter` yang sama — selepas kiraan detik selesai, status menunggu sehingga tangan mula bergerak, merakam hingga gerakan berhenti, memotong lebihan tepi dengan selamat, dan menyimpan vektor `146`-ciri ke pangkalan data (`AiTrainingController::storeSample()`).
- **Penguji Langsung (*Live Tester*) Dwi-Mod (Disahkan):** Penguji dinamik di AI Studio berkongsi `DynamicGestureEngine` yang sama dengan `learn/sign.blade.php` dan menyediakan **togol dua mod**:
  1. **Mod Terbuka (*Open-Set* — lalai Studio):** Menilai klip yang baru tamat terhadap kesemua templat DTW tersimpan + `J`/`Z` untuk mengesan kelas pemenang terdekat dan melihat potensi kekeliruan antara isyarat.
  2. **Simulasi Pelajar (*Target-Locked*):** Mengunci penilaian kepada `Isyarat Sasaran Aktif` supaya pentadbir/penguji dapat melihat pengalaman 1-ke-1 pelajar termasuk peraturan `BIM_FACE_RULES` (Pilihan A) dan Mod Cermin `0`-klip.

---

### F. Pelan Pengesahan & Ujian (*Verification*)

1. **Ujian PHPUnit / Laravel (`AiTrainingController` & `GestureSample`):**
   - Menulis dan menjalankan ujian automatik Feature/Unit mengesahkan `GestureSample::normalise()` dan `POST /admin/ai-studio/sample` (`sample_type = 'sequence'`) menerima kedua-dua vektor lama (`63..78`) dan vektor baharu v2 (`146`), manakala eksport CSV statik tidak rosak.
2. **Ujian Logik JS (`gesture-ai.js`):**
   - Menjalankan ujian automatik Node (ES module test script) ke atas:
     - `MotionSegmenter` (`IDLE -> CAPTURING -> EVALUATING -> COOLDOWN`), termasuk penimbal pra-rakaman dan tamat pada keadaan pegun.
     - `dtwDistance()` & `SequenceClassifier` dengan klip sintetik berkelajuan berbeza (`1.0x` vs `1.5x` laju), klip 2 tangan, serta keserasian klip lama `78`-ciri terhadap bingkai ujian `146`-ciri.
     - Peraturan ketat `0`-klip: `"bebas"`/`"neutral"` diabaikan, `zones && exprs` diwajibkan apabila kedua-duanya wujud, penolakan apabila `AI Wajah` dimatikan, serta penolakan tanpa lulus palsu bagi isyarat Mod Cermin.
     - Laluan dwi-mod `DynamicGestureEngine` (`open-set` berbilang kelas vs `target-locked` pelajar).
3. **Semakan Visual & Prestasi Kamera Langsung:**
   - Memastikan FPS kamera kekal lancar (`>= 20 FPS`), tidak membeku semasa transisi `EVALUATING`, dan lencana status UI/Kiko memaparkan maklum balas Mula/Gerak/Nilai/Cermin dengan tepat.

---

## 6. Keputusan Yang Ditinggalkan Kepada Pelaksana (*Builder Calls*)

Perkara berikut ditetapkan sebagai keputusan kejuruteraan munasabah dalam dokumen ini (boleh diubah semasa pelaksanaan tanpa mengubah objektif produk):
- Dimensi tepat vektor v2 (`146` ciri dengan `features[141..145]` sebagai flag `hasHand2`, offset tangan kedua & `hasPose`).
- Nilai ambang angka awal `MotionSegmenter` (`START_THRESH`, `STOP_THRESH`, `PAUSE_MS = 280ms`, `MAX_CLIP_MS = 2400ms`, `COOLDOWN = 450ms`) yang akan ditala melalui ujian simulasi kelajuan dan ujian kamera sebenar.
- Pola selang (*interleaving*) ganjil-genap untuk `PoseLandmarker` dan `FaceLandmarker` supaya beban CPU pelayar kekal rendah.
- Penyingkiran bonus percuma untuk `"bebas"`/`"neutral"` dalam `evaluateFaceSignBoost()` supaya keyakinan DTW (seperti `ASSALAMUALAIKUM`) mencerminkan kesepadanan gerakan sebenar.
- Menetapkan **Mod Terbuka (*Open-Set*)** sebagai mod lalai togol *Live Tester* di AI Studio dengan pilihan togol ke **Simulasi Pelajar (*Target-Locked*)**.

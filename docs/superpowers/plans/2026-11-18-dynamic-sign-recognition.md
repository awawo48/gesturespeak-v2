# Dynamic Sign Language Recognition Upgrade Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menaik taraf pengecaman bahasa isyarat dinamik dalam pelayar bagi `gesturespeak-v2` supaya menyokong **2 tangan + rujukan bahu (*Pose*)**, penilaian automatik **Mula–Gerak–Tamat (*Motion State-Machine*)**, penalaan jarak DTW yang toleran kelajuan, tingkah laku ketat Pilihan A bagi isyarat `0` klip, dan penyelarasan dwi-mod AI Studio.

**Architecture:** Naik taraf enjin modul ES sisi klien (`public/js/gesture-ai.js`) untuk mengekstrak vektor multimodal `146` ciri (serasi ke belakang dengan `63`/`78` ciri lama), memproses segmen gerakan melalui `MotionSegmenter` dan `DynamicGestureEngine`, serta menyambungkannya ke gelung penglihatan berselang (`HandLandmarker` `numHands: 2`, `PoseLandmarker` lite, `FaceLandmarker`) dalam `sign.blade.php` dan `ai-studio.blade.php` bersama sokongan penyimpanan `GestureSample` di Laravel.

**Tech Stack:** Laravel 13 (`PHP 8.3`), PHPUnit, ES Modules (`JavaScript`), MediaPipe `@mediapipe/tasks-vision@0.10.14` (`HandLandmarker`, `PoseLandmarker`, `FaceLandmarker`), Node `v22.14.0` built-in test runner (`node --test`).

**Spec:** `docs/superpowers/specs/2026-11-18-dynamic-sign-recognition-design.md`

---

## Global Constraints

- **100% dalam pelayar:** Semua inferens penglihatan komputer berjalan dalam pelayar via `@mediapipe/tasks-vision@0.10.14` (tiada pelayan Python tambahan).
- **Keserasian data lama:** Semua `991` sampel statik dan `21` klip `sequence` sedia ada (`63` atau `78` ciri/bingkai, khususnya `J: 9`, `Z: 3`, `ASSALAMUALAIKUM: 9`) mesti terus sah dibaca dan dinilai tanpa penghijrahan DB yang merosakkan.
- **Keserasian CSV statik:** Aliran `AiTrainingController::exportCsv()` bagi `sample_type = 'static'` tidak boleh terganggu.
- **Tiada kelulusan palsu (Pilihan A):** Isyarat dinamik `0` klip tanpa peraturan wajah sah mesti kekal dalam Mod Cermin dan tidak boleh lulus daripada pergerakan rawak; `"bebas"` dan `"neutral"` mesti diabaikan.
- **Imbangan FPS pelayar:** `PoseLandmarker` dan `FaceLandmarker` mesti dijalankan secara berselang-seli (ganjil/genap) serta mempunyai *graceful fallback* GPU $\rightarrow$ CPU dan bila gagal dimuat.

## Review Focus

1. **Pertukaran tangan kiri/kanan semasa bergerak (`handedness` hilang atau bertukar slot):** Jika pengguna mengangkat 2 tangan atau menukar tangan aktif, `selectHandsOrder` dan `buildMultimodalVector` mesti memastikan susunan slot 1 vs slot 2 konsisten supaya DTW tidak melonjak palsu — disahkan oleh ujian di Task 2.
2. **Bahu / Pose di luar bingkai atau `PoseLandmarker` tidak tersedia:** Apabila `poseLm` tiada atau bahu hampir bertindih (`shoulderWidth < 0.08`), `extractPoseContext` mesti kembali `null` secara selamat tanpa `NaN`/`Infinity` dan jatuh balik kepada rujukan wajah/kamera — disahkan oleh ujian di Task 2.
3. **Keserasian silang dimensi DTW (`78`-ciri DB lama vs `146`-ciri live):** Membandingkan klip `146`-ciri dengan `21` templat `78`-ciri lama mesti membandingkan dimensi kongsi `0..77` secara tepat, manakala klip 2-tangan (`146` vs `146`) menyemak tangan kedua — disahkan oleh ujian di Task 3.
4. **Pintasan `"bebas"` / `"neutral"`, AI Wajah dimatikan, & gerakan melebihi had masa:** Isyarat dengan `"bebas"`/`"neutral"` (seperti `SELAMAT_TINGGAL`), padanan separa zon tanpa ekspresi (`GEMBIRA`), atau wajah dimatikan (`faceAiEnabled: false`) tidak boleh lulus palsu; gerakan berulang > `2400ms` mesti dinilai automatik tanpa tersangkut — disahkan oleh ujian di Task 4.
5. **Validasi pelayan untuk vektor `63..146` serta integriti Hand 2 yang sifar:** `GestureSample::normalise()` mesti menormalisasi tangan kedua apabila wujud tanpa menghasilkan `NaN` atau memecahkan bingkai `0` apabila hanya 1 tangan hadir — disahkan oleh ujian PHPUnit di Task 1.

---

### Task 1: Sokongan Skema Vektor `63..146` di `GestureSample` & `AiTrainingController`

**Files:**
- Modify: `app/Models/GestureSample.php:39-80`
- Modify: `app/Http/Controllers/AiTrainingController.php:95-122`
- Test: `tests/Feature/DynamicGestureSampleTest.php`

**Interfaces:**
- Produces:
  - `GestureSample::normalise(array $features): array` — menerima `63`, `78`, atau `146` float; menormalisasi tangan 1 (`0..62`), mengehadkan konteks (`63..77` kepada `[-2.5, 2.5]`), menormalisasi tangan 2 (`78..140`) secara idempoten apabila `maxAbs > 1e-6` (kekal `0.0` jika tangan 2 tiada), dan mengehadkan slot konteks dwi-tangan/pose (`141..145` kepada `[-3.0, 3.0]`).
  - `POST /admin/ai-studio/sample` (`sample_type: 'sequence'`) menerima `frames.*` dengan panjang `min:63, max:146`.

- [ ] **Step 1: Tulis ujian gagal untuk `GestureSample::normalise()` (v1 `78` & v2 `146`, tangan 2 sifar vs aktif) dan endpoint `storeSample` (`sequence` `146`-D & penolakan panjang tidak sah)**

```php
// tests/Feature/DynamicGestureSampleTest.php
// test_normalise_preserves_78_and_normalises_146_with_and_without_second_hand()
// test_admin_can_store_146_feature_sequence_and_legacy_78_feature_sequence()
// test_static_export_csv_remains_valid_with_mixed_sequence_rows()
```
Pastikan assertions menyemak: `count($res78) === 78`, `count($res146ZeroHand2) === 146` dengan indeks `78..140` kekal `0.0`, `count($res146TwoHands) === 146` dengan tangan 2 ternormal pada `[-1.0, 1.0]` dan idempoten (`normalise($res146TwoHands) === $res146TwoHands`), status HTTP `200` untuk `146`-ciri sequence, `422` untuk `frames.*` sepanjang `147`, dan eksport CSV hanya mengandungi baris `static`.

- [ ] **Step 2: Jalankan PHPUnit untuk sahkan ia gagal**

Run: `C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe artisan test tests/Feature/DynamicGestureSampleTest.php`  
Expected: FAIL (`count($res146)` masih dipotong ke `78` dan validasi memulangkan `422` untuk `146` ciri).

- [ ] **Step 3: Kemas kini `GestureSample::normalise()` dan `AiTrainingController::storeSample()`**

- Ekstrak pembantu peribadi `normaliseHandSlice(array $slice): array` dalam `app/Models/GestureSample.php` supaya tangan 1 (`0..62`) dan tangan 2 (`78..140`) berkongsi logik penormalan pergelangan tangan yang sama (pulangkan `63` angka `0.0` jika `maxAbs <= 1e-6`).
- Jika `count($features) >= 146`: sambung `$handNorm` (63) + `$faceNorm` (`63..77` diapit `[-2.5, 2.5]`) + `normaliseHandSlice(array_slice($features, 78, 63))` + `$dwiNorm` (`141..145` diapit `[-3.0, 3.0]`).
- Dalam `app/Http/Controllers/AiTrainingController.php`, kemas kini peraturan validasi `frames.*` kepada `['required', 'array', 'min:63', 'max:146']`.

- [ ] **Step 4: Jalankan PHPUnit untuk sahkan semua ujian lulus**

Run: `C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe artisan test tests/Feature/DynamicGestureSampleTest.php`  
Expected: PASS (3 tests passing).

- [ ] **Step 5: Commit Task 1**

```bash
git add app/Models/GestureSample.php app/Http/Controllers/AiTrainingController.php tests/Feature/DynamicGestureSampleTest.php
git commit -m "feat(ai): support 146-dim two-hand pose sequence samples in backend"
```

---

### Task 2: Ekstraksi Pose Bahu, Dwi-Tangan & Vektor Multimodal v2 (`gesture-ai.js`)

**Files:**
- Modify: `public/js/gesture-ai.js:17-330,1194-1219`
- Test: `tests/js/gesture-features.test.mjs`

**Interfaces:**
- Produces (exports dari `public/js/gesture-ai.js`):
  - `FULL_V2_FEATURE_DIM = 146`
  - `extractPoseContext(poseLm): { hasPose: boolean, shoulderCenter: {x,y,z}, shoulderWidth: number, nose: {x,y,z}|null, anchors: object|null } | null`
  - `selectHandsOrder(handsLm, handedness = null, preferredPrimary = "Right"): { primary: Array|null, secondary: Array|null, primaryIdx: number }`
  - `buildMultimodalVector(handsLm, handedness = null, faceContext = null, poseContext = null): Array<number>` (panjang tepat `146`)
  - `drawPoseAnchors(ctx, poseLm, w, h, poseContext): void`
  - `SequenceBuffer`: menerima bingkai panjang `63 | 78 | 146`

- [ ] **Step 1: Tulis ujian gagal `tests/js/gesture-features.test.mjs`**

Test cases menggunakan `node:test` dan `node:assert/strict`:
1. `extractPoseContext` mengembalikan `hasPose: false` / `null` jika bahu kiri/kanan tiada, keterlihatan `< 0.35`, atau `shoulderWidth < 0.08`, dan mengira `shoulderCenter` & `shoulderWidth` tepat bila sah.
2. `selectHandsOrder` memastikan tangan `"Right"` dipilih sebagai `primary` dan `"Left"` sebagai `secondary` tanpa mengira susunan tatasusunan MediaPipe, dan jatuh balik teratur bila `handedness` tiada.
3. `buildMultimodalVector` memulangkan tatasusunan tepat `146` nombor tanpa `NaN`/`Infinity`:
   - 1 tangan + tanpa wajah + ada Pose $\rightarrow$ `vec[77] === 0.5`, `vec[141] === 0`, `vec[145] === 1`, `vec[78..140]` semua `0`.
   - 2 tangan + ada wajah + ada Pose $\rightarrow$ `vec[77] === 1`, `vec[141] === 1`, `vec[144] > 0` (jarak antara pergelangan tangan), `vec[145] === 1`, dan `vec[78..140]` ternormal `[-1, 1]`.
   - 1 tangan + tanpa wajah + tanpa Pose $\rightarrow$ `vec[77] === 0.25`, `vec[145] === 0`.
4. `SequenceBuffer` menerima bingkai panjang `146` dan menolak panjang luar `63/78/146`.

- [ ] **Step 2: Jalankan ujian JS untuk sahkan ia gagal**

Run: `node --test tests/js/gesture-features.test.mjs`  
Expected: FAIL (`SyntaxError` / eksport tidak wujud).

- [ ] **Step 3: Implementasi fungsi Pose & Dwi-Tangan dalam `public/js/gesture-ai.js`**

- Tambah `FULL_V2_FEATURE_DIM = 146`.
- Tambah `extractPoseContext(poseLm)` menggunakan indeks MediaPipe Pose (`11` bahu kiri, `12` bahu kanan, `0` hidung; sah jika `visibility ?? 1 >= 0.35` dan lebar `>= 0.08`).
- Tambah `selectHandsOrder(handsLm, handedness, preferredPrimary)`: membaca kategori `handedness?.[i]?.[0]?.categoryName` (case-insensitive) dengan *fallback* susunan indeks.
- Kemas kini `extractFaceContext` & tambah `buildMultimodalVector(handsLm, handedness, faceContext, poseContext)`: kekalkan `buildFeatureVector(handLm, faceContext)` sebagai pembungkus serasi bagi pemanggil `78`-ciri statik, dan jana vektor `146`-ciri bagi aliran dwi-tangan/dinamik.
- Tambah `drawPoseAnchors(ctx, poseLm, w, h, poseContext)` melukis garisan bahu halus & titik rujukan.
- Kemas kini `SequenceBuffer.push(frame)` untuk menerima `63`, `78`, dan `146`.

- [ ] **Step 4: Jalankan ujian JS untuk sahkan lulus**

Run: `node --test tests/js/gesture-features.test.mjs`  
Expected: PASS (semua kes lulus).

- [ ] **Step 5: Commit Task 2**

```bash
git add public/js/gesture-ai.js tests/js/gesture-features.test.mjs
git commit -m "feat(ai): add Pose shoulder normalization and 146-dim two-hand feature builder"
```

---

### Task 3: Penalaan DTW Dwi-Tangan/Kelajuan & Penapisan Ketat `BIM_FACE_RULES` (`gesture-ai.js`)

**Files:**
- Modify: `public/js/gesture-ai.js:403-487,544-590,872-1190`
- Test: `tests/js/dtw-and-rules.test.mjs`

**Interfaces:**
- Consumes: `FULL_V2_FEATURE_DIM` (Task 2)
- Produces (exports / behaviour in `public/js/gesture-ai.js`):
  - `getEffectiveFaceRule(label): { zones: string[]|null, exprs: string[]|null, strictZone: boolean, hasConstraint: boolean, hintNeed: string|null, hintExpr: string|null, hintOk: string|null } | null`
  - `evaluateFaceSignBoost(targetLabel, faceContext)` — tidak lagi memberi `+bonus` untuk `"bebas"` atau `"neutral"`.
  - `dtwDistance(seqA, seqB, useFace = false, mirror = false): number` — menyokong jarak dwi-tangan apabila kedua-duanya `>= 145` dan kekal serasi `0..77` untuk templat `78`-ciri.
  - `SequenceClassifier` (`targetLength = 30`, `load`, `predict(testFrames, faceContext = null, targetLabel = null)`) — apabila `targetLabel` diberi, tapis templat kepada kelas sasaran itu sahaja; apabila `null`, nilai secara `open-set` merentasi semua templat dan pulangkan `{ label, confidence, distance, usedFace }`.

- [ ] **Step 1: Tulis ujian gagal `tests/js/dtw-and-rules.test.mjs`**

Test cases:
1. `getEffectiveFaceRule`:
   - `SELAMAT_TINGGAL` (`zones: ["bebas"], exprs: ["gembira", "neutral"]` atau jika hanya `"bebas"`/`"neutral"`): tapis keluar `"bebas"` dan `"neutral"`; untuk `ABANG`/`TOLONG`/`MAAF`/`GEMBIRA`/`SUKA`, `"bebas"` dan `"neutral"` dikecualikan supaya `TOLONG.zones` hanya `["dada"]`, `GEMBIRA.zones` hanya `["dada"]` dan `exprs` `["gembira"]`, dan untuk `SELAMAT_TINGGAL` `zones` menjadi `null` (tinggal `exprs: ["gembira"]` jika dikekalkan, atau pastikan tiada zon `"bebas"`). *Semakan tepat:* uji `TOLONG` mengekalkan `["dada"]`, `MAAF` mengekalkan `zones: ["dada"]` dan `exprs: ["sedih"]`, dan `evaluateFaceSignBoost("ASSALAMUALAIKUM", neutralBebasCtx).bonus === 0`!
2. Kelajuan DTW: dua klip gerakan tangan yang sama pada kelajuan `1.0x` (30 bingkai) berbanding `1.6x` (18 bingkai sebelum resample) mesti mendapat `confidence >= 68%` melalui `SequenceClassifier.predict()`.
3. Keserasian klip lama `78`-ciri terhadap ujian `146`-ciri: menghasilkan skor tinggi untuk gerakan sepadan dan jarak jauh (`confidence < 45%`) untuk gerakan arah bertentangan.
4. Klip 2 tangan (`146` vs `146`): gerakan yang sepadan dengan 2 tangan menewaskan gerakan yang hanya mengangkat 1 tangan bagi templat dwi-tangan (`hasHand2` aktif).

- [ ] **Step 2: Jalankan ujian JS untuk sahkan ia gagal**

Run: `node --test tests/js/dtw-and-rules.test.mjs`  
Expected: FAIL (`getEffectiveFaceRule` belum dieksport & `evaluateFaceSignBoost` masih memberi `+17` untuk `"bebas"` + `"neutral"`).

- [ ] **Step 3: Kemas kini `dtwDistance`, `SequenceClassifier`, `trimIdleSequenceFrames`, dan `evaluateFaceSignBoost` dalam `gesture-ai.js`**

- Dalam `dtwDistance`:
  - Tukar berat trajektori halaju daripada `14.0` kepada `1.8` (`trajSq += dt * dt * 0.85 + dtv * dtv * 1.8`) dan berat `velSq` daripada `1.6` kepada `0.9`.
  - Sokong penanda bahu `f[77] >= 0.45` selain wajah (`hasTraj` aktif apabila `f[77] >= 0.2`).
  - Apabila kedua-dua `A` dan `B` berpanjang `>= 145` dan sekurang-kurangnya satu mempunyai `hasHand2 > 0.5`: tambah kos jarak pergelangan + bentuk jari Tangan 2 (`78..144`), termasuk penalti seimbang apabila templat guna 2 tangan tetapi ujian hanya 1 tangan.
- Dalam `resampleSequence` & `trimIdleSequenceFrames`: sokong dimensi `63`, `78`, dan `146`, kekalkan flag `77`, `141`, `145` pada sempadan, dan simpan 2 bingkai konteks tepi.
- Dalam `SequenceClassifier`:
  - Gunakan `distScore = clamp(1 - minDistance / 0.58, 0, 1)`.
  - Tambah sokongan kaedah terus `predictTemplates(frames, faceContext)` (berbanding menuntut gelung luaran).
- Tambah `getEffectiveFaceRule(label)` yang menapis `"bebas"` daripada `zones` dan `"neutral"` daripada `exprs`, dan kemas kini `evaluateFaceSignBoost()` supaya hanya memberi bonus jika zon khusus atau ekspresi nyata benar-benar sepadan.

- [ ] **Step 4: Jalankan ujian JS untuk sahkan lulus**

Run: `node --test tests/js/dtw-and-rules.test.mjs`  
Expected: PASS (semua kes lulus).

- [ ] **Step 5: Commit Task 3**

```bash
git add public/js/gesture-ai.js tests/js/dtw-and-rules.test.mjs
git commit -m "feat(ai): calibrate DTW velocity sensitivity, add 2-hand matching, and harden face rules"
```

---

### Task 4: `MotionSegmenter` (Mula–Gerak–Tamat) & `DynamicGestureEngine` (`gesture-ai.js`)

**Files:**
- Modify: `public/js/gesture-ai.js` (bahagian akhir modul)
- Test: `tests/js/dynamic-engine.test.mjs`

**Interfaces:**
- Consumes: `FULL_V2_FEATURE_DIM`, `selectHandsOrder`, `buildMultimodalVector` (Task 2); `SequenceClassifier`, `getEffectiveFaceRule`, `evaluateFaceSignBoost`, `trimIdleSequenceFrames`, `isPinkyUpShape`, `isIndexPointingShape` (Task 3)
- Produces:
  - `MotionSegmenter(opts?)`:
    - `state`: `'IDLE' | 'CAPTURING' | 'COOLDOWN'`
    - `pushFrame({ frameVec, handsLm, primaryHand, faceContext, timestampMs }): { state, event: 'started' | 'completed' | 'too_short' | null, clip: Array|null, handsHistory: Array|null, faceHistory: Array|null, durationMs: number }`
    - `reset(): void`
  - `DynamicGestureEngine(opts?)`:
    - `setTemplates(sequences): void`
    - `evaluateClip({ mode: 'target-locked' | 'open-set', targetLabel, clipFrames, handsHistory, faceHistory, faceAiEnabled }): { status: 'matched' | 'miss' | 'mirror' | 'need_face', matched: boolean, label: string|null, confidence: number, hint: string, source: 'jz' | 'dtw' | 'face-rule' | 'mirror' }`

- [ ] **Step 1: Tulis ujian gagal `tests/js/dynamic-engine.test.mjs`**

Test cases (merangkumi semua kes **Review Focus #4**):
1. **Kitaran lengkap `MotionSegmenter` (`IDLE -> CAPTURING -> completed -> COOLDOWN -> IDLE`):** Suapan 6 bingkai pegun + 16 bingkai bergerak + 10 bingkai pegun (`PAUSE_MS = 280ms` dicapai) $\rightarrow$ mesti mengeluarkan `event: 'completed'` sekali dengan klip merangkumi bingkai pra-rakaman (`>= 18` bingkai).
2. **Gerakan terlalu singkat (`< 8` bingkai aktif):** Gerak selama 3 bingkai lalu diam $\rightarrow$ `event: 'too_short'` dan tidak menghantar klip penilaian.
3. **Had maksimum `MAX_CLIP_MS` (`2400ms`):** Gerakan berterusan melebihi `2400ms` mesti mencetuskan `event: 'completed'` secara paksa tanpa tersekat.
4. **Pilihan A (`0` klip) — `BIM_FACE_RULES` ketat:**
   - Bagi `GEMBIRA` (`0` klip): lulus HANYA jika gerakan aktif wujud **DAN** kedua-dua zon `dada` serta ekspresi `gembira` dikesan (`>= 2` bingkai); jika hanya zon `dada` dengan ekspresi `neutral` $\rightarrow$ `status: 'miss'` dengan panduan ekspresi.
   - Bagi `BAPA` (`0` klip) apabila `faceAiEnabled: false` atau tiada wajah dalam `faceHistory` $\rightarrow$ `status: 'need_face'`, `matched: false` dengan gesaan hidupkan AI Wajah.
   - Bagi `BUKU` (`0` klip, tiada peraturan) atau `SELAMAT_TINGGAL` (jika tiada kekangan zon/ekspresi mencukupi atau Mod Cermin): apabila `0` klip dan tiada peraturan wajib $\rightarrow$ `status: 'mirror'`, `matched: false` walau sekuat mana tangan digerakkan.
5. **Dwi-Mod `open-set` vs `target-locked`:** Mod `open-set` memulangkan label terbaik daripada semua templat DTW (`ASSALAMUALAIKUM`) atau `J`/`Z` walaupun `targetLabel` ialah label lain, manakala `target-locked` mengikut `targetLabel`.
6. **`J` dan `Z` dalam mod klip:** Gerakan cangkuk kelengkeng `J` dinilai tepat pada klip lengkap tanpa rosak apabila tangan berhenti di penghujung cangkuk.

- [ ] **Step 2: Jalankan ujian JS untuk sahkan ia gagal**

Run: `node --test tests/js/dynamic-engine.test.mjs`  
Expected: FAIL (`MotionSegmenter` / `DynamicGestureEngine` belum wujud).

- [ ] **Step 3: Implementasi `MotionSegmenter` dan `DynamicGestureEngine` dalam `public/js/gesture-ai.js`**

- `MotionSegmenter`: hitung anjakan antara bingkai berturutan menggunakan ciri ternormal (tangan 1 + tangan 2 + konteks bahu/trajektori `63..66`), guna penimbal pra-rakaman 6 bingkai, jeda tamat `280ms`, `MIN_ACTIVE_FRAMES = 8`, `MAX_CLIP_MS = 2400ms`, dan `COOLDOWN_MS = 450ms`.
- `DynamicGestureEngine`: membungkus `SequenceClassifier` dan melaksanakan laluan penilaian:
  1. Bagi `target-locked`: `J`/`Z` (semak bentuk jari dominan + `detectJMotion`/`detectZMotion` ke atas `handsHistory` klip) $\rightarrow$ templat DTW jika `seqClassifier.labels.includes(target)` (ambang `>= 55%`) $\rightarrow$ Pilihan A `getEffectiveFaceRule(target)` (`faceAiEnabled && hasFace` wajib; `zones && exprs` mesti dipenuhi serentak) $\rightarrow$ `status: 'mirror'` bagi `0`-klip tanpa kekangan.
  2. Bagi `open-set`: uji `J`/`Z` terlebih dahulu jika bentuk jari jelas $\rightarrow$ uji `seqClassifier.predict(clipFrames, peakFaceCtx)` merentasi semua templat tersimpan dan pulangkan pemenang jika `confidence >= 52%`.

- [ ] **Step 4: Jalankan kesemua ujian JS untuk sahkan lulus**

Run: `node --test tests/js/*.test.mjs`  
Expected: PASS across all 3 JS test files.

- [ ] **Step 5: Commit Task 4**

```bash
git add public/js/gesture-ai.js tests/js/dynamic-engine.test.mjs
git commit -m "feat(ai): implement MotionSegmenter state-machine and DynamicGestureEngine"
```

---

### Task 5: Integrasi Kamera Pelajar (`sign.blade.php`) & AI Studio Dwi-Mod (`ai-studio.blade.php`)

**Files:**
- Modify: `resources/views/learn/sign.blade.php:190-245,280-1005`
- Modify: `resources/views/admin/ai-studio.blade.php:795-965,1078-1260,1410-1775,2005-2295`
- Test: `tests/Feature/DynamicViewsSmokeTest.php`

**Interfaces:**
- Consumes: Semua eksport daripada Task 2, Task 3, dan Task 4 (`PoseLandmarker` dari `@mediapipe/tasks-vision@0.10.14` + `extractPoseContext`, `selectHandsOrder`, `buildMultimodalVector`, `drawPoseAnchors`, `MotionSegmenter`, `DynamicGestureEngine`).

- [ ] **Step 1: Tulis ujian Feature Laravel `tests/Feature/DynamicViewsSmokeTest.php`**

Ujian memastikan:
1. Halaman belajar isyarat dinamik tanpa klip (`TERIMA_KASIH` atau `BUKU`) memaparkan UI Mod Dinamik baharu tanpa ralat pelayan (status `200`) dan menyertakan penanda status dinamik.
2. Halaman AI Studio (`admin`) memaparkan togol dwi-mod *Live Tester* (`Open-Set` & `Simulasi Pelajar`) serta mod rakaman auto gerakan tanpa ralat Blade (status `200`).

- [ ] **Step 2: Jalankan ujian Feature untuk sahkan ia gagal**

Run: `C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe artisan test tests/Feature/DynamicViewsSmokeTest.php`  
Expected: FAIL (penanda elemen UI baharu belum wujud dalam `sign.blade.php` dan `ai-studio.blade.php`).

- [ ] **Step 3: Kemas kini `resources/views/learn/sign.blade.php` & `resources/views/admin/ai-studio.blade.php`**

1. **`resources/views/learn/sign.blade.php`:**
   - Muatkan `PoseLandmarker` (`pose_landmarker_lite.task`) dalam `initPoseMediaPipe()` (hanya apabila `!isStaticSign`, GPU dengan *CPU fallback*).
   - Tetapkan `HandLandmarker` kepada `numHands: isStaticSign ? 1 : 2`.
   - Dalam `predictWebcam()`: jalankan `HandLandmarker` setiap bingkai kamera, selang-selikan `PoseLandmarker` (bingkai genap) dan `FaceLandmarker` (bingkai ganjil). Lukis kedua-dua tangan + rangka bahu (`drawPoseAnchors`).
   - Bagi `!isStaticSign`, hantar bingkai `146`-ciri (`buildMultimodalVector`) ke `MotionSegmenter`:
     - Semasa `IDLE`: tunjuk status sedia atau lencana `"Mod Cermin (Klip AI belum dirakam)"` jika `0` klip dan tiada peraturan `getEffectiveFaceRule(targetSign)`.
     - Semasa `CAPTURING`: tunjuk `"Mengesan gerakan..."` berserta jejak neon tangan aktif.
     - Pada `event === 'completed'`: panggil `dynamicEngine.evaluateClip({ mode: 'target-locked', ... })`. Jika `matched === true`, terus panggil `onGestureSuccess(res.confidence)`. Jika `need_face` / `miss` / `too_short` / `mirror`, papar maklum balas tepat yang disyorkan `res.hint`.
2. **`resources/views/admin/ai-studio.blade.php`:**
   - Tambah `initPoseModel()` dan jalankan `PoseLandmarker` berselang-seli semasa `studioCameraMode === "dynamic"`, serta auto-set `numHands = 2` apabila menukar ke Mod Dinamik (`setStudioCameraMode("dynamic")`).
   - Naik taraf `recordSequence()` supaya menyokong mod **Auto Mula–Gerak–Tamat** (menggunakan `MotionSegmenter` selepas kiraan detik dan menyimpan vektor `146`-ciri ke `ROUTES.store`). Kekalkan input `cfg-seq-frames` sebagai had maksimum atau mod manual apabila `countdown === 0` / pilihan ditetapkan.
   - Tambah butang togol dwi-mod di kad **Live Model Tester** (`Open-Set` lalai vs `Simulasi Pelajar`) dan guna `dynamicEngine.evaluateClip(...)` + `MotionSegmenter` supaya pengalaman Studio selaras 1-ke-1 dengan skrin pelajar.

- [ ] **Step 4: Jalankan ujian penuh Laravel + Node JS untuk sahkan kesemua ujian lulus**

Run:
- `C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe artisan test`
- `node --test tests/js/*.test.mjs`  
Expected: Semua ujian PHPUnit dan Node test PASS tanpa amaran ralat sintaks atau kegagalan.

- [ ] **Step 5: Commit Task 5**

```bash
git add resources/views/learn/sign.blade.php resources/views/admin/ai-studio.blade.php tests/Feature/DynamicViewsSmokeTest.php
git commit -m "feat(ui): wire two-hand Pose state-machine and dual-mode tester into sign and AI studio views"
```

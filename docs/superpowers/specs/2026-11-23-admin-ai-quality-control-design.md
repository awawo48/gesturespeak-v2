# Reka Bentuk: Kawalan Kualiti AI — Pengurus Sampel Rosak & Pengesan Pasangan Tertukar

**Tarikh:** 2026-11-23  
**Projek:** Sign Cilik (`gesturespeak-v2`) — Portal Admin (`Studio Latihan AI`)  
**Pendekatan Dipilih:** Pendekatan B — Pengurus Sampel Rosak + Pengesan Pasangan Tertukar (*Confusion Analyzer*)

---

## 1. Tujuan & Masalah Yang Diselesaikan
Dalam sistem semasa, setiap rakaman dalam `GestureSample` terus disiarkan kepada pelajar melalui `/ai/dataset`. Apabila terdapat **sampel rosak** (tangan terkeluar bingkai, rangka kosong, atau gerakan tersilap) atau **pasangan isyarat yang sangat mirip** (cth: `A ↔ S`, `Terima Kasih ↔ Maaf`), ketepatan pengecaman Kamera AI merosot tanpa Admin tahu puncanya — dan Admin hanya boleh mengosongkan keseluruhan kelas (`clearSamples`), bukan membuang sampel rosak satu-persatu.

## 2. Matlamat, Bukan Matlamat & Anti-Matlamat
- **Matlamat:**
  1. **Tapis Sampel Rosak Semasa Rakam:** Kesan rangka tangan kosong/hilang sebelum disimpan dan beri amaran jika sampel terlalu jauh menyimpang daripada purata kelasnya.
  2. **Pengurus Sampel Per-Isyarat:** Papar senarai sampel individu (ID, jenis `Statik/DTW/Steps`, skor kualiti, bilangan bingkai/pose, tarikh) dengan butang **Padam** bagi setiap sampel tanpa mengganggu sampel yang elok.
  3. **Pengesan Pasangan Isyarat Tertukar (*Confusion Pair Analyzer*):** Analisis jarak ciri antara kelas dalam dataset sedia ada dan senaraikan pasangan yang paling tinggi risiko tertukar (%) bersama tindakan terus **`Semak Sampel →`** dan **`Rakam Semula →`**.
- **Bukan Matlamat:**
  - Tidak memerlukan pelayan GPU atau latihan PyTorch luaran (semua analisis dijalankan terus atas vektor `GestureSample` sedia ada).
  - Tidak menambah aliran *Draft / Publish Staging Gate* pada fasa ini.
- **Anti-Matlamat:**
  - Tidak melambatkan aliran rakaman pantas Admin di Studio Kamera AI.
  - Tidak memadam mana-mana sampel secara automatik tanpa pengesahan Admin.

## 3. Pendekatan Yang Dipertimbangkan
| Pendekatan | Ringkasan | Pertukaran (*Trade-off*) | Status |
|---|---|---|---|
| **A** | Penapis & pengurus sampel rosak sahaja | Ringkas, tetapi tidak nampak pasangan isyarat yang sering tertukar | Ditolak |
| **B** | **Pengurus sampel rosak + Pengesan pasangan tertukar** | **Menyelesaikan kedua-dua masalah teras tanpa pelayan tambahan atau aliran kerja berat** | **Dipilih** |
| **C** | Pendekatan B + *Staging Gate* (Draf/Terbit) | Paling selamat, tetapi menambah satu langkah penerbitan setiap kali rakam | Ditangguh |

## 4. Reka Bentuk Aliran Kerja & Antara Muka

### A. Tab / Skrin Baharu: **Diagnostik Kualiti AI** (di bawah Studio Latihan AI)
1. **3 Kad KPI Kualiti (Atas):**
   - **Kesihatan Dataset:** cth. `91%` (peratus sampel bersih).
   - **Sampel Bermasalah (*Outlier*):** cth. `7 sampel` (boleh klik untuk tapis terus).
   - **Pasangan Berisiko Tertukar:** cth. `3 pasangan` (pertindihan ≥ 60%).
2. **Jadual Pasangan Isyarat Tertukar (Kiri / Utama):**
   - Lajur: `Pasangan Isyarat` (cth. `Huruf A ↔ Huruf S`), `Mod` (`Statik KNN`), `Tahap Pertindihan` (bar 2-warna + `%`), `Punca Ringkas` (cth. `2 sampel outlier`), dan tindakan **`Semak Sampel →`** / **`Uji di Kamera →`**.
3. **Panel Pengurus Sampel Per-Isyarat (Kanan / Bawah):**
   - Pilih isyarat + togol penapis **`Semua` / `Bermasalah Sahaja`**.
   - Setiap baris memaparkan: `#ID`, `Jenis (Statik/DTW/Steps)`, `Skor Kualiti (%)`, `Bingkai/Pose`, lencana status (`Baik` / `Outlier` / `Rangka Tidak Lengkap`), dan butang **`Padam`**.

### B. Kawalan Kualiti Semasa Rakam (Studio Kamera AI)
- Kira skor kestabilan & liputan sendi tangan sebelum `POST /admin/ai-studio/sample`.
- Jika > 30% bingkai tiada tangan atau vektor kosong, halang simpan dan papar mesej jelas: *"Tangan tidak dikesan penuh — sila rakam semula"*.
- Jika sampel berjaya disimpan tetapi menyimpang jauh daripada sampel lain dalam kelas sama, simpan dengan bendera amaran supaya mudah disemak.

### C. Laluan Backend Laravel (Cadangan Pembina)
- `GET /admin/ai-studio/diagnostics` — imbas dataset dan pulangkan KPI, senarai pasangan bertindih, dan senarai ID sampel *outlier*.
- `GET /admin/ai-studio/samples/{label}` — senarai terperinci sampel bagi satu label dengan skor kualiti setiap sampel.
- `DELETE /admin/ai-studio/sample/{sample}` — padam satu sampel tertentu dan kemas kini kiraan dataset.

## 5. Skop Pelaksanaan (Cadangan)
1. **Reka bentuk FigCraft:** Tambah 1 artboard **Pixel v2 — Admin: Diagnostik Kualiti AI & Pengurus Sampel** dalam `F:\figcraft\sign cilik.figcraft` (sebaris dengan Papan Pemuka Studio AI).
2. **Implementasi Laravel (`gesturespeak-v2`):** Tambah endpoint diagnostik + padam sampel individu dalam `AiTrainingController.php` dan integrasikan UI ke `admin/ai-studio.blade.php`.

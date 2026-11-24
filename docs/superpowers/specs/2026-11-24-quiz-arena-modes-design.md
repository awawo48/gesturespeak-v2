# Reka Bentuk Pentas Kuiz Berlainan — 4 Mod Permainan (Sign Cilik Pixel v2)

**Tarikh:** 2026-11-24  
**Skop:** 4 skrin bermain kuiz (`/quiz/{category}?mode=...`) dalam FigCraft & Laravel `gesturespeak-v2`  
**Saiz Kerja:** Projek berfokus (1 skrin sedia ada direka semula + 3 skrin mod kuiz baharu di FigCraft; 2 mod baharu ditambah dalam `QuizController`)

---

## 1. Tujuan & Rasional (Intent & Why)
Skrin *Cabaran Kuiz Bintang* (`id: 2757`) sebelum ini berkongsi susun atur 2 lajur yang hampir sama dengan *Latihan Kamera AI* (`id: 2704`), menjadikan kuiz terasa seperti sambungan halaman belajar biasa. Untuk membina rasa teruja, fokus, dan ganjaran permainan bagi murid berumur 5–12 tahun, skrin kuiz perlu beralih sepenuhnya kepada **Pentas Permainan Terpusat** dengan **4 mod permainan berbeza** yang masing-masing mempunyai identiti warna dan mekanik tersendiri.

## 2. Matlamat, Bukan Matlamat & Anti-Matlamat
- **Matlamat:**
  - Memisahkan rasa UI Kuiz daripada UI Belajar (tiada bar navigasi biasa & tiada panel sisi besar — diganti dengan **HUD Permainan** di atas dan **Bar Maklum Balas Bawah**).
  - Menyediakan 4 mod kuiz yang jelas berbeza: **Pilihan Jawapan**, **Mengeja**, **Kuiz Kamera AI**, dan **Padan Pasangan**.
  - Menjadikan setiap mod mudah difahami kanak-kanak 5–12 tahun dalam satu pandang dan kekal terang/jelas di bilik darjah.
- **Bukan Matlamat:**
  - Tidak membuang *Lobi Pilih Kuiz* (`/quiz`) — lobi kekal sebagai tempat memilih modul, mod kuiz, dan Latihan Khas Guru.
  - Tidak memaksa pemasa kiraan undur (*countdown timer*) yang menekan kanak-kanak pendidikan khas.
- **Anti-Matlamat:**
  - Jangan gunakan sistem nyawa/hati (`♥`) yang menghukum kesilapan atau menendang murid keluar daripada kuiz — kekalkan ganjaran positif sahaja (Kombo, XP & Bintang) agar murid 5–12 tahun (termasuk PPKI) kekal seronok mencuba.
  - Jangan menyekat murid tanpa kamera web dalam *Kuiz Kamera AI* (mesti ada butang *"Langkau / Tukar Mod"* yang jelas).
  - Jangan menggunakan latar gelap penuh pada mod teks banyak (*Mengeja* & *Padan Pasangan*) supaya tidak meletihkan mata di bilik darjah terang.

## 3. Kekangan Sistem
- `QuizController.php` sedia ada sudah menyokong `choice` dan `spelling` dengan pemarkahan `QuizAttempt` (`score`, `total`, `stars`, `mode`).
- Mod `camera` boleh menggunakan enjin pengesanan AI pelayar sedia ada (`/ai/dataset` + MediaPipe) yang sama dengan *Latihan Kamera AI*.
- Mod `match` boleh dijana daripada 4 pasangan `Sign` rawak dalam kategori yang sama (tanpa jadual pangkalan data baharu; hanya perlu terima nilai `mode` baharu dalam pengesahan `submit`).

## 4. Pendekatan Yang Dipertimbangkan
| Pendekatan | Ringkasan | Kelebihan & Kekurangan | Keputusan |
|---|---|---|---|
| **1 — Pentas Terpusat (1 Tema)** | 1 pentas tengah dengan palet warna seragam untuk semua mod. | Paling mudah diselenggara, tetapi 4 mod terasa terlalu seragam. | Ditolak |
| **2 — Arena Arked Gelap** | Latar malam gelap penuh (`#0F172A`) untuk semua kuiz. | Kontras dramatik, tetapi kurang selesa di projektor/bilik darjah terang untuk mod ejaan & kad. | Ditolak |
| **3 — Pentas Terpusat + Tema Warna Unik Setiap Mod** | Struktur HUD permainan seragam + palet aksen & mekanik tersendiri bagi setiap satu daripada 4 mod. | Terasa 100% macam permainan berbanding halaman belajar, setiap mod unik, dan kekal jelas dibaca di kelas. | **Dipilih** |

## 5. Rangka Bersama Pentas Kuiz (Shared Game Shell)
Semua 4 mod kuiz berkongsi rangka permainan bersih (`max-width: 1040px` di tengah, latar krim `#FFFDF7` dengan aksen piksel halus):
- **Bar HUD Permainan Atas (`h: 76px`):**
  - Kiri: Butang piksel **`✕ Keluar`** + lencana nama mod & modul (cth. `Arena Bintang · Salam & Ucapan`).
  - Tengah: **Bar Kemajuan Segmen Piksel** (`Soalan 3 / 5`, segmen hijau/emas).
  - Kanan: **Pil Kombo (`3x Berturut`)** + **Pil XP Sesi (`★ +30 XP`)** — ganjaran positif tanpa nyawa hati.
- **Bar Maklum Balas Bawah (*Slide-up Feedback Bar*, `h: 108px`):**
  - Muncul apabila murid menjawab: hijau lembut (`#ECFDF5` / `#22C55E`) jika betul atau merah jambu lembut (`#FEF2F2` / `#EF4444`) jika salah.
  - Memaparkan **Kiko Mini** (`64×64`, *celebrate* atau *encourage*) + mesej galakan ringkas + butang 3D besar **`Soalan seterusnya →`**.

## 6. Spesifikasi 4 Mod Kuiz Berlainan

### Mod 1 — Pilihan Jawapan (*Arena Bintang*) · `?mode=choice`
- **Palet Aksen:** Biru Laut (`#132F4C`) + 4 Warna Arked 3D (Biru Langit `#38BDF8`, Hijau Zamrud `#22C55E`, Kuning Emas `#FFE14D`, Koral Lembut `#FB7185`).
- **Pentas Tengah:** Bingkai video/isyarat BIM gelap terpusat (`680×290`) dengan butang *Ulang tonton* & kelajuan `0.5x`.
- **Pilihan Jawapan:** Grid `2×2` butang 3D tebal bertepi bawah ala arked dengan lencana huruf piksel `A`, `B`, `C`, `D` dan status terpilih/tepat yang jelas.

### Mod 2 — Mengeja (*Bengkel Huruf*) · `?mode=spelling`
- **Palet Aksen:** Indigo Lembut (`#4F46E5` / `#EEF2FF`) + Krim Hangat.
- **Pentas Tengah:** Kad video isyarat padat di atas (`460×220`) bersama pembayang panjang perkataan (cth. *"4 huruf · Ucapan harian"*).
- **Slot Ejaan:** Barisan petak huruf piksel 3D (`M` · `A` · `A` · `_`) dengan slot aktif bercahaya indigo.
- **Papan Jubin Huruf:** Jubin huruf 3D rawak yang boleh ditekan (`A`, `M`, `F`, `A`, `T`, `S`, `I`) + butang **`⌫ Padam`** dan **`Semak Ejaan →`**.

### Mod 3 — Kuiz Kamera AI (*Pentas Aksi AI*) · `?mode=camera`
- **Palet Aksen:** Bingkai Pentas Gelap (`#0F2238`) + Sian Neon (`#38BDF8`) & Emas (`#FFE14D`).
- **Kad Sasaran Atas:** Sepanduk besar jelas: **`Tunjukkan isyarat: TERIMA KASIH`** (dengan pilihan *"Tunjuk pembayang"* jika murid lupa).
- **Pentas Kamera Lebar:** Paparan kamera AI terpusat (`760×380`) dengan rangka tangan *MediaPipe*, penunjuk tahan-kunci (`Tahan 1.5 saat...`), dan **Meter Ketepatan AI Langsung (`89%`)** di tepi.
- **Kawalan Bawah:** Butang **`Langkau soalan`** (untuk murid tanpa kamera) & status sambungan kamera.

### Mod 4 — Padan Pasangan (*Meja Kad Pasangan*) · `?mode=match`
- **Palet Aksen:** Hijau Pudina (`#10B981` / `#ECFDF5`) + Biru Langit untuk kad terpilih.
- **Pentas Tengah:** 2 lajur simetri:
  - **Lajur Kiri (4 Kad Isyarat BIM):** Pratonton ikon/isyarat BIM + nombor piksel `1–4`.
  - **Lajur Kanan (4 Kad Perkataan Melayu):** Perkataan besar jelas (`Assalamualaikum`, `Terima Kasih`, `Selamat Pagi`, `Maaf`).
- **3 Keadaan Kad:** Biasa (putih berbayang 3D), Sedang Dipilih (gelang biru `#38BDF8` + terangkat), Berjaya Dipadankan (hijau pudar + lencana `✓`).

## 7. Pelan Susunan FigCraft (`Baris 2 — Peranan Murid`)
- Kemas kini artboard `id: 2757` menjadi **Kuiz 1: Pilihan Jawapan (Arena Bintang)**.
- Tambah 3 artboard baharu terus di sebelahnya dalam `Baris 2 — Peranan Murid`:
  - **Kuiz 2: Mengeja (Bengkel Huruf)**
  - **Kuiz 3: Kamera AI (Pentas Aksi AI)**
  - **Kuiz 4: Padan Pasangan (Meja Kad Pasangan)**
- Alihkan `id: 3616` (*Profil Murid & Pilih Watak*) ke hujung kanan Baris 2 dan kemas kini tajuk seksyen Baris 2–5.

## 8. Diserahkan Kepada Pembina (Builder Calls)
- Animasi mikro (konfeti piksel bila kombo ≥ 3, goncangan lembut kad salah, patuh `prefers-reduced-motion`).
- Integrasi `mode=camera` dan `mode=match` dalam `QuizController` & `quiz/play.blade.php` semasa fasa kod Laravel nanti.

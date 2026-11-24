# Reka Bentuk Laluan Langkah Demi Langkah Gaya Duolingo (Senarai Isyarat Modul)

**Tarikh:** 2026-11-24  
**Skop:** Skrin Murid — `Senarai Isyarat Modul` (`/learn/category/{category}` / FigCraft Artboard `id: 4143`)  
**Saiz Kerja:** Perubahan kecil & berfokus (1 skrin UI + logik kunci urutan dalam `LearningController`, tanpa migrasi pangkalan data)

---

## 1. Tujuan & Rasional (Intent & Why)
Murid berusia 5–12 tahun mudah keliru apabila melihat grid 7 hingga 26 kad isyarat serentak. Menukar paparan *Senarai Isyarat Modul* kepada **laluan langkah demi langkah berkunci gaya Duolingo** menjadikan sasaran seterusnya serta-merta jelas, mengurangkan beban keputusan (*decision fatigue*), dan memberi rasa pencapaian setiap kali mangga isyarat baharu terbuka.

## 2. Matlamat, Bukan Matlamat & Anti-Matlamat
- **Matlamat:**
  - Setiap isyarat dalam modul dipaparkan sebagai 1 nod bulat piksel 3D di sepanjang laluan berliku menegak.
  - Urutan dikunci ketat: nod `N` hanya terbuka selepas nod `1..N-1` siap (`is_done`).
  - Di penghujung laluan, terdapat nod **Piala Kuiz Modul** yang terbuka apabila semua isyarat modul disiapkan.
  - Kekalkan estetika **Sign Cilik Pixel v2** (`#FFFDF7`, `#132F4C`, `#FFE14D`, `#22C55E`, `CutePixel` + `Nunito`, maskot **Kiko**).
- **Bukan Matlamat:**
  - Tidak mengunci 6 modul di *Pusat Belajar Murid* (`/learn`) — murid masih bebas memilih mana-mana modul utama.
  - Tidak mengubah aliran 1 isyarat = 1 sesi Kamera AI (`/learn/category/{category}/{sign}`).
  - Tidak menyekat **Latihan Khas Guru** di *Lobi Pilih Kuiz*.
- **Anti-Matlamat:**
  - Jangan menyekat murid daripada mengulang kaji isyarat yang sudah selesai (`✓`).
  - Jangan membiarkan murid tersekat selamanya pada isyarat dinamik (kekalkan logik `is_done` sedia ada: `mastered` atau `viewed_at` bagi isyarat dinamik).

## 3. Kekangan Sistem
- `LearningController@category` sudah memuatkan isyarat mengikut `sort_order` beserta status `is_done`, `mastered`, dan `best_confidence`.
- Saiz modul berbeza-beza: 7 isyarat (*Salam & Ucapan*, *Keluarga*, *Perasaan*, *Soalan*), 11 isyarat (*Nombor 0–10*), dan 26 isyarat (*Huruf A–Z*).
- Tiada perubahan skema pangkalan data diperlukan — status `completed`, `current`, atau `locked` dikira terus daripada jujukan `is_done` mengikut `sort_order`.

## 4. Pendekatan Yang Dipertimbangkan
| Pendekatan | Ringkasan | Kelebihan & Kekurangan | Keputusan |
|---|---|---|---|
| **A — Laluan Berliku Menegak (1 Nod = 1 Isyarat)** | Nod bulat 3D berliku kiri-kanan ke bawah + Kiko di tepi nod aktif + Piala Kuiz di hujung. Modul panjang dibahagi kepada bahagian kecil (*Bahagian 1: A–E*...). | Paling hampir dengan rasa Duolingo, kemas di desktop & mobile, 100% serasi dengan Laravel sedia ada. Modul 26 huruf perlu skrol (dibantu auto-skrol ke nod aktif). | **Dipilih** |
| **B — Peta Ular GBA Mendatar** | Susunan melintang kiri→kanan→turun→kanan→kiri. | Kurang skrol di desktop, tetapi sempit di telefon dan kurang ruang untuk Kiko & label isyarat. | Ditolak |
| **C — Nod Berkelompok (3 Isyarat / Nod)** | 1 nod menggabungkan 3 isyarat berturut-turut. | Memendekkan *Huruf A–Z*, tetapi memerlukan aliran sesi baharu dan terlalu berat untuk murid 5–7 tahun. | Ditolak |

## 5. Spesifikasi Reka Bentuk (Pendekatan A)

### A. Susun Atur Desktop (`1440px`, 2 Lajur Seimbang)
- **Bar Navigasi Atas (Sedia Ada):** Logo `SignCilik` · `← Kembali ke Pusat Belajar` · Pil Avatar Murid (`ADAM-1234`) · Pil `120 XP`.
- **Lajur Kiri / Utama (Laluan Duolingo, ~840px):**
  - **Sepanduk Unit / Modul (`#132F4C`):** Ikon modul piksel, tajuk *Salam & Ucapan*, sub-label *Unit 1 · 7 isyarat ucapan harian*, dan bar kemajuan (`5 daripada 7 isyarat dikuasai · 71%`).
  - **Laluan Berliku Menegak:** Garis/jejak piksel putus-putus menyambungkan nod kiri-tengah-kanan secara berirama (offset X berayun halus ±90px dari paksi tengah).
  - **3 Keadaan Nod Bulat Piksel 3D (dengan bayang bawah tebal gaya Duolingo):**
    1. **Selesai (`completed`):** Bulatan hijau (`#22C55E`, tepi bawah `#15803D`) + ikon `✓` putih + label nama isyarat + ketepatan terbaik (cth. *Assalamualaikum · 97%*). Boleh ditekan untuk ulang kaji.
    2. **Aktif Semasa (`current`):** Bulatan kuning emas (`#FFE14D`, tepi bawah `#D97706`) dengan gelang fokus biru muda (`#38BDF8`) + pil terapung **"MULA! ★"** di atas nod + label nama isyarat & ganjaran (`+10 XP`). **Maskot Kiko** berdiri di sisi bertentangan nod aktif sambil memberi kata semangat ringkas.
    3. **Terkunci (`locked`):** Bulatan kelabu lembut (`#E2E8F0`, tepi bawah `#CBD5E1`) + ikon mangga (`🔒`) + nama isyarat pudar (`#94A3B8`). Tidak boleh ditekan (tooltip: *"Siapkan isyarat sebelumnya dahulu"*).
  - **Nod Penamat — Piala Kuiz Modul (`🏆`):**
    - Terkunci (kelabu) selagi belum semua isyarat modul siap; bertukar emas berkilau apabila 100% isyarat siap dan membawa terus ke `/quiz/{category}`.
  - **Pemisah Bahagian (Untuk Modul > 10 Isyarat seperti *Huruf A–Z*):**
    - Setiap 5–6 isyarat dipisahkan dengan garis label mendatar ringkas (*Bahagian 1: A–E*, *Bahagian 2: F–J*, dll.).
- **Lajur Kanan (Panel Sisi Ringkas, ~380px, *sticky* di web):**
  - **Kad Fokus Semasa:** Memaparkan butiran nod aktif (cth. *Terima Kasih · Dinamik 4 langkah*), ganjaran `+10 XP`, dan butang besar **"Berlatih sekarang →"**.
  - **Kad Peti Ganjaran Modul:** Ringkasan kemajuan bintang & status buka kunci *Kuiz Modul* (cth. *"Lagi 2 isyarat untuk buka Kuiz Modul!"*).

### B. Peraturan Kunci di Laravel (`LearningController`)
- Urutan isyarat sentiasa disusun dengan `orderBy('sort_order')`.
- Nod pertama yang `is_done == false` ditetapkan sebagai `current`; semua sebelumnya ialah `completed`; semua selepasnya ialah `locked`.
- Jika murid cuba mengakses terus URL `/learn/category/{category}/{sign}` bagi isyarat berstatus `locked`, controller akan `redirect` kembali ke `learn.category` dengan mesej mesra (*"Siapkan isyarat sebelumnya dahulu untuk buka langkah ini!"*).

## 6. Diserahkan Kepada Pembina (Builder Calls)
- Animasi mikro di web (cth. lonjakan halus pil *"MULA!"* dan kesan tekan 3D nod mengikut `prefers-reduced-motion`).
- Auto-skrol halus ke nod `current` apabila halaman dibuka untuk modul panjang seperti *Huruf A–Z*.
- Pengemaskinian artboard FigCraft `id: 4143` (*Sign Cilik (Pixel v2) — Senarai Isyarat Modul*) supaya menjadi rujukan visual rasmi sebelum kod Laravel ditulis.

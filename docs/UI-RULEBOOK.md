# Sign Cilik — Buku Panduan & Peraturan UI (`UI Rulebook v2 — Pixel GBA`)

Dokumen ini ialah rujukan rasmi (*single source of truth*) untuk reka bentuk antara muka pengguna (**UI**), sistem aset **Pixel Art (GBA/SNES)**, token CSS, kebolehcapaian (**WCAG AA**), interaksi, dan peraturan pengkodan Blade bagi aplikasi **Sign Cilik** (`gesturespeak-v2`).

Rujukan visual induk disimpan di **FigCraft** (`F:\figcraft\sign cilik.figcraft` — artboard **`Sign Cilik (Pixel v2)`**) dan fail vektor induk disimpan di **VectorCraft** (`public/media/brand/` & `public/media/icons/`).

Setiap perubahan atau pembinaan paparan baharu (`home`, `auth/*`, `learn/*`, `quiz/*`, `guardian/*`, `admin/*`) wajib mematuhi garis panduan di bawah.

---

## 1. Identiti Jenama, Arah Visual & Mod Paparan

- **Nama produk rasmi:** Gunakan **Sign Cilik** (`SignCilik`) pada semua `<title>`, kepala halaman, footer, dan salinan UI. Jangan gunakan nama legasi *GestureSpeak* pada antara muka pengguna.
- **Wording Logo Kekal:** Teks logo dua warna pixel asal **SignCilik** (`Sign` `#16324f` + `Cilik` `#26b0e5` daripada [signcilik-logo.svg](file:///f:/gesture%20speak/gesturespeak-v2/public/media/brand/signcilik-logo.svg)) kekal digunakan tanpa diubah suai, dipadankan dengan ikon baharu **Kiko Cartridge Tile** ([signcilik-icon.svg](file:///f:/gesture%20speak/gesturespeak-v2/public/media/brand/signcilik-icon.svg)).
- **Arah Visual (`Pixel v2`):**
  - **Watak:** Ceria, taktil, tenang dibaca.
  - **Dunia visual:** Kartrid permainan tangan retro (GBA/SNES) di atas kanvas krim hangat (`#fffdf7`).
  - **Fokus (*Moments*):** Panel Hero Kiko Holo-Pod & 1 kad tugasan aktif sahaja yang menonjol; elemen krom lain kekal senyap (*Quiet Chrome*).
  - **Tanda khas (*Signature*):** Sempadan pixel navy `3px` + bayang tekan taktil khusus untuk elemen aktif dan CTA utama.
- **Tiga mod antara muka mengikut pengguna:**
  1. **Mod Awam & Pintu Masuk (`home`, `auth/*`):** Kanvas terang dan tenang, susun atur lapang tanpa lambakan lencana bertindan, dengan **Konsol Kamera AI** atau **Kad Pelajar** sebagai fokus visual tunggal.
  2. **Mod Pembelajaran Kanak-Kanak (`learn/*`, `quiz/*`):** Ceria, mesra sentuhan (`>= 54px` untuk butang utama kanak-kanak), paparan angka ganjaran jelas, dan maskot bimbingan **Kiko Pixel** dibenarkan aktif.
  3. **Mod Portal Dewasa (`guardian/*`, `admin/*`):** Padat data, berfokus tugasan, hierarki kad sekata, tanpa dekorasi kartun berlebihan atau pop-up Kiko.
- **Peraturan Maskot Kiko Pixel (`partials/kiko-mascot.blade.php`):**
  - Maskot rasmi ialah **Kiko Pixel Art (48×44 GBA/SNES)** — robot kucing Holo-Pod menghadap kiri (kepala *squircle* `#ffffff`, visor & garis `#16324f`, badan/telinga `#58caf3`, antena bintang & bola ekor `#ffe942`, pancaran *dither* hologram `#d8f4fe`, pipi `#ff8fa3`).
  - Fail induk VectorCraft: [kiko-mascot-pixel.vectorcraft](file:///f:/gesture%20speak/gesturespeak-v2/public/media/brand/kiko-mascot-pixel.vectorcraft) (1 artboard, 6 reaksi berasingan).
  - 6 fail SVG sedia web (`public/media/brand/mascot/`): `kiko-pixel-happy.svg` (ILY 🤟), `kiko-pixel-celebrate.svg` (bintang emas), `kiko-pixel-encourage.svg` (hati merah jambu), `kiko-pixel-coach.svg` (tangan terbuka ✋ + garis imbas AI), `kiko-pixel-poke.svg` (terkejut `!`), `kiko-pixel-speaking.svg` (pembesar suara & gelombang).
  - Kekalkan API JS `window.Kiko` dan hanya muatkan pada laluan `learn.*` dan `quiz.*` melalui [layouts/app.blade.php](file:///f:/gesture%20speak/gesturespeak-v2/resources/views/layouts/app.blade.php). **Dilarang** pada `/`, `/student/login`, `/login`, `/register`, atau portal dewasa.

---

## 2. Sistem Warna, Kontras (`WCAG AA`) & *Quiet Chrome*

Semua warna teras ditakrifkan di `:root` dalam [public/css/app.css](file:///f:/gesture%20speak/gesturespeak-v2/public/css/app.css). Jangan tulis kod warna mentah berulang secara *inline* apabila token sudah wujud.

| Token CSS | Nilai | Peranan Utama | Syarat Penggunaan & Kontras |
| :--- | :--- | :--- | :--- |
| `--primary` | `#16324f` | Teks utama, butang utama, panel Hero HUD, garis pixel | Kontras tinggi (> 12:1) di atas putih & `#fffdf7` |
| `--primary-dark` | `#0e2238` | Pentas kamera AI, `.site-footer`, bayang visor/border | Untuk latar gelap berfasa telemetri |
| `--secondary-text` | `#166891` | **WAJIB** untuk teks kecil biru, peratus aktif, label meta, & CTA biru | **Lulus WCAG AA (≥ 5.9:1)** di atas putih/krim & dengan teks putih |
| `--secondary` | `#26b0e5` | Perkataan *Cilik* pada logo & aksen grafik | **Jangan** guna untuk teks kecil `< 18px` di atas putih (~3.2:1) |
| `--secondary-light` | `#58caf3` | Bar kemajuan aktif, sudut pemidang tilik, aksen atas latar gelap | Kontras tinggi (6.9:1) di atas `--primary` / `--primary-dark` |
| `--accent` | `#ffe942` | Butang CTA kuning, lencana XP bintang, sorotan tugasan | Mesti dipadankan dengan teks `--primary` (`#16324f`) |
| `--pixel-green` | `#28c76f` | Bar kemajuan selesai `100%` & status padanan tepat | Untuk grafik/bar; bukan teks kecil di atas putih |
| `--success-text` | `#047857` | **WAJIB** untuk teks status selesai (`100%`, `Selesai 100%`) | **Lulus WCAG AA (≥ 5.4:1)** di atas putih/tint hijau |
| `--danger` | `#d63031` | Mesej ralat pengesahan & amaran tindakan | Sertakan teks/ikon jelas; jangan bergantung pada warna sahaja |
| `--bg-gradient` | `#fffdf7` | Latar kanvas utama aplikasi (krim hangat) | Boleh disertai corak titik halus `rgba(22, 50, 79, 0.055)` |
| `--bg-card` | `#ffffff` | Permukaan kad kandungan & borang | Kekal senyap supaya ikon pixel & teks menonjol |
| `--text-muted` | `#475569` / `#5b7590` | Subtajuk, kapsyen, petunjuk input | Pastikan kontras ≥ 4.5:1 di atas putih |
| `--border-quiet` | `#cbd9e8` | Sempadan `2px` untuk kad tidak aktif, trek bar, & lencana pasif | Mengurangkan kebisingan visual (*Quiet Chrome*) |

### Peraturan Warna Bar Kemajuan & Anti-Slop
- **2 warna semantik bar kemajuan sahaja:** Gunakan biru `#58caf3` untuk semua modul sedang belajar dan hijau `#28c76f` khusus untuk modul selesai `100%`. Elakkan bar pelangi (4–5 warna berbeza pada satu grid kad).
- **Hierarki *Quiet Chrome*:** Hanya **Hero HUD** dan **1 kad tugasan aktif** (cth. *Tugasan Cikgu*) menerima sempadan tebal `3px` (`#166891`/`#16324f`) + *offset shadow*. Kad modul lain dan kad aksi bawah menggunakan sempadan senyap `2px solid #cbd9e8` tanpa bayang berat (hanya timbul halus ketika `:hover`/`:focus-visible`).
- **Larangan Warna (*Anti-Slop*):** Dilarang menggunakan ungu generik (`#6c5ce7`, `#8b5cf6`), kecerunan neon pelangi pada teks, cahaya *glow* hiasan, atau warna berbeza pada keempat-empat sudut bingkai kamera.
- **Sokongan Forced Colors Mode:** Jangan bergantung pada `box-shadow` semata-mata untuk sempadan kad atau fokus kerana pelayar membuang `box-shadow` dalam Windows High Contrast Mode — kekalkan `border` sebenar dan `outline` untuk `:focus-visible`.

---

## 3. Tipografi (`CutePixel` + `Nunito`) & Salinan Tenang

Sign Cilik Pixel v2 menggabungkan **dua keluarga fon utama** (dengan `Fredoka` sebagai sokongan legasi jika perlu):

| Fon | Token CSS | Kegunaan Sah (Pixel v2) |
| :--- | :--- | :--- |
| **CutePixel** | `--font-pixel` | Tajuk paparan (`<h1>`, `<h2>`, tajuk kad modul & kad aksi pantas), angka ganjaran & peratus (`120 XP`, `76%`, `71%`), kod murid (`ADAM-1234`), slot PIN, & telemetri kamera (`30 FPS`) |
| **Nunito** | `--font-main` | Semua perenggan penerangan, subtajuk kad, label navigasi, teks butang, lencana status, & arahan borang (`400`, `600`, `700`; elak `900`) |

### Peraturan Tipografi & Salinan (*Copy*)
1. **Had `CutePixel`:** Jangan gunakan `CutePixel` untuk perenggan panjang atau arahan borang demi kebolehbacaan kanak-kanak dan pengguna skrin kecil. Sentiasa gunakan `font-variant-numeric: tabular-nums` pada angka XP/peratus.
2. **Skala Responsif:** Gunakan `clamp(min, fluid, max)` dengan syarat **`max <= 2.5 * min`** (WCAG 1.4.4 supaya zum 200% tidak terhalang) dan hadkan lebar perenggan kepada `50ch`–`62ch`.
3. **Sentence Case & Salinan Tenang (*Unslop*):**
   - Guna *sentence case* pada semua *eyebrow*, meta, dan lencana (`Misi minggu ini`, `Penguasaan BIM keseluruhan`, `Kiko · Rakan BIM`, `6 modul aktif · 63 isyarat`, `Tugasan cikgu`, `Hampir siap`) — **elakkan** `ALL CAPS` berjarak lebar (*wide-tracked uppercase*).
   - **Elakkan** tanda seru (`!`) pada tajuk/kapsyen UI dan **elakkan** noktah hujung pada label pendek/butang.
   - Guna anak panah Unicode sebenar **`→`** (bukan `->`) dan label butang ringkas (`Sambung modul →`, `Ulang kaji →`, `Mula modul →`).

---

## 4. Jarak, Grid, Jejari Pixel & Struktur Kad Responsif

- **Skala Jarak `4px`:** Semua `padding`, `margin`, dan `gap` mesti gandaan `4px` (`4, 8, 12, 16, 20, 24, 32, 48px`). Utamakan sifat logik CSS (`margin-inline`, `padding-inline`, `min-block-size`, `min-inline-size`).
- **Jejari Sudut Pixel v2 (*Nested Radii*):**
  - Panel Hero & kad modul / kad aksi bawah: `10px`
  - Jubin ikon pixel dalam kad: `8px` (`10px - padding` atau `6px`–`8px` supaya kekal bersegi retro)
  - Butang CTA, tab navigasi, & pil status HUD: `6px`
  - Lencana status kecil & trek bar kemajuan: `4px` (isi bar: `2px`)
- **Struktur Kad Moden (`modern-web-guidance: card`):**
  - Gunakan `<article>` sebagai akar setiap kad modul.
  - Jadikan setiap kad sebagai bekas saiz (`container-type: inline-size`) dan guna `@container` supaya susun atur menyesuaikan lebar kad (3 lajur desktop → 2 tablet → 1 telefon).
  - Gunakan `display: grid; grid-template-rows: subgrid` apabila kad dalam satu baris perlu menyelaraskan tajuk, bar kemajuan, dan butang CTA.
  - Untuk kad boleh klik penuh, guna corak *stretched link* (`a.card-link::after { content: ""; position: absolute; inset: 0; }`) pada pautan tajuk kad dan elakkan menyarangkan `<button>` di dalam `<a>`.

---

## 5. Aset Jenama, Pek 19 Ikon Pixel & Fail Master

Semua aset pixel SVG wajib menyertakan `shape-rendering="crispEdges"` dan dipaparkan di CSS dengan `image-rendering: pixelated` supaya bucu piksel kekal tajam.

### Aset Jenama & Web/PWA (`public/media/brand/` & `public/`)
- [signcilik-icon.svg](file:///f:/gesture%20speak/gesturespeak-v2/public/media/brand/signcilik-icon.svg) & [logo-square.svg](file:///f:/gesture%20speak/gesturespeak-v2/public/media/brand/logo-square.svg) — Ikon aplikasi **Kiko Cartridge Tile (32×32 Pixel Art)**; fail induk: [signcilik-icon-pixel.vectorcraft](file:///f:/gesture%20speak/gesturespeak-v2/public/media/brand/signcilik-icon-pixel.vectorcraft).
- Set ikon web/PWA lengkap di `public/`: `favicon.ico` (16/32/48px), `favicon-16x16.png`, `favicon-32x32.png`, `apple-touch-icon.png` (180px), `icon-192.png`, `icon-512.png`, dan `site.webmanifest`.
- [signcilik-logo.svg](file:///f:/gesture%20speak/gesturespeak-v2/public/media/brand/signcilik-logo.svg) & [signcilik-logo-dark.svg](file:///f:/gesture%20speak/gesturespeak-v2/public/media/brand/signcilik-logo-dark.svg) — Logo penuh (kekalkan teks pixel asal **SignCilik**).

### Komponen Ikon `<x-sc-icon>` & `<x-sc-category-icon>` (19 Ikon Pixel Rasmi)
Fail induk VectorCraft (1 artboard 19 ikon): [signcilik-icon-pack-pixel.vectorcraft](file:///f:/gesture%20speak/gesturespeak-v2/public/media/icons/signcilik-icon-pack-pixel.vectorcraft).

- **Senarai 19 ikon SVG pixel rasmi (grid 24×24, `192×192`, + `mascot`):**
  `abjad-nombor`, `bintang-kuiz`, `buku`, `guru`, `haiwan`, `ibu-bapa`, `kamera-ai`, `keluarga`, `keluarga-guru`, `kod-pin`, `nombor`, `pelajar`, `pelajar-cemerlang`, `perasaan`, `robot-ai`, `soalan`, `suara-bm`, `trofi`, `ucapan`.
- **Ikon tanpa muka:** `buku`, `kod-pin`, `keluarga`, `trofi`, dan `ucapan` kekal bersih tanpa muka (ikon `ucapan` memaparkan garis teks putih di dalam gelembung biru).
- **Pemetaan kategori ([sc-category-icon.blade.php](file:///f:/gesture%20speak/gesturespeak-v2/resources/views/components/sc-category-icon.blade.php)):** `huruf/abjad` → `abjad-nombor`, `nombor` → `nombor`, `salam/ucapan` → `ucapan`, `keluarga` → `keluarga`, `haiwan` → `haiwan`, `perasaan` → `perasaan`, `soalan` → `soalan`, lain-lain → `buku`.
- **Ikon hiasan & Larangan Emoji:** Ikon di sebelah label teks mesti `alt=""` (lalai pada `<x-sc-icon>`, menambah `aria-hidden="true"`). **Dilarang** meletakkan emoji UI (`🏫`, `📊`, `🏆`, `⭐`) dalam tajuk kad, butang, atau lencana — sentiasa guna `<x-sc-icon>`.

---

## 6. Struktur Halaman & Komponen Utama

1. **Header & Footer (`layouts/app.blade.php`):**
   - Header lekat penuh (`position: sticky; top: 0; width: 100%`) dengan latar `#ffffff` / `rgba(255, 255, 255, 0.96)` dan `border-bottom: 3px solid var(--primary)` (mod pixel murid) atau `1px solid rgba(22, 50, 79, 0.08)` (mod awam/dewasa).
   - Sediakan skip-link (`Langkau ke kandungan utama`) ke `<main id="main-content" class="page-container" tabindex="-1">`.
   - Setiap `<nav>` mempunyai `aria-label` unik tanpa perkataan *"navigasi"* (cth. `aria-label="Utama"`, `aria-label="Dunia permainan"`).
2. **Hero & Seksyen Halaman Utama (`home.blade.php`):**
   - Maksimum **1 butang utama + 1 butang sekunder** dalam `.hero-cta-row`, menuju destinasi berbeza.
   - Jangan gabung `display: flex` pada `<li>` yang mempunyai tag sebaris (`<strong>` atau `<code>`) tanpa pembalut teks.
3. **Halaman Log Masuk (`student-login.blade.php`, `login.blade.php`, `register.blade.php`):**
   - Susun atur 2 lajur `.auth-split` (`minmax(0, 480px) minmax(0, 1fr)`).
   - Letakkan teks bantuan (`aria-describedby`) **di atas** kotak input supaya *autocomplete* pelayar tidak menutupinya.
   - Tetapkan `autocomplete`, `<label for="id">`, dan `enterkeyhint` pada setiap input.

---

## 7. Interaksi (`emil-design-eng`), Responsif & Kebolehcapaian (`WCAG AA`)

- **Interaksi & Gerakan Taktil:**
  - Gunakan lengkung `--ease-out: cubic-bezier(0.23, 1, 0.32, 1)` dengan tempoh pantas **`140ms`–`220ms`** (jangan melebihi `300ms` untuk elemen UI).
  - **Jangan guna `transition: all`:** Nyatakan sifat khusus sahaja (`transition: transform 160ms var(--ease-out), background-color 160ms ease, border-color 160ms ease`).
  - **Maklum balas tekan (`:active`):** Setiap butang pixel wajib memberi rasa ditekan: `transform: translateY(3px)` (dengan pengurangan *offset shadow*) atau `transform: scale(0.97)` dalam `160ms var(--ease-out)`.
  - **Hover khusus tetikus:** Balut kesan `:hover` dalam `@media (hover: hover) and (pointer: fine)` supaya tablet/telefon tidak mengalami *sticky hover*.
  - **Popover & Gelembung Kiko:** Tetapkan `transform-origin: bottom right` (berpaut pada Kiko) dan animasikan masuk dengan `@starting-style` dari `transform: scale(0.95); opacity: 0` — **jangan** mula dari `scale(0)`.
  - **Kemunculan senarai kad:** Jika menganimasikan kad masuk, guna satu *fade + rise* `8px` (`220ms var(--ease-out)`) dengan *stagger* pendek `40ms` dan hormati `@media (prefers-reduced-motion: reduce)`.
- **Responsif & Limpahan Skrin:**
  - `body` menggunakan `min-height: 100dvh` dan `overflow-x: clip`; semua anak grid/flex menetapkan `min-width: 0`.
  - Tiada limpahan mendatar pada lebar `390px` (telefon) dan `1440px` (desktop): `scrollWidth === clientWidth`.
- **Kebolehcapaian Wajib:**
  - `:focus-visible` mesti mempunyai `outline: 3px solid var(--primary); outline-offset: 3px;` dengan sokongan `@media (forced-colors: active)`.
  - **Nama aksesibel unik untuk CTA berulang:** Jangan biarkan beberapa pautan/butang membaca `"Mula modul"` yang sama pada pembaca skrin — tambah teks tersembunyi visual (`Mula modul <span class="visually-hidden">Nombor 0–10</span>`).
  - Sasaran sentuhan minimum `44×44px` (`@media (pointer: coarse)`), `48px` (`--touch-min`) untuk butang umum, dan `54px` (`--touch-kid`) untuk CTA murid.
  - Satu `<h1>` sahaja per halaman dan turutan tajuk `<h1>` → `<h2>` → `<h3>` tanpa melangkau aras.
  - Utamakan `<dialog>` natif (`.showModal()`) untuk sebarang modal baharu.

---

## 8. Senarai Semak Pra-Siap (*Pre-Ship Checklist*)

Sebelum menamatkan sebarang kerja UI baharu atau penyelarasan halaman (`learn/*`, `quiz/*`, `guardian/*`, `admin/*`):

1. **Jenama & Aset Pixel:** Tiada nama *GestureSpeak*, tiada ungu generik `#6c5ce7`/`#8b5cf6`, tiada emoji UI, semua ikon menggunakan 19 ikon rasmi `<x-sc-icon>` (`image-rendering: pixelated`), wording logo `SignCilik` asal kekal.
2. **Kontras & Hierarki:** Semua teks kecil biru/hijau memakai `#166891` / `#047857` (≥ 4.5:1), hanya Hero & kad aktif menonjol (*Quiet Chrome*), bar kemajuan guna 2 warna semantik sahaja (`#58caf3` / `#28c76f`).
3. **Tipografi & Salinan:** `CutePixel` untuk tajuk/angka HUD/kod, `Nunito` untuk teks huraian/butang, *sentence case* pada label meta, tiada `!` atau noktah hujung pada label pendek, anak panah guna `→`.
4. **Interaksi & Aksesibiliti:** Tiada `transition: all`, ada `:active` pada butang, hover dalam `@media (hover: hover)`, nama aksesibel unik pada CTA berulang, `:focus-visible` jelas, dan `scrollWidth === clientWidth` pada `390px` & `1440px`.
5. **Jalankan Verifikasi:**
   - `php artisan view:cache` & `php artisan view:clear`
   - `php artisan test`
   - `node --test tests/js/*.test.mjs` (jika menyentuh JS kamera/kuiz)

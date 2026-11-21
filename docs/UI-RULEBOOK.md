# Sign Cilik — Buku Panduan & Peraturan UI (`UI Rulebook`)

Dokumen ini ialah rujukan rasmi (*single source of truth*) untuk reka bentuk antara muka pengguna (**UI**), sistem token CSS, kebolehcapaian (**WCAG AA**), dan peraturan pengkodan Blade bagi aplikasi **Sign Cilik** (`gesturespeak-v2`).

Setiap perubahan atau pembinaan paparan baharu (`home`, `auth/*`, `learn/*`, `quiz/*`, `guardian/*`, `admin/*`) wajib mematuhi garis panduan di bawah.

---

## 1. Identiti Jenama & Mod Paparan

- **Nama produk rasmi:** Gunakan **Sign Cilik** (`SignCilik`) pada semua `<title>`, kepala halaman, footer, dan salinan UI. Jangan gunakan nama legasi *GestureSpeak* pada antara muka pengguna.
- **Tiga mod antara muka mengikut pengguna:**
  1. **Mod Awam & Pintu Masuk (`home`, `auth/*`):** Kanvas terang dan tenang, susun atur lapang tanpa lambakan lencana bertindan, dengan **Konsol Kamera AI** atau **Kad Pelajar** sebagai fokus visual tunggal.
  2. **Mod Pembelajaran Kanak-Kanak (`learn/*`, `quiz/*`):** Ceria, mesra sentuhan (`>= 54px` untuk butang utama kanak-kanak), paparan angka ganjaran jelas, dan maskot bimbingan **Kiko** dibenarkan aktif.
  3. **Mod Portal Dewasa (`guardian/*`, `admin/*`):** Padat data, berfokus tugasan, hierarki kad sekata, tanpa dekorasi kartun berlebihan atau pop-up Kiko.
- **Peraturan Maskot Kiko (`partials/kiko-mascot.blade.php`):**
  - Kiko v2 ialah **robot kucing Holo-Pod menghadap kiri** (kepala *squircle* `#ffffff`, visor & garis `#16324f`, badan/telinga `#58caf3`, antena bintang & isyarat `#ffe942`, cahaya hologram `#d8f4fe`, pipi `#ff8fa3`) — memegang peranti Holo-Pod yang memancarkan kad isyarat tangan ke arah kiri supaya sesuai di sudut kanan bawah skrin. Jangan kembalikan kucing oren lama atau warna ungu.
  - Reaksi muka & kad hologram berubah dinamik melalui `data-mood` (`happy`: ILY 🤟, `celebrate`: Thumbs-Up 👍, `encourage`: hati, `coach`: imbas tapak tangan ✋) + kelas `is-speaking` (mulut bercakap & gelombang suara) dan `is-poked` (terkejut `!`). Kekalkan API `window.Kiko`.
  - Hanya dimuatkan pada laluan `learn.*` dan `quiz.*` melalui [layouts/app.blade.php](file:///f:/gesture%20speak/gesturespeak-v2/resources/views/layouts/app.blade.php).
  - **Dilarang** dipaparkan pada halaman utama (`/`), log masuk (`/student/login`, `/login`), pendaftaran (`/register`), atau portal dewasa.

---

## 2. Sistem Warna & Kontras (`Color & Contrast Tokens`)

Semua warna teras ditakrifkan di `:root` dalam [public/css/app.css](file:///f:/gesture%20speak/gesturespeak-v2/public/css/app.css). Jangan tulis kod warna mentah berulang secara *inline* apabila token sudah wujud.

| Token CSS | Nilai | Peranan Utama | Syarat Penggunaan |
| :--- | :--- | :--- | :--- |
| `--primary` | `#16324f` | Teks utama, butang utama, permukaan kad pelajar/CTA | Kontras tinggi di atas putih & `--bg-gradient` |
| `--primary-dark` | `#0f2238` | Pentas kamera AI, `.site-footer`, teks atas aksen terang | Untuk latar gelap berfasa telemetri |
| `--secondary` | `#26b0e5` | Aksen interaktif, garis aktif, identiti pelajar | Gunakan untuk sempadan aktif/fokus, bukan pembahagi neon |
| `--secondary-light` | `#58caf3` | Sudut pemidang tilik piksel & garisan sendi tangan | Untuk kontras di atas `--primary-dark` |
| `--secondary-dark` | `#0284c7` | Pautan teks, *kicker*, sorotan tajuk | Memenuhi WCAG AA di atas latar putih/krim |
| `--accent` | `#ffe942` | Butang CTA kuning (`.btn-accent`, `.btn-cta-yellow`), bintang | Mesti dipadankan dengan teks `--primary` / `--primary-dark` |
| `--pixel-green` / `--success` | `#22c55e` / `#059669` | Status padanan tepat, kemajuan selesai | Gunakan bersama `--success-dark` (`#065f46`) untuk teks di atas tint cerah |
| `--danger` | `#d63031` | Mesej ralat pengesahan & amaran tindakan | Jangan bergantung pada warna semata-mata; sertakan teks jelas |
| `--bg-gradient` | `#fffdf7` | Latar kanvas utama aplikasi | Disertai corak titik halus `rgba(22, 50, 79, 0.055)` |
| `--bg-card` | `rgba(255, 255, 255, 0.98)` | Permukaan kad kandungan & borang | Kekal senyap; biar kandungan menjadi fokus |
| `--text-muted` | `#475569` | Subtajuk, kapsyen, petunjuk input | Memastikan kontras >= 4.5:1 di atas putih |
| `--border-light` | `rgba(22, 50, 79, 0.14)` | Garisan kad dan pembahagi halus | Utamakan garisan halus `1px` (`0.08`–`0.12` alpha) untuk kad moden |

### Larangan Warna (*Anti-Slop*)
- **Dilarang** menggunakan ungu generik (`#6c5ce7`, `#8b5cf6`), kecerunan neon pelangi pada teks, atau cahaya *glow* hiasan pada kad baru (termasuk menyelaraskan semula `$accent` ungu pada portal ibu bapa apabila mengemaskini portal tersebut).
- **Dilarang** memakai pembahagi neon tebal (`border-top: 3px solid var(--secondary)`) pada footer atau kad statik.
- **Dilarang** memakai berbilang warna berbeza pada keempat-empat sudut bingkai kamera — gunakan **satu** warna seragam `--secondary-light` (`#58caf3`).

---

## 3. Tipografi & Peraturan Fon `CutePixel`

Sign Cilik menggabungkan tiga keluarga fon daripada [layouts/app.blade.php](file:///f:/gesture%20speak/gesturespeak-v2/resources/views/layouts/app.blade.php) dan [public/css/app.css](file:///f:/gesture%20speak/gesturespeak-v2/public/css/app.css):

| Fon | Token CSS | Berat Dibenarkan | Kegunaan Sah |
| :--- | :--- | :--- | :--- |
| **Fredoka** | `--font-display` | `500`, `600` (elak `700`–`900` kecuali legasi belum dikemas kini) | Tajuk `<h1>`–`<h4>`, jenama teks, nama modul |
| **Nunito** | `--font-main` | `400`, `600`, `700` (elak `800`–`900` baru) | Teks perenggan, label input, navigasi, butang, senarai |
| **CutePixel** | `--font-pixel` | `500` (`size-adjust: 140%`, `letter-spacing: 0.04em`, `tabular-nums`) | **KHUSUS** untuk telemetri pendek & kod |

### Peraturan Wajib Fon Piksel (`CutePixel.ttf`)
1. **Di mana `CutePixel` MESTI digunakan:**
   - Telemetri kamera & kuiz: contoh `AI BIM | 30 FPS`, `Padanan tepat | 96%`, `.pixel-hud`, `.pixel-stat-num`.
   - Kod pelajar & token data: contoh `ADAM-1234`, `DEMO-1234`, `.pixel-code`, slot digit `.pin-box`.
   - Bilangan ringkas pada kad modul: contoh `27 isyarat`.
2. **Di mana `CutePixel` DILARANG digunakan:**
   - Perenggan penerangan, arahan borang panjang, butang navigasi umum, atau tajuk halaman utama `<h1>` (untuk menjaga kebolehbacaan kanak-kanak dan pengguna skrin kecil).
3. **Skala Saiz Responsif:**
   - Gunakan `clamp(min, fluid, max)` dengan syarat **`max <= 2.5 * min`** (WCAG 1.4.4 supaya zum pelayar 200% tidak terhalang).
   - Hadkan panjang baris perenggan utama kepada `50ch`–`62ch` (`max-width: 52ch`).

---

## 4. Jarak, Grid, Kedalaman & Jejari Tersarang

- **Skala Jarak `4px`:** Semua `padding`, `margin`, dan `gap` mesti berada dalam gandaan `4px` (`0.25rem`, `0.5rem`, `0.75rem`, `1rem`, `1.25rem`, `1.5rem`, `2rem`, `3rem` / token `--space-1` hingga `--space-8`).
- **Sifat Logik CSS:** Utamakan sifat logik (`margin-inline`, `padding-inline-start`, `min-block-size`, `min-inline-size`) untuk jarak aliran teks dan kawalan.
- **Jejari Sudut Tersarang (*Concentric Radii: `inner = outer - padding`*):**
  - Bekas luar / kad utama: `20px` (`.card`, `.auth-card`, `.hero-console`, `.cta-banner`)
  - Sub-kad / dunia belajar / pemidang tilik: `16px` / `12px` (`.world-card`, `.console-screen`, `.step-mini-card`, `.demo-box`, `.form-input`, `.role-switcher`)
  - Kawalan dalaman & slot: `8px`–`10px` (`.role-tab`, `.pin-box`, `.console-pill`, `.btn-sm`, `.password-toggle-btn`, `.pin-toggle-btn`)
  - Lencana kod kecil: `6px` (`.pixel-code`)
- **Permukaan & Bayang Senyap:**
  - Gunakan sempadan halus `1px solid rgba(22, 50, 79, 0.1)` dan `--shadow-sm` / `--shadow-md` (`0 1px 2px ...`, `0 8px 24px ...`).
  - **Elakkan** bayang komik keras (`4px 4px 0`, `6px 6px 0`), sempadan `2px dashed`, atau bingkai tebal berganda pada kad maklumat dan navigasi.
- **Keseimbangan Grid:**
  - Elakkan baris terakhir tergantung tidak seimbang (cth. `5 + 2` untuk 7 kategori). Gunakan `display: flex; flex-wrap: wrap; justify-content: center` untuk menghasilkan baris simetri (cth. `4 + 3` di desktop, 2 lajur pada skrin sempit `<= 560px`).

---

## 5. Aset Jenama, Ikon SVG & Peraturan Emoji

### Aset Jenama Rasmi (`public/media/brand/`)
- `signcilik-logo.svg` — Logo latar cerah (navbar & halaman cerah).
- `signcilik-logo-dark.svg` — Logo latar gelap (footer & mod gelap).
- `signcilik-icon.svg` — Maskot bulatan/kotak piksel biru (ikon aplikasi & `<x-sc-icon name="mascot" />`).

### Komponen Ikon `<x-sc-icon>` & `<x-sc-category-icon>`
Sentiasa guna komponen Blade [sc-icon.blade.php](file:///f:/gesture%20speak/gesturespeak-v2/resources/views/components/sc-icon.blade.php) dan [sc-category-icon.blade.php](file:///f:/gesture%20speak/gesturespeak-v2/resources/views/components/sc-category-icon.blade.php):

- **Senarai 16 ikon SVG rasmi (+ `mascot`):**
  `abjad-nombor`, `bintang-kuiz`, `buku`, `guru`, `haiwan`, `ibu-bapa`, `kamera-ai`, `keluarga`, `keluarga-guru`, `kod-pin`, `pelajar`, `pelajar-cemerlang`, `robot-ai`, `suara-bm`, `trofi`, `ucapan`.
- **Ikon hiasan mesti kekal `alt=""` (lalai pada `<x-sc-icon>`)** supaya `aria-hidden="true"` ditambah secara automatik; nyatakan `alt="..."` hanya jika ikon berdiri sendiri tanpa teks yang menjelaskan maknanya.
- **Larangan Emoji UI:** Dilarang menambah emoji (`🏫`, `📊`, `🏆`, `⚠️`, `👩‍🏫`, `🐾`, `⭐`) ke dalam tajuk kad, label metrik, butang, atau lencana. Gantikan secara berperingkat dengan `<x-sc-icon>` pada halaman legasi (`learn/*`, `quiz/*`, `guardian/*`, `admin/*`).
- **Kawalan Ketumpatan Ikon:**
  - Jangan letak ikon pada setiap pautan teks navigasi utama.
  - Jangan letak kotak kapsul `.section-pill` berulang-ulang di atas setiap tajuk `<h2>` atau setiap kad peranan — guna `.section-eyebrow` atau `.role-kicker-row` yang ringkas.

---

## 6. Struktur Halaman & Komponen Utama

1. **Header & Footer (`layouts/app.blade.php`):**
   - Header lekat penuh (`position: sticky; top: 0; width: 100%`) dengan latar separuh telus `rgba(252, 251, 247, 0.94)` dan `border-bottom: 1px solid rgba(22, 50, 79, 0.08)`.
   - Sediakan skip-link (`Langkau ke kandungan utama`) ke `<main id="main-content" class="page-container" tabindex="-1">`.
   - Setiap `<nav>` mempunyai `aria-label` yang jelas dan berbeza tanpa mengulangi perkataan *"navigasi"* (cth. `aria-label="Utama"`, `aria-label="Dunia permainan"`, `aria-label="Pintu masuk"`).
2. **Hero & Seksyen Halaman Utama (`home.blade.php`):**
   - Maksimum **1 butang utama + 1 butang sekunder** dalam `.hero-cta-row`, dan kedua-duanya **mesti menuju destinasi berbeza** (cth. `Mula belajar sekarang` -> `/student/login` dan `Lihat modul BIM` -> `#modul`).
   - Dilarang menambah baris kad lencana ringkas terus di bawah butang hero jika seksyen ciri sudah wujud di bawahnya.
   - Jangan gabung `display: flex` pada `<li>` yang mempunyai tag sebaris (`<strong>` atau `<code>`) tanpa pembalut teks supaya jarak perkataan kekal normal.
3. **Halaman Log Masuk (`student-login.blade.php`, `login.blade.php`, `register.blade.php`):**
   - Gunakan susun atur 2 lajur `.auth-split` (`minmax(0, 480px) minmax(0, 1fr)`).
   - Butang togol kata laluan (`.password-toggle-btn` dengan `position: absolute`) hanya sah di dalam pembalut `.password-wrap` (`position: relative`). Untuk togol sebaris bersebelahan label seperti skrin PIN, gunakan `.pin-toggle-btn` (`position: static`).
   - Letakkan teks bantuan input (`aria-describedby="pin-help"`) **di atas** kotak input supaya senarai *autocomplete* pelayar tidak menutupinya.
   - Setiap input peribadi mesti menetapkan `autocomplete` yang tepat (`username`, `current-password`, `email`), `label for="id"` yang dipautkan secara eksplisit, dan `enterkeyhint` yang sesuai.

---

## 7. Gerakan, Responsif & Kebolehcapaian (`WCAG AA`)

- **Gerakan:**
  - Gunakan lengkung tungal `--ease-out: cubic-bezier(0.22, 1, 0.36, 1)` dengan tempoh `140ms`–`180ms`.
  - Animasikan `transform` dan `opacity` sahaja; balut kesan `:hover` di dalam `@media (hover: hover) and (pointer: fine)` supaya skrin sentuh tidak mengalami *sticky hover*.
  - Jangan buang peraturan `@media (prefers-reduced-motion: reduce)`.
- **Responsif & Limpahan Skrin:**
  - `body` menggunakan `min-height: 100dvh` (elak `100vh`) dan `overflow-x: clip`.
  - Pastikan semua anak grid/flex mempunyai `min-width: 0`. Tiada elemen dibenarkan melebihi lebar paparan pada lebar ujian `390px` (telefon) dan `1440px` (desktop): `document.documentElement.scrollWidth === document.documentElement.clientWidth`.
- **Kebolehcapaian Wajib:**
  - `:focus-visible` mesti mempunyai `outline: 3px solid var(--primary); outline-offset: 3px;` (jangan matikan `outline` tanpa ganti, dan kekalkan `@media (forced-colors: active)`).
  - Saiz sasaran klik/sentuh minimum `44px` di dalam `@media (pointer: coarse)`, `48px` (`--touch-min`) untuk butang umum, dan `54px` (`--touch-kid`) untuk CTA murid.
  - Hierarki tajuk mengikut turutan `<h1>` -> `<h2>` -> `<h3>` tanpa melangkau aras; satu `<h1>` sahaja per halaman.
  - Jangan letakkan `aria-label` pada `<div>` atau `<section>` hiasan kecuali benar-benar membentuk mercu tanda penting; guna `<aside>` untuk panel maklumat sampingan.
  - Untuk modal baharu atau penambahbaikan modal sedia ada, utamakan `<dialog>` natif (`.showModal()`) berbanding `div` `display: flex` manual supaya pengurusan fokus dan `Escape` dikendalikan pelayar.

---

## 8. Senarai Semak Pra-Siap (*Pre-Ship Checklist*)

Sebelum menamatkan sebarang kerja UI baharu atau penyelarasan halaman sedia ada (`learn/*`, `quiz/*`, `guardian/*`, `admin/*`):

1. **Semak Token & Jenama:** Tiada nama *GestureSpeak*, tiada ungu generik `#6c5ce7`/`#8b5cf6`, tiada emoji UI gantian kepada `<x-sc-icon>`.
2. **Semak Tipografi & Sudut:** Berat fon tajuk `600`, `CutePixel` hanya pada kod/telemetri pendek, jejari tersarang mematuhi skala (`20 -> 16/12 -> 8/10`).
3. **Semak Responsif & Visual:** Ujian paparan pada lebar `1440px` dan `390px` menunjukkan `scrollWidth === clientWidth`, tiada butang terkeluar daripada bekasnya, dan tiada teks terpotong.
4. **Semak Kebolehcapaian & Teks Entiti:** Label `<label for>` lengkap, `:focus-visible` jelas, dan patuhi jangkaan teks ujian Laravel (maklumkan atau selaraskan dengan teliti jika ujian sedia ada seperti [ExampleTest.php](file:///f:/gesture%20speak/gesturespeak-v2/tests/Feature/ExampleTest.php) memeriksa rentetan HTML khusus).
5. **Jalankan Verifikasi:**
   - `php artisan view:cache` & `php artisan view:clear`
   - `php artisan test`
   - `node --test tests/js/*.test.mjs` (jika menyentuh JS kamera/kuiz)

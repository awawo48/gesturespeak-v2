# Reka Bentuk: Profil Guru & Ibu Bapa + Kad Guru Pembimbing dalam Portal Ibu Bapa

**Tarikh:** 2026-11-23  
**Projek:** Sign Cilik (`gesturespeak-v2`) — Portal Ibu Bapa & Guru  
**Pendekatan Dipilih:** Pendekatan 1 — Halaman Profil Khusus (`/guardian/profile`) + Kad "Guru Pembimbing BIM" di Portal Ibu Bapa

---

## 1. Tujuan & Masalah Yang Diselesaikan
Pada masa ini, akaun dewasa (`users`) hanya menyimpan `name`, `email`, `role`, dan `status`, tanpa halaman profil untuk mengemas kini maklumat diri atau kata laluan. Selain itu, ibu bapa yang memantau anak di Portal Ibu Bapa tidak dapat melihat maklumat rasmi guru anak mereka (sekolah, peranan pendidikan khas, status pengesahan, atau cara berhubung).

## 2. Matlamat, Bukan Matlamat & Anti-Matlamat
- **Matlamat:**
  1. Menyediakan halaman **Profil & Tetapan Akaun** (`/guardian/profile`) yang menyesuaikan diri untuk **Guru** dan **Ibu Bapa**.
  2. Membolehkan **Guru** melihat status pengesahan rasmi (`✓ Disahkan oleh Admin`) serta mengurus butiran sekolah, kategori pendidik, kod sekolah KPM, dan kawalan privasi nombor telefon pejabat (*opt-in*).
  3. Membolehkan **Ibu Bapa** mengemas kini nama, hubungan (*Ibu / Bapa / Penjaga*), no. telefon perhubungan (pilihan), dan menukar kata laluan.
  4. Memaparkan **Kad "Guru Pembimbing BIM"** dalam Portal Ibu Bapa bagi setiap anak yang dipautkan kepada guru, supaya kolaborasi rumah–sekolah lebih telus dan dipercayai.
- **Bukan Matlamat:**
  - Tiada sistem sembang (*live chat*) terbina dalam atau pengumpulan No. MyKad (kekal patuh PDPA).
  - Tiada sistem pilih avatar chibi untuk dewasa (kekal gaya *Quiet Chrome* + inisial / ikon peranan).
- **Anti-Matlamat:**
  - Tidak mendedahkan nombor telefon peribadi guru tanpa izin (hanya papar nombor pejabat/emel kerja jika guru mengaktifkan togol privasi).

## 3. Pendekatan Yang Dipertimbangkan
| Pendekatan | Ringkasan | Pertukaran (*Trade-off*) | Status |
|---|---|---|---|
| **1** | **Halaman Profil khusus (`/guardian/profile`) + Kad "Guru Pembimbing" di Portal Ibu Bapa** | **Kemas, menjaga privasi guru, mudah diurus dan disambung dengan aliran pengesahan guru** | **Dipilih** |
| **2** | Modal tetapan kecil + 1 baris info guru di bawah nota | Cepat dibina, tetapi sempit untuk status pengesahan + kata laluan + maklumat hubungan | Ditolak |

## 4. Reka Bentuk Antara Muka & Data

### A. Halaman Profil & Tetapan Akaun (`/guardian/profile`)
Susun atur 2 lajur (*Quiet Chrome*, konsisten dengan Sign Cilik Pixel v2):
- **Lajur Kiri (Ringkasan Akaun & Keselamatan):**
  - Kad identiti: Inisial/ikon peranan, Nama Penuh, Emel, lencana peranan (`Guru` / `Ibu Bapa`), dan bagi Guru: lencana **`✓ Disahkan oleh Admin`** + tarikh kelulusan.
  - Kad **Tukar Kata Laluan**: Kata laluan semasa, kata laluan baharu, sahkan kata laluan baharu, butang **`Kemas kini kata laluan`**.
- **Lajur Kanan (Butiran Profil Mengikut Peranan):**
  - **Jika Guru:**
    - Nama Penuh, Emel Kerja *(lencana automatik jika `@moe-dl.edu.my`)*
    - Nama Sekolah / Institusi, Kod Sekolah KPM *(pilihan)*, Kategori Pendidik (*Guru Pendidikan Khas PPKI/SKPK, Prasekolah, Sekolah Rendah/Menengah, Juruterapi BIM*)
    - No. Telefon Pejabat *(pilihan)* + **Togol Privasi:** `[✓] Papar emel & telefon pejabat kepada ibu bapa murid saya`
    - Butang **`Simpan perubahan profil →`**
  - **Jika Ibu Bapa:**
    - Nama Penuh, Emel, Hubungan dengan anak (*Ibu / Bapa / Penjaga*), No. Telefon Perhubungan *(pilihan)*
    - Ringkasan senarai anak dipautkan + butang **`Simpan perubahan profil →`**

### B. Kad "Guru Pembimbing BIM" dalam Portal Ibu Bapa
Dipaparkan pada halaman butiran anak (`/guardian/child/{student}`) dan ringkasan kecil pada kad anak di papan pemuka:
- Nama Guru (cth: *Cikgu Tan Mei Ling*) + lencana hijau **`✓ Guru Disahkan`**
- Sekolah & Peranan: *SK Pendidikan Khas Jalan Peel · Guru Pendidikan Khas (PPKI)*
- Perhubungan *(jika dibenarkan oleh guru)*: Emel kerja & No. telefon pejabat
- Nota tugasan mingguan guru terletak bersebelahan supaya ibu bapa terus nampak konteks penuh.

### C. Perubahan Pangkalan Data & Laluan Laravel (Cadangan Pembina)
- **Kolum baharu (nullable) pada jadual `users`:**
  - `school_name` (`string`, 150)
  - `school_code` (`string`, 20)
  - `educator_type` (`string`, 60)
  - `phone` (`string`, 30)
  - `show_contact_to_parents` (`boolean`, default `true`)
  - `relationship_label` (`string`, 40 — untuk ibu bapa)
- **Laluan baharu di bawah `guardian.`:**
  - `GET /guardian/profile` — papar halaman profil
  - `PUT /guardian/profile` — simpan kemas kini profil
  - `PUT /guardian/profile/password` — tukar kata laluan

## 5. Skop Pelaksanaan
1. **FigCraft:** Tambah artboard **Profil Guru & Ibu Bapa** + kemas kini **Portal Ibu Bapa & Guru** dengan **Kad Guru Pembimbing BIM** dalam `F:\figcraft\sign cilik.figcraft`.
2. **Laravel (`gesturespeak-v2`):** Tambah migrasi kolum profil pada `users`, controller/routes `/guardian/profile`, dan paparan Kad Guru Pembimbing dalam portal ibu bapa.

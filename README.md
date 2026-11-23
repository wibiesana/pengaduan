# SIPAS - Sistem Informasi Pengaduan & Aspirasi Sekolah SMKN 1 Cipeundeuy

Aplikasi Web Pengaduan Resmi untuk **SMKN 1 Cipeundeuy (Kabupaten Bandung Barat)** berbasis **PHP Native** dan **MariaDB / MySQL**.

Mengusung palet warna tema yang selaras dengan logo SMKN 1 Cipeundeuy (Deep Azure Blue, Kujang Gold/Yellow, White, and Slate).

---

## 🌟 Fitur Utama

1. **Wajib Login Sebelum Melapor (Cegah Laporan Palsu/Fiktif)**:
   - Pelapor (Siswa, Orang Tua / Wali Murid, Masyarakat Umum) harus mendaftar atau masuk ke akun yang valid.
   - Pendaftaran mencantumkan identitas resmi (NISN / NIK, No. WhatsApp aktif, email).

2. **2 Kategori / Jalur Pengaduan**:
   - **Pengaduan Umum & Sarana**: Kerusakan fasilitas ruang kelas, bengkel otomotif/kejuruan, toilet, kebersihan, KBM, dan layanan administrasi tata usaha.
   - **Laporan Kekerasan & Bullying (TPPK)**: Jalur khusus penanganan perundungan fisik, verbal, cyberbullying, pemalakan/pemerasan, dan pelecehan. Dilengkapi opsi privasi **Anonim / Rahasia** di mana data pelapor hanya dapat diakses oleh Tim Satgas TPPK/BK.

3. **Upload Foto Bukti Pendukung**:
   - Pelapor dapat mengunggah bukti foto (JPG, PNG, WEBP max 5MB).
   - Pratinjau gambar instan (*live image preview*).

4. **Halaman Admin & Petugas (Tindak Lanjut Laporan)**:
   - Dashboard analitik & ringkasan metrik (Total Laporan, Umum, Bullying, Menunggu, Diproses, Selesai).
   - Filter komprehensif berdasarkan jenis laporan, status, prioritas, rentang tanggal, dan pencarian kata kunci.
   - **Formulir Tindak Lanjut**: Memperbarui status penanganan (`menunggu` &rarr; `diproses` &rarr; `selesai` / `ditolak`) dan menambahkan catatan resmi/tanggapan.
   - Riwayat kronologis log tindak lanjut lengkap dengan waktu dan nama petugas.
   - Manajemen akun pelapor (aktifkan / nonaktifkan akun spam).

5. **Cetak & Dokumen Resmi**:
   - Cetak Tanda Terima / Tiket Laporan resmi ber-Kop SMKN 1 Cipeundeuy untuk pelapor.
   - Rekapitulasi Laporan Pengaduan untuk pimpinan sekolah (Kepala Sekolah & Komite).

---

## 🔑 Akun Uji Coba (Demo Accounts)

| Peran (Role) | Username | Password | Deskripsi |
| :--- | :--- | :--- | :--- |
| **Administrator TPPK** | `admin` | `12345678` | Akses penuh Panel Admin & Tindak Lanjut |
| **Petugas BK / TPPK** | `petugas_bk` | `admin123` | Penanganan khusus kasus siswa & bullying |
| **Pelapor (Siswa)** | `siswa1` | `siswa123` | Akun Siswa XII TKRO |
| **Pelapor (Wali Murid)**| `wali1` | `wali123` | Akun Orang Tua / Wali Siswa |

---

## 🛠️ Cara Menjalankan Aplikasi

### 1. Konfigurasi Database (MariaDB / MySQL)
File konfigurasi berada di `config/database.php`:
- **Host**: `127.0.0.1` (Port 3306)
- **Database**: `db_pengaduan_smkn1cipeundeuy`
- **User**: `root`
- **Password**: ``

*Skema SQL dan data awal (kategori, akun demo, contoh pengaduan) telah terpasang di `database/schema.sql`.*

### 2. Jalankan Melalui PHP Built-in Server
Buka terminal / PowerShell di direktori `D:\work\pengaduan`, lalu jalankan:

```bash
php -S localhost:8080
```

Buka peramban (browser) dan akses:
👉 **http://localhost:8080**

### 3. Struktur Direktori Proyek

```
pengaduan/
├── admin/               # Halaman Panel Admin & Tindak Lanjut
│   ├── dashboard.php    # Dashboard statistik dan antrean prioritas
│   ├── laporan.php      # Daftar filter seluruh pengaduan
│   ├── detail.php       # Detail pengaduan & form tindak lanjut
│   ├── users.php        # Manajemen pengguna terdaftar
│   └── cetak.php        # Cetak rekapitulasi laporan pengaduan
├── asset/
│   └── img/
│       └── logo.jpg     # Logo resmi SMKN 1 Cipeundeuy
├── auth/                # Otentikasi
│   ├── login.php        # Masuk akun pelapor & admin
│   ├── register.php     # Registrasi Siswa / Wali / Masyarakat
│   └── logout.php       # Keluar sistem
├── config/              # Konfigurasi & Helpers
│   ├── database.php     # Koneksi PDO MariaDB
│   └── helpers.php      # Helper otentikasi, format tanggal, upload foto
├── database/
│   └── schema.sql       # Skema DDL dan data awal
├── includes/            # Komponen template
│   ├── header.php       # Navigasi atas & tema logo SMKN 1 Cipeundeuy
│   ├── footer.php       # Footer resmi & hotline TPPK
│   └── sidebar_admin.php# Sidebar navigasi panel admin
├── pelapor/             # Modul Pelapor
│   ├── dashboard.php    # Dashboard riwayat pengaduan pelapor
│   ├── buat-laporan.php # Form buat laporan (Umum & Bullying) + upload
│   ├── detail.php       # Pantau progres & respon sekolah
│   └── cetak-tiket.php  # Cetak bukti tanda terima pelaporan
├── uploads/             # Folder penyimpanan foto bukti
└── index.php            # Halaman beranda utama publik
```

-- Database Schema: Sistem Pengaduan SMKN 1 Cipeundeuy
-- Karakteristik: MariaDB / MySQL

CREATE DATABASE IF NOT EXISTS `db_pengaduan_smkn1cipeundeuy` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `db_pengaduan_smkn1cipeundeuy`;

-- 1. Tabel Users (Admin, Petugas, Siswa, Wali Siswa, Masyarakat)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `no_telp` VARCHAR(25) NOT NULL,
    `role` ENUM('admin', 'petugas', 'pelapor') NOT NULL DEFAULT 'pelapor',
    `tipe_pelapor` ENUM('siswa', 'wali_murid', 'masyarakat', 'staff') NOT NULL DEFAULT 'siswa',
    `nomor_identitas` VARCHAR(50) NULL COMMENT 'NISN untuk siswa, NIK untuk wali/masyarakat',
    `alamat` TEXT NULL,
    `status_aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Kategori Pengaduan
CREATE TABLE IF NOT EXISTS `kategori_pengaduan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `jenis_laporan` ENUM('umum', 'kekerasan_bullying') NOT NULL,
    `nama_kategori` VARCHAR(100) NOT NULL,
    `deskripsi` TEXT NULL,
    `icon` VARCHAR(50) DEFAULT 'fa-folder'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Pengaduan
CREATE TABLE IF NOT EXISTS `pengaduan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_tiket` VARCHAR(30) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `jenis_laporan` ENUM('umum', 'kekerasan_bullying') NOT NULL,
    `kategori_id` INT NULL,
    `judul` VARCHAR(200) NOT NULL,
    `isi_laporan` TEXT NOT NULL,
    `tanggal_kejadian` DATE NOT NULL,
    `lokasi_kejadian` VARCHAR(150) NOT NULL,
    `foto_bukti` VARCHAR(255) NULL,
    `status_privasi` ENUM('publik', 'rahasia') NOT NULL DEFAULT 'publik' COMMENT 'rahasia: hanya admin/TPPK yang tahu identitas pelapor',
    `prioritas` ENUM('rendah', 'sedang', 'tinggi', 'darurat') NOT NULL DEFAULT 'sedang',
    `status` ENUM('menunggu', 'diproses', 'selesai', 'ditolak') NOT NULL DEFAULT 'menunggu',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`kategori_id`) REFERENCES `kategori_pengaduan`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Riwayat Tindak Lanjut / Respon Petugas
CREATE TABLE IF NOT EXISTS `tindak_lanjut` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pengaduan_id` INT NOT NULL,
    `petugas_id` INT NOT NULL,
    `status_sebelumnya` ENUM('menunggu', 'diproses', 'selesai', 'ditolak') NOT NULL,
    `status_terbaru` ENUM('menunggu', 'diproses', 'selesai', 'ditolak') NOT NULL,
    `catatan` TEXT NOT NULL,
    `lampiran_tindak_lanjut` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`pengaduan_id`) REFERENCES `pengaduan`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`petugas_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEED DATA: Kategori Pengaduan
INSERT INTO `kategori_pengaduan` (`jenis_laporan`, `nama_kategori`, `deskripsi`, `icon`) VALUES
('umum', 'Sarana & Prasarana', 'Laporan kerusakan fasilitas kelas, laboratorium bengkel, toilet, musholla, lapangan, dll.', 'fa-tools'),
('umum', 'Kebersihan & Lingkungan', 'Laporan area sekolah kotor, tempat sampah penuh, drainase mampet, atau polusi lingkungan.', 'fa-leaf'),
('umum', 'Pelayanan Administrasi & Tata Usaha', 'Laporan terkait pengurusan surat menyurat, legalisir, surat izin, dan layanan administrasi.', 'fa-id-card'),
('umum', 'Proses Belajar & Akademik', 'Laporan kendala jadwal KBM, materi pembelajaran, atau kegiatan magang/PKL.', 'fa-graduation-cap'),
('umum', 'Tata Tertib & Keamanan', 'Laporan ketertiban gerbang, parkir kendaraan, helm, atau kerumunan tanpa izin.', 'fa-shield-halved'),
('kekerasan_bullying', 'Perundungan Fisik (Bullying)', 'Kekerasan fisik seperti pemukulan, penendangan, dorongan kasar, atau kontak fisik melukai.', 'fa-hand-fist'),
('kekerasan_bullying', 'Perundungan Verbal', 'Ejekan, hinaan martabat, intimidasi kata-kata, body shaming, dan ancaman.', 'fa-comments'),
('kekerasan_bullying', 'Perundungan Daring (Cyberbullying)', 'Penyebaran aib di media sosial, komentar jahat berulang, doxxing, atau akun palsu pencemar nama baik.', 'fa-mobile-screen-button'),
('kekerasan_bullying', 'Pemalakan & Pemerasan', 'Pemaksaan meminta uang kas, uang jajan, atau merampas barang milik siswa lain.', 'fa-money-bill-wave'),
('kekerasan_bullying', 'Pelecehan Seksual', 'Tindakan atau ucapan bernuansa seksual yang merendahkan, melecehkan, atau tanpa persetujuan.', 'fa-triangle-exclamation'),
('kekerasan_bullying', 'Diskriminasi & Pengucilan', 'Tindakan mengucilkan siswa dari pergaulan kelas secara sengaja dan sistematis.', 'fa-users-slash');

-- SEED DATA: User Administrator & Pelapor Contoh
-- Password hash: admin -> '12345678', siswa -> 'siswa123'
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `email`, `no_telp`, `role`, `tipe_pelapor`, `nomor_identitas`, `alamat`) VALUES
('admin', '$2y$12$kpJY1foBAShVGBm9hPKBVugazr9P0v.Qp2S88mekJNtvcJ0PxEKve', 'Administrator TPPK SMKN 1 Cipeundeuy', 'admin@smkn1cipeundeuy.sch.id', '081234567890', 'admin', 'staff', '198501012010011001', 'Jl. Cirata Margalaksana RT/RW: 001/012 Cipeundeuy'),
('petugas_bk', '$2y$12$1WC1Is56cvOw.co7LOuT6e2ffisCSjYAaNIE2IkgSVlqgP6n8rqQe', 'Guru Bimbingan Konseling / TPPK', 'bk@smkn1cipeundeuy.sch.id', '081234567891', 'petugas', 'staff', '198802022011012002', 'Cipeundeuy, Bandung Barat'),
('siswa1', '$2y$12$UC6CAd3f5p0KwFfWSzakKe7qQI/4opQII90VVdG7SgMWuspYVnSoq', 'Ahmad Fauzi (Siswa XII TKRO)', 'fauzi@example.com', '085712345678', 'pelapor', 'siswa', '0061234567', 'Jl. Raya Cipeundeuy No. 12, Bandung Barat'),
('wali1', '$2y$12$UC6CAd3f5p0KwFfWSzakKe7qQI/4opQII90VVdG7SgMWuspYVnSoq', 'Bapak Suryadi (Wali Siswa)', 'suryadi@example.com', '082198765432', 'pelapor', 'wali_murid', '3217012345670001', 'Desa Sirnagalih, Kec. Cipeundeuy');

-- SEED DATA: Contoh Laporan Pengaduan Awal
INSERT INTO `pengaduan` (`kode_tiket`, `user_id`, `jenis_laporan`, `kategori_id`, `judul`, `isi_laporan`, `tanggal_kejadian`, `lokasi_kejadian`, `foto_bukti`, `status_privasi`, `prioritas`, `status`) VALUES
('LAP-202611-0001', 3, 'umum', 1, 'Lampu Penerangan Bengkel Otomotif Redup', 'Beberapa lampu neon di area bengkel TKRO bagian selatan padam sehingga menghambat praktik kelistrikan pada sore hari. Mohon dapat diganti.', '2026-11-20', 'Bengkel TKRO Lantai 1', NULL, 'publik', 'sedang', 'diproses'),
('LAP-202611-0002', 3, 'kekerasan_bullying', 6, 'Terjadi Pemalakan di Area Belakang Kantin Saat Jam Istirahat', 'Ada sekelompok oknum yang meminta uang jajan secara paksa kepada adik kelas X saat istirahat kedua. Kejadian sudah berulang dua kali minggu ini.', '2026-11-22', 'Lorong Belakang Kantin Sekolah', NULL, 'rahasia', 'tinggi', 'menunggu');

-- SEED DATA: Contoh Tindak Lanjut untuk Laporan 1
INSERT INTO `tindak_lanjut` (`pengaduan_id`, `petugas_id`, `status_sebelumnya`, `status_terbaru`, `catatan`, `created_at`) VALUES
(1, 1, 'menunggu', 'diproses', 'Laporan telah diverifikasi oleh tim sarpras. Penggantian lampu LED telah dijadwalkan bersama teknisi sekolah pada hari Rabu.', NOW());

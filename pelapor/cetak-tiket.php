<?php
// pelapor/cetak-tiket.php
require_once __DIR__ . '/../config/helpers.php';
require_login();

$user = current_user();
$db = get_db_connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Laporan tidak valid.");
}

$stmt = $db->prepare("
    SELECT p.*, k.nama_kategori, u.nama_lengkap as nama_pelapor, u.tipe_pelapor, u.no_telp as telp_pelapor, u.nomor_identitas
    FROM pengaduan p
    LEFT JOIN kategori_pengaduan k ON p.kategori_id = k.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$lap = $stmt->fetch();

if (!$lap) {
    die("Laporan tidak ditemukan.");
}

// Cek hak akses
if ($lap['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'petugas'])) {
    die("Akses ditolak.");
}

// Ambil riwayat tindak lanjut terakhir jika ada
$stmtTL = $db->prepare("
    SELECT tl.*, u.nama_lengkap as nama_petugas
    FROM tindak_lanjut tl
    JOIN users u ON tl.petugas_id = u.id
    WHERE tl.pengaduan_id = ?
    ORDER BY tl.id DESC
    LIMIT 1
");
$stmtTL->execute([$id]);
$lastTL = $stmtTL->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tanda Terima Pengaduan - <?= htmlspecialchars($lap['kode_tiket']) ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 py-8 px-4 font-sans text-slate-900">

    <div class="max-w-3xl mx-auto mb-4 no-print flex justify-between items-center">
        <a href="<?= base_url('pelapor/detail.php?id=' . $lap['id']) ?>" class="text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Detail Laporan
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs rounded-xl shadow flex items-center gap-2">
            <i class="fa-solid fa-print"></i> Cetak Dokumen (PDF)
        </button>
    </div>

    <!-- Dokumen Tanda Terima -->
    <div class="max-w-3xl mx-auto bg-white p-8 sm:p-12 rounded-2xl shadow-md border border-slate-200">
        <!-- Kop Surat -->
        <div class="border-b-4 border-double border-slate-900 pb-3 mb-6">
            <div class="flex items-center gap-4">
                <img src="<?= base_url('asset/img/logo.png') ?>" alt="Logo SMKN 1 Cipeundeuy" class="h-20 w-20 object-contain shrink-0">
                <div class="text-center flex-1">
                    <h4 class="text-xs font-bold tracking-wider uppercase text-slate-900 leading-tight">PEMERINTAH DAERAH PROVINSI JAWA BARAT</h4>
                    <h4 class="text-xs font-bold tracking-wider uppercase text-slate-900 leading-tight">DINAS PENDIDIKAN</h4>
                    <h4 class="text-xs font-bold tracking-wider uppercase text-slate-900 leading-tight">CABANG DINAS PENDIDIKAN WILAYAH VI</h4>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight my-1">SMK NEGERI 1 CIPEUNDEUY</h2>
                    <p class="text-[11px] text-slate-700 leading-tight">
                        NPSN: 20279928, Jl. Cirata Margalaksana RT/RW: 001/012 Kec. Cipeundeuy
                    </p>
                    <p class="text-[11px] text-slate-700 leading-tight mt-0.5">
                        Kab. Bandung Barat Prov. Jawa Barat Telp.02263200004 Fax.0 Kode Pos 40558
                    </p>
                    <p class="text-[10px] text-slate-600 leading-tight mt-0.5">
                        Website: <span class="underline text-sky-700">www.smkn1cipeundeuy.sch.id</span> Email: <span class="underline text-sky-700">smkn1cpdykbb@gmail.com</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Judul Dokumen -->
        <div class="text-center mb-6">
            <h3 class="text-base font-extrabold uppercase tracking-wider text-slate-900">
                TANDA TERIMA & BUKTI PENGADUAN RESMI
            </h3>
            <p class="text-xs text-slate-500 font-mono mt-0.5">
                Nomor Tiket: <strong class="text-slate-900 text-sm"><?= htmlspecialchars($lap['kode_tiket']) ?></strong>
            </p>
        </div>

        <!-- Info Kotak Ringkasan -->
        <table class="w-full text-xs mb-6 border-collapse border border-slate-200">
            <tbody>
                <tr class="bg-slate-50">
                    <td class="w-1/3 p-2.5 font-bold border border-slate-200">Jenis Layanan</td>
                    <td class="p-2.5 border border-slate-200 font-bold uppercase <?= $lap['jenis_laporan'] === 'kekerasan_bullying' ? 'text-rose-700' : 'text-sky-700' ?>">
                        <?= $lap['jenis_laporan'] === 'kekerasan_bullying' ? 'Laporan Kekerasan & Bullying (TPPK)' : 'Pengaduan Umum & Sarana' ?>
                    </td>
                </tr>
                <tr>
                    <td class="p-2.5 font-bold border border-slate-200">Sub-Kategori</td>
                    <td class="p-2.5 border border-slate-200"><?= htmlspecialchars($lap['nama_kategori'] ?? 'Umum') ?></td>
                </tr>
                <tr class="bg-slate-50">
                    <td class="p-2.5 font-bold border border-slate-200">Status Privasi</td>
                    <td class="p-2.5 border border-slate-200">
                        <?= $lap['status_privasi'] === 'rahasia' ? 'Rahasia / Identitas Terproteksi TPPK' : 'Publik' ?>
                    </td>
                </tr>
                <tr>
                    <td class="p-2.5 font-bold border border-slate-200">Waktu Pelaporan</td>
                    <td class="p-2.5 border border-slate-200"><?= format_tanggal_indonesia($lap['created_at'], true) ?></td>
                </tr>
                <tr class="bg-slate-50">
                    <td class="p-2.5 font-bold border border-slate-200">Nama Pelapor</td>
                    <td class="p-2.5 border border-slate-200">
                        <?= htmlspecialchars($lap['nama_pelapor']) ?> (<?= ucfirst($lap['tipe_pelapor']) ?>)
                        <?php if ($lap['nomor_identitas']): ?>
                            - ID: <?= htmlspecialchars($lap['nomor_identitas']) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="p-2.5 font-bold border border-slate-200">Tanggal Kejadian</td>
                    <td class="p-2.5 border border-slate-200"><?= format_tanggal_indonesia($lap['tanggal_kejadian']) ?></td>
                </tr>
                <tr class="bg-slate-50">
                    <td class="p-2.5 font-bold border border-slate-200">Lokasi Kejadian</td>
                    <td class="p-2.5 border border-slate-200"><?= htmlspecialchars($lap['lokasi_kejadian']) ?></td>
                </tr>
                <tr>
                    <td class="p-2.5 font-bold border border-slate-200">Status Saat Ini</td>
                    <td class="p-2.5 border border-slate-200 font-bold uppercase">
                        <?= htmlspecialchars($lap['status']) ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Rincian Pokok Pengaduan -->
        <div class="mb-6">
            <h4 class="text-xs font-bold uppercase text-slate-700 mb-1">Judul Laporan:</h4>
            <p class="text-sm font-extrabold text-slate-900 mb-3 bg-slate-50 p-2.5 rounded border border-slate-200">
                <?= htmlspecialchars($lap['judul']) ?>
            </p>

            <h4 class="text-xs font-bold uppercase text-slate-700 mb-1">Rincian / Kronologi Peristiwa:</h4>
            <div class="text-xs text-slate-700 leading-relaxed p-3 rounded border border-slate-200 bg-white whitespace-pre-line">
                <?= htmlspecialchars($lap['isi_laporan']) ?>
            </div>
        </div>

        <?php if ($lastTL): ?>
            <!-- Catatan Tindak Lanjut Terakhir -->
            <div class="mb-6 p-3 rounded-lg bg-sky-50 border border-sky-200 text-xs">
                <h5 class="font-bold text-sky-900 mb-1">Tindak Lanjut Terakhir oleh Petugas:</h5>
                <p class="text-slate-700"><?= htmlspecialchars($lastTL['catatan']) ?></p>
                <p class="text-[10px] text-slate-400 mt-1">
                    Petugas: <?= htmlspecialchars($lastTL['nama_petugas']) ?> • <?= format_tanggal_indonesia($lastTL['created_at'], true) ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Catatan & Legalitas Tanda Tangan -->
        <div class="mt-8 pt-6 border-t border-slate-200 grid grid-cols-2 text-xs">
            <div>
                <p class="text-[11px] text-slate-500">
                    * Dokumen ini adalah tanda terima resmi pelaporan pada Sistem Informasi Pengaduan & Aspirasi Sekolah (SIPAS) SMKN 1 Cipeundeuy.<br>
                    * Simpan kode tiket untuk melacak status penanganan.
                </p>
            </div>
            <div class="text-center space-y-12">
                <div>
                    <p class="text-slate-500">Cipeundeuy, <?= date('d F Y') ?></p>
                    <p class="font-bold text-slate-800">Petugas / Tim TPPK</p>
                </div>
                <div>
                    <p class="font-bold border-b border-slate-400 inline-block px-8 pb-0.5">
                        <?= $lastTL ? htmlspecialchars($lastTL['nama_petugas']) : 'Tim Layanan Terpadu' ?>
                    </p>
                    <p class="text-[10px] text-slate-400">SMKN 1 Cipeundeuy</p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

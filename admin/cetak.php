<?php
// admin/cetak.php
require_once __DIR__ . '/../config/helpers.php';
require_role(['admin', 'petugas']);

$db = get_db_connection();

$jenis     = $_GET['jenis'] ?? 'all';
$status    = $_GET['status'] ?? 'all';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date'] ?? date('Y-m-d');

$sql = "
    SELECT p.*, k.nama_kategori, u.nama_lengkap as nama_pelapor, u.tipe_pelapor, u.no_telp as telp_pelapor
    FROM pengaduan p
    LEFT JOIN kategori_pengaduan k ON p.kategori_id = k.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE DATE(p.created_at) BETWEEN ? AND ?
";
$params = [$startDate, $endDate];

if (in_array($jenis, ['umum', 'kekerasan_bullying'])) {
    $sql .= " AND p.jenis_laporan = ?";
    $params[] = $jenis;
}

if (in_array($status, ['menunggu', 'diproses', 'selesai', 'ditolak'])) {
    $sql .= " AND p.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY p.id ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rekap = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Laporan Pengaduan SMKN 1 Cipeundeuy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            @page { size: landscape; margin: 1.5cm; }
        }
    </style>
</head>
<body class="bg-slate-100 py-8 px-4 font-sans text-slate-900">

    <!-- Filter Control Box (Screen Only) -->
    <div class="max-w-6xl mx-auto mb-6 no-print bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <a href="<?= base_url('admin/laporan.php') ?>" class="text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Panel Admin
            </a>

            <form method="GET" action="" class="flex flex-wrap items-center gap-2 text-xs">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400">Dari:</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="px-2.5 py-1.5 border border-slate-200 rounded-lg bg-slate-50">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400">Sampai:</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="px-2.5 py-1.5 border border-slate-200 rounded-lg bg-slate-50">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400">Jenis:</label>
                    <select name="jenis" class="px-2.5 py-1.5 border border-slate-200 rounded-lg bg-slate-50">
                        <option value="all">Semua Jenis</option>
                        <option value="umum" <?= $jenis === 'umum' ? 'selected' : '' ?>>Umum</option>
                        <option value="kekerasan_bullying" <?= $jenis === 'kekerasan_bullying' ? 'selected' : '' ?>>Bullying</option>
                    </select>
                </div>
                <div class="self-end flex items-center gap-2">
                    <button type="submit" class="px-4 py-1.5 bg-slate-800 text-white font-bold rounded-lg hover:bg-slate-900">
                        Filter
                    </button>
                    <button type="button" onclick="window.print()" class="px-4 py-1.5 bg-sky-600 text-white font-bold rounded-lg hover:bg-sky-700 flex items-center gap-1.5 shadow">
                        <i class="fa-solid fa-print"></i> Cetak Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Halaman Cetak Dokumen -->
    <div class="max-w-6xl mx-auto bg-white p-8 sm:p-12 rounded-2xl shadow-md border border-slate-200">
        <!-- Kop Resmi -->
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

        <div class="text-center mb-6">
            <h3 class="text-base font-extrabold uppercase tracking-wider text-slate-900">
                LAPORAN REKAPITULASI PENGADUAN & ASPIRASI SEKOLAH
            </h3>
            <p class="text-xs text-slate-600 mt-0.5">
                Periode: <strong><?= format_tanggal_indonesia($startDate) ?></strong> s/d <strong><?= format_tanggal_indonesia($endDate) ?></strong>
                <?= $jenis !== 'all' ? ' | Kategori: <strong>' . strtoupper($jenis) . '</strong>' : '' ?>
            </p>
        </div>

        <?php if (empty($rekap)): ?>
            <div class="p-8 text-center text-xs text-slate-500 border border-slate-200 rounded-xl">
                Tidak ada data pengaduan pada periode tanggal tersebut.
            </div>
        <?php else: ?>
            <table class="w-full text-left text-xs border-collapse border border-slate-300">
                <thead>
                    <tr class="bg-slate-100 text-slate-800 font-bold uppercase text-[10px]">
                        <th class="border border-slate-300 p-2 text-center w-8">No</th>
                        <th class="border border-slate-300 p-2 w-28">No. Tiket</th>
                        <th class="border border-slate-300 p-2 w-24">Tanggal</th>
                        <th class="border border-slate-300 p-2">Pelapor</th>
                        <th class="border border-slate-300 p-2">Jenis / Kategori</th>
                        <th class="border border-slate-300 p-2">Judul & Lokasi</th>
                        <th class="border border-slate-300 p-2 text-center w-20">Prioritas</th>
                        <th class="border border-slate-300 p-2 text-center w-24">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($rekap as $r): ?>
                        <tr>
                            <td class="border border-slate-300 p-2 text-center"><?= $no++ ?></td>
                            <td class="border border-slate-300 p-2 font-mono font-bold"><?= htmlspecialchars($r['kode_tiket']) ?></td>
                            <td class="border border-slate-300 p-2"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                            <td class="border border-slate-300 p-2">
                                <?= htmlspecialchars($r['nama_pelapor']) ?>
                                <span class="block text-[10px] text-slate-500 capitalize">(<?= htmlspecialchars($r['tipe_pelapor']) ?>)</span>
                            </td>
                            <td class="border border-slate-300 p-2 font-semibold">
                                <?= $r['jenis_laporan'] === 'kekerasan_bullying' ? 'Kekerasan/Bullying' : 'Umum' ?>
                                <span class="block text-[10px] font-normal text-slate-500"><?= htmlspecialchars($r['nama_kategori'] ?? '-') ?></span>
                            </td>
                            <td class="border border-slate-300 p-2">
                                <span class="font-bold block"><?= htmlspecialchars($r['judul']) ?></span>
                                <span class="text-[10px] text-slate-500"><?= htmlspecialchars($r['lokasi_kejadian']) ?></span>
                            </td>
                            <td class="border border-slate-300 p-2 text-center uppercase font-bold text-[10px]">
                                <?= htmlspecialchars($r['prioritas']) ?>
                            </td>
                            <td class="border border-slate-300 p-2 text-center font-bold uppercase text-[10px]">
                                <?= htmlspecialchars($r['status']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Tanda Tangan Pengesahan -->
        <div class="mt-12 pt-6 border-t border-slate-200 grid grid-cols-2 text-xs">
            <div>
                <p class="text-slate-500">Mengetahui,</p>
                <p class="font-bold text-slate-800">Kepala SMKN 1 Cipeundeuy</p>
                <div class="h-20"></div>
                <p class="font-bold border-b border-slate-400 inline-block px-8 pb-0.5">
                    ( .................................................... )
                </p>
                <p class="text-[10px] text-slate-400 mt-0.5">NIP. ....................................</p>
            </div>
            <div class="text-right">
                <p class="text-slate-500">Cipeundeuy, <?= date('d F Y') ?></p>
                <p class="font-bold text-slate-800">Koordinator Satgas TPPK / Sarpras</p>
                <div class="h-20"></div>
                <p class="font-bold border-b border-slate-400 inline-block px-8 pb-0.5">
                    ( Administrator Layanan Pengaduan )
                </p>
                <p class="text-[10px] text-slate-400 mt-0.5">SMKN 1 Cipeundeuy</p>
            </div>
        </div>

    </div>

</body>
</html>

<?php
// admin/laporan.php
require_once __DIR__ . '/../config/helpers.php';
require_role(['admin', 'petugas']);

$db = get_db_connection();
$user = current_user();

// Parameter Filter
$jenis     = $_GET['jenis'] ?? 'all';
$status    = $_GET['status'] ?? 'all';
$prioritas = $_GET['prioritas'] ?? 'all';
$search    = trim($_GET['search'] ?? '');

$sql = "
    SELECT p.*, k.nama_kategori, u.nama_lengkap as nama_pelapor, u.tipe_pelapor, u.no_telp as telp_pelapor
    FROM pengaduan p
    LEFT JOIN kategori_pengaduan k ON p.kategori_id = k.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE 1=1
";
$params = [];

if (in_array($jenis, ['umum', 'kekerasan_bullying'])) {
    $sql .= " AND p.jenis_laporan = ?";
    $params[] = $jenis;
}

if (in_array($status, ['menunggu', 'diproses', 'selesai', 'ditolak'])) {
    $sql .= " AND p.status = ?";
    $params[] = $status;
}

if (in_array($prioritas, ['rendah', 'sedang', 'tinggi', 'darurat'])) {
    $sql .= " AND p.prioritas = ?";
    $params[] = $prioritas;
}

if (!empty($search)) {
    $sql .= " AND (p.kode_tiket LIKE ? OR p.judul LIKE ? OR p.isi_laporan LIKE ? OR u.nama_lengkap LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$sql .= " ORDER BY p.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$laporanList = $stmt->fetchAll();

$pageTitle = 'Semua Pengaduan Masuk';
$activeMenu = $jenis === 'kekerasan_bullying' ? 'laporan_bullying' : ($jenis === 'umum' ? 'laporan_umum' : 'semua_laporan');
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | SIPAS SMKN 1 Cipeundeuy</title>
    <link rel="icon" type="image/png" href="<?= base_url('asset/img/logo.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: {
                        smk: {
                            blue: '#0284c7',
                            'blue-hover': '#0369a1',
                            'blue-dark': '#075985',
                            yellow: '#f59e0b',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="flex h-full text-slate-800 antialiased selection:bg-sky-500 selection:text-white">

    <!-- Sidebar Admin -->
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 sticky top-0 z-30 shadow-xs">
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">
                    <?= $jenis === 'kekerasan_bullying' ? 'Laporan Kekerasan & Bullying (TPPK)' : ($jenis === 'umum' ? 'Daftar Pengaduan Umum' : 'Seluruh Laporan Pengaduan') ?>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">Ditemukan <?= count($laporanList) ?> laporan sesuai kriteria filter</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="<?= base_url('admin/cetak.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>" target="_blank" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center gap-1.5 border border-slate-200">
                    <i class="fa-solid fa-print"></i> Cetak Rekap
                </a>
            </div>
        </header>

        <!-- Body -->
        <main class="p-6 sm:p-8 space-y-6">
            
            <!-- Filter Box -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                <form method="GET" action="" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    
                    <!-- Search Input -->
                    <div class="lg:col-span-2">
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Pencarian</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </span>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Tiket, judul, atau nama pelapor..."
                                class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Filter Jenis -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Jenis Laporan</label>
                        <select name="jenis" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:outline-none">
                            <option value="all">Semua Jenis</option>
                            <option value="umum" <?= $jenis === 'umum' ? 'selected' : '' ?>>Pengaduan Umum</option>
                            <option value="kekerasan_bullying" <?= $jenis === 'kekerasan_bullying' ? 'selected' : '' ?>>Kekerasan / Bullying</option>
                        </select>
                    </div>

                    <!-- Filter Status -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:outline-none">
                            <option value="all">Semua Status</option>
                            <option value="menunggu" <?= $status === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                            <option value="diproses" <?= $status === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                            <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="ditolak" <?= $status === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        </select>
                    </div>

                    <!-- Filter Submit -->
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 py-2 bg-smk-blue hover:bg-smk-blue-hover text-white font-bold text-xs rounded-xl transition flex items-center justify-center gap-1.5 shadow-xs">
                            <i class="fa-solid fa-filter"></i> Filter
                        </button>
                        <a href="<?= base_url('admin/laporan.php') ?>" class="p-2 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl text-xs" title="Reset Filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
                <?php if (empty($laporanList)): ?>
                    <div class="py-16 text-center text-slate-400 text-xs">
                        <i class="fa-solid fa-folder-open text-3xl text-slate-300 mb-2"></i>
                        <p class="font-bold text-slate-700">Tidak Ada Laporan Ditemukan</p>
                        <p class="text-slate-500 mt-0.5">Coba ubah kata kunci pencarian atau sesuaikan opsi filter.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th class="py-3.5 px-6">Tiket & Tanggal</th>
                                    <th class="py-3.5 px-6">Pelapor</th>
                                    <th class="py-3.5 px-6">Jenis & Topik</th>
                                    <th class="py-3.5 px-6">Judul Laporan</th>
                                    <th class="py-3.5 px-6">Bukti</th>
                                    <th class="py-3.5 px-6">Prioritas</th>
                                    <th class="py-3.5 px-6">Status</th>
                                    <th class="py-3.5 px-6 text-center">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                <?php foreach ($laporanList as $lap): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-4 px-6">
                                            <span class="font-mono font-bold text-slate-800 block"><?= htmlspecialchars($lap['kode_tiket']) ?></span>
                                            <span class="text-[11px] text-slate-400"><?= date('d/m/Y H:i', strtotime($lap['created_at'])) ?></span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="font-bold text-slate-800 block">
                                                <?php if ($lap['status_privasi'] === 'rahasia'): ?>
                                                    <i class="fa-solid fa-lock text-rose-500 mr-1" title="Laporan Rahasia / TPPK"></i>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($lap['nama_pelapor']) ?>
                                            </span>
                                            <span class="text-[10px] text-slate-400 capitalize"><?= htmlspecialchars($lap['tipe_pelapor']) ?></span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <?= render_jenis_badge($lap['jenis_laporan']) ?>
                                            <span class="text-[11px] text-slate-500 block mt-1"><?= htmlspecialchars($lap['nama_kategori'] ?? 'Umum') ?></span>
                                        </td>
                                        <td class="py-4 px-6 max-w-xs">
                                            <a href="<?= base_url('admin/detail.php?id=' . $lap['id']) ?>" class="font-bold text-slate-900 hover:text-smk-blue block truncate">
                                                <?= htmlspecialchars($lap['judul']) ?>
                                            </a>
                                            <span class="text-[11px] text-slate-400 block truncate">
                                                <i class="fa-solid fa-location-dot text-[10px] mr-1"></i><?= htmlspecialchars($lap['lokasi_kejadian']) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <?php if ($lap['foto_bukti']): ?>
                                                <a href="<?= base_url('uploads/' . $lap['foto_bukti']) ?>" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-sky-600 hover:text-sky-800 bg-sky-50 px-2 py-1 rounded border border-sky-200">
                                                    <i class="fa-solid fa-image"></i> Foto
                                                </a>
                                            <?php else: ?>
                                                <span class="text-[11px] text-slate-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= in_array($lap['prioritas'], ['darurat', 'tinggi']) ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700' ?>">
                                                <?= $lap['prioritas'] ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <?= render_status_badge($lap['status']) ?>
                                        </td>
                                        <td class="py-4 px-6 text-center whitespace-nowrap">
                                            <a href="<?= base_url('admin/detail.php?id=' . $lap['id']) ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-smk-blue text-white hover:bg-smk-blue-hover shadow-xs transition inline-flex items-center gap-1">
                                                <i class="fa-solid fa-screwdriver-wrench text-[10px]"></i>
                                                <span>Tindak Lanjut</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- Flash message SweetAlert2 -->
    <?php $flash = get_flash(); if (!empty($flash)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: '<?= $flash['type'] === 'danger' ? 'error' : ($flash['type'] === 'warning' ? 'warning' : 'success') ?>',
                    title: '<?= $flash['type'] === 'danger' ? 'Peringatan' : ($flash['type'] === 'warning' ? 'Perhatian' : 'Berhasil!') ?>',
                    text: <?= json_encode($flash['message']) ?>,
                    confirmButtonColor: '#0284c7',
                    timer: 4000,
                    timerProgressBar: true
                });
            });
        </script>
    <?php endif; ?>

</body>
</html>

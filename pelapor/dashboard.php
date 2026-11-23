<?php
// pelapor/dashboard.php
require_once __DIR__ . '/../config/helpers.php';
require_login();

$user = current_user();
$db = get_db_connection();

// Filter status & jenis
$filterStatus = $_GET['status'] ?? 'all';
$filterJenis  = $_GET['jenis'] ?? 'all';

$sql = "
    SELECT p.*, k.nama_kategori 
    FROM pengaduan p
    LEFT JOIN kategori_pengaduan k ON p.kategori_id = k.id
    WHERE p.user_id = ?
";
$params = [$user['id']];

if (in_array($filterStatus, ['menunggu', 'diproses', 'selesai', 'ditolak'])) {
    $sql .= " AND p.status = ?";
    $params[] = $filterStatus;
}

if (in_array($filterJenis, ['umum', 'kekerasan_bullying'])) {
    $sql .= " AND p.jenis_laporan = ?";
    $params[] = $filterJenis;
}

$sql .= " ORDER BY p.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$pengaduanList = $stmt->fetchAll();

// Hitung Statistik Pelapor Ini
$stmtStat = $db->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN jenis_laporan = 'kekerasan_bullying' THEN 1 ELSE 0 END) as bullying
    FROM pengaduan 
    WHERE user_id = ?
");
$stmtStat->execute([$user['id']]);
$stats = $stmtStat->fetch() ?: ['total' => 0, 'menunggu' => 0, 'diproses' => 0, 'selesai' => 0, 'bullying' => 0];

$pageTitle = 'Dashboard Pelapor';
$activeNav = 'pelapor_dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-10 px-4 sm:px-6 lg:px-8 bg-slate-50 min-h-[85vh]">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-sky-900 via-sky-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur text-xs text-sky-200 mb-2">
                        <i class="fa-solid fa-user-check text-emerald-400"></i>
                        <span>Pelapor Terdaftar: <?= htmlspecialchars($user['tipe_pelapor'] ?? 'Umum') ?></span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                        Halo, <?= htmlspecialchars($user['nama_lengkap']) ?>!
                    </h1>
                    <p class="text-xs sm:text-sm text-sky-200 mt-1 max-w-xl">
                        Selamat datang di pusat layanan pengaduan SMKN 1 Cipeundeuy. Anda dapat memantau status penyelesaian dan membuat laporan baru di bawah ini.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="<?= base_url('pelapor/buat-laporan.php?jenis=umum') ?>" class="px-4 py-2.5 rounded-xl font-bold text-xs bg-amber-400 text-slate-950 hover:bg-amber-300 shadow transition flex items-center gap-1.5">
                        <i class="fa-solid fa-bullhorn"></i>
                        <span>Lapor Umum</span>
                    </a>
                    <a href="<?= base_url('pelapor/buat-laporan.php?jenis=kekerasan_bullying') ?>" class="px-4 py-2.5 rounded-xl font-bold text-xs bg-rose-600 text-white hover:bg-rose-500 shadow transition flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-cat"></i>
                        <span>Lapor Bullying</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 4 Stat Counters -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-smk-blue flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Laporan</p>
                    <p class="text-2xl font-black text-slate-900"><?= number_format($stats['total']) ?></p>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Menunggu</p>
                    <p class="text-2xl font-black text-amber-600"><?= number_format($stats['menunggu']) ?></p>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-spinner"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Ditindaklanjuti</p>
                    <p class="text-2xl font-black text-sky-600"><?= number_format($stats['diproses']) ?></p>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Selesai</p>
                    <p class="text-2xl font-black text-emerald-600"><?= number_format($stats['selesai']) ?></p>
                </div>
            </div>
        </div>

        <!-- Filter & Table Card -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- Filter Bar -->
            <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-50/50">
                <div>
                    <h3 class="text-lg font-black text-slate-900">Riwayat Pengaduan Saya</h3>
                    <p class="text-xs text-slate-500">Semua laporan yang pernah Anda sampaikan ke sekolah</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <a href="<?= base_url('pelapor/dashboard.php') ?>" class="px-3 py-1.5 rounded-lg font-bold <?= ($filterStatus === 'all' && $filterJenis === 'all') ? 'bg-smk-blue text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
                        Semua
                    </a>
                    <a href="<?= base_url('pelapor/dashboard.php?status=menunggu') ?>" class="px-3 py-1.5 rounded-lg font-bold <?= $filterStatus === 'menunggu' ? 'bg-amber-500 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
                        Menunggu
                    </a>
                    <a href="<?= base_url('pelapor/dashboard.php?status=diproses') ?>" class="px-3 py-1.5 rounded-lg font-bold <?= $filterStatus === 'diproses' ? 'bg-sky-500 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
                        Diproses
                    </a>
                    <a href="<?= base_url('pelapor/dashboard.php?status=selesai') ?>" class="px-3 py-1.5 rounded-lg font-bold <?= $filterStatus === 'selesai' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
                        Selesai
                    </a>
                    <a href="<?= base_url('pelapor/dashboard.php?jenis=kekerasan_bullying') ?>" class="px-3 py-1.5 rounded-lg font-bold <?= $filterJenis === 'kekerasan_bullying' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100' ?>">
                        <i class="fa-solid fa-shield-cat mr-1"></i> Khusus Bullying
                    </a>
                </div>
            </div>

            <!-- List Content -->
            <?php if (empty($pengaduanList)): ?>
                <div class="py-16 text-center text-slate-400 px-4">
                    <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl text-slate-400">
                        <i class="fa-solid fa-inbox"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-700">Belum Ada Pengaduan</h4>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                        Anda belum memiliki pengaduan pada filter ini. Klik tombol di bawah untuk membuat laporan baru.
                    </p>
                    <div class="mt-4 flex items-center justify-center gap-3">
                        <a href="<?= base_url('pelapor/buat-laporan.php?jenis=umum') ?>" class="px-4 py-2 rounded-xl text-xs font-bold bg-smk-blue text-white hover:bg-smk-blue-hover">
                            Buat Pengaduan Umum
                        </a>
                        <a href="<?= base_url('pelapor/buat-laporan.php?jenis=kekerasan_bullying') ?>" class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 text-white hover:bg-rose-700">
                            Lapor Kasus Bullying
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($pengaduanList as $lap): ?>
                        <div class="p-5 sm:p-6 hover:bg-slate-50/80 transition flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div class="space-y-2 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                        <?= htmlspecialchars($lap['kode_tiket']) ?>
                                    </span>
                                    <?= render_jenis_badge($lap['jenis_laporan']) ?>
                                    <?= render_status_badge($lap['status']) ?>
                                    <?php if ($lap['status_privasi'] === 'rahasia'): ?>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded bg-slate-800 text-white">
                                            <i class="fa-solid fa-lock text-[9px]"></i> Rahasia / TPPK
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h4 class="text-base font-extrabold text-slate-900 hover:text-smk-blue transition">
                                    <a href="<?= base_url('pelapor/detail.php?id=' . $lap['id']) ?>">
                                        <?= htmlspecialchars($lap['judul']) ?>
                                    </a>
                                </h4>

                                <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                                    <?= htmlspecialchars($lap['isi_laporan']) ?>
                                </p>

                                <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-500 pt-1">
                                    <div class="flex items-center gap-1">
                                        <i class="fa-solid fa-calendar text-slate-400"></i>
                                        <span>Kejadian: <?= date('d M Y', strtotime($lap['tanggal_kejadian'])) ?></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <i class="fa-solid fa-location-dot text-slate-400"></i>
                                        <span><?= htmlspecialchars($lap['lokasi_kejadian']) ?></span>
                                    </div>
                                    <?php if ($lap['foto_bukti']): ?>
                                        <div class="flex items-center gap-1 text-sky-600 font-semibold">
                                            <i class="fa-solid fa-image"></i>
                                            <span>Ada Foto Bukti</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 w-full md:w-auto justify-end pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                                <?php if ($lap['status'] === 'menunggu'): ?>
                                    <a href="<?= base_url('pelapor/edit-laporan.php?id=' . $lap['id']) ?>" class="px-3 py-2 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200 transition flex items-center gap-1.5" title="Edit Laporan / Tambah Foto">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        <span>Edit</span>
                                    </a>
                                <?php endif; ?>
                                <a href="<?= base_url('pelapor/detail.php?id=' . $lap['id']) ?>" class="px-4 py-2 rounded-xl text-xs font-bold bg-sky-50 text-smk-blue hover:bg-smk-blue hover:text-white border border-sky-200 transition flex items-center gap-1.5">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <span>Lacak Progres</span>
                                </a>
                                <a href="<?= base_url('pelapor/cetak-tiket.php?id=' . $lap['id']) ?>" target="_blank" class="p-2 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 transition" title="Cetak Bukti Laporan">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

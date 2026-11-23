<?php
// includes/sidebar_admin.php
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/helpers.php';
}

$db = get_db_connection();

// Ambil statistik ringkas untuk badge navigasi
$stmtCount = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN jenis_laporan = 'kekerasan_bullying' AND status IN ('menunggu', 'diproses') THEN 1 ELSE 0 END) as bullying_aktif
    FROM pengaduan
");
$counts = $stmtCount->fetch() ?: ['total' => 0, 'menunggu' => 0, 'bullying_aktif' => 0];

$activeMenu = $activeMenu ?? 'dashboard';
$user = current_user();
?>
<aside class="w-64 bg-slate-900 text-slate-300 flex-shrink-0 flex flex-col min-h-screen border-r border-slate-800">
    <!-- Header Admin -->
    <div class="p-5 border-b border-slate-800 flex items-center gap-3 bg-slate-950/40">
        <img src="<?= base_url('asset/img/logo.png') ?>" alt="Logo SMKN 1 Cipeundeuy" class="w-10 h-10 object-contain shrink-0 drop-shadow">
        <div>
            <h2 class="text-white font-extrabold text-sm tracking-tight leading-tight">PANEL ADMIN</h2>
            <p class="text-[11px] text-sky-400 font-medium">SMKN 1 Cipeundeuy</p>
        </div>
    </div>

    <!-- User Profile Badge -->
    <div class="px-5 py-4 border-b border-slate-800 bg-slate-800/30">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-sky-500 to-amber-400 text-slate-950 font-black flex items-center justify-center text-sm shadow">
                <?= strtoupper(substr($user['nama_lengkap'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
                <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($user['nama_lengkap'] ?? 'Petugas') ?></p>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold"><?= htmlspecialchars($user['role'] ?? 'petugas') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto text-xs font-semibold">
        <p class="px-3 pt-2 pb-1 text-[10px] uppercase font-bold text-slate-500 tracking-wider">Utama</p>
        
        <a href="<?= base_url('admin/dashboard.php') ?>" class="flex items-center justify-between px-3 py-2.5 rounded-lg transition <?= $activeMenu === 'dashboard' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-gauge-high w-4 text-center <?= $activeMenu === 'dashboard' ? 'text-white' : 'text-sky-400' ?>"></i>
                <span>Dashboard</span>
            </div>
        </a>

        <p class="px-3 pt-4 pb-1 text-[10px] uppercase font-bold text-slate-500 tracking-wider">Manajemen Pengaduan</p>

        <a href="<?= base_url('admin/laporan.php') ?>" class="flex items-center justify-between px-3 py-2.5 rounded-lg transition <?= $activeMenu === 'semua_laporan' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-inbox w-4 text-center <?= $activeMenu === 'semua_laporan' ? 'text-white' : 'text-sky-400' ?>"></i>
                <span>Semua Pengaduan</span>
            </div>
            <?php if ($counts['menunggu'] > 0): ?>
                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-400 text-slate-950">
                    <?= $counts['menunggu'] ?> baru
                </span>
            <?php endif; ?>
        </a>

        <a href="<?= base_url('admin/laporan.php?jenis=kekerasan_bullying') ?>" class="flex items-center justify-between px-3 py-2.5 rounded-lg transition <?= $activeMenu === 'laporan_bullying' ? 'bg-rose-600 text-white shadow-sm' : 'text-rose-400 hover:bg-rose-950/40 hover:text-rose-300' ?>">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-shield-cat w-4 text-center text-rose-400"></i>
                <span class="font-bold">Laporan Bullying / Kekerasan</span>
            </div>
            <?php if ($counts['bullying_aktif'] > 0): ?>
                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-rose-500 text-white animate-pulse">
                    <?= $counts['bullying_aktif'] ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="<?= base_url('admin/laporan.php?jenis=umum') ?>" class="flex items-center justify-between px-3 py-2.5 rounded-lg transition <?= $activeMenu === 'laporan_umum' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-bullhorn w-4 text-center <?= $activeMenu === 'laporan_umum' ? 'text-white' : 'text-sky-400' ?>"></i>
                <span>Pengaduan Umum</span>
            </div>
        </a>

        <p class="px-3 pt-4 pb-1 text-[10px] uppercase font-bold text-slate-500 tracking-wider">Master Data & Laporan</p>

        <a href="<?= base_url('admin/users.php') ?>" class="flex items-center justify-between px-3 py-2.5 rounded-lg transition <?= $activeMenu === 'users' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-users w-4 text-center <?= $activeMenu === 'users' ? 'text-white' : 'text-sky-400' ?>"></i>
                <span>Data Pengguna (Pelapor)</span>
            </div>
        </a>

        <a href="<?= base_url('admin/cetak.php') ?>" class="flex items-center justify-between px-3 py-2.5 rounded-lg transition <?= $activeMenu === 'cetak' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-file-pdf w-4 text-center <?= $activeMenu === 'cetak' ? 'text-white' : 'text-sky-400' ?>"></i>
                <span>Rekap & Cetak Laporan</span>
            </div>
        </a>

        <div class="border-t border-slate-800 my-4 pt-3">
            <a href="<?= base_url('index.php') ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-sky-300 hover:bg-slate-800 transition">
                <i class="fa-solid fa-globe w-4 text-center"></i>
                <span>Lihat Web Utama</span>
            </a>
            <a href="<?= base_url('auth/logout.php') ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-rose-400 hover:text-rose-300 hover:bg-rose-950/30 transition mt-1">
                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>
                <span>Keluar (Logout)</span>
            </a>
        </div>
    </nav>
</aside>

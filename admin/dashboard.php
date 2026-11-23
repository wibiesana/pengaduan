<?php
// admin/dashboard.php
require_once __DIR__ . '/../config/helpers.php';
require_role(['admin', 'petugas']);

$db = get_db_connection();
$user = current_user();

// 1. Statistik Ringkasan
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_laporan,
        SUM(CASE WHEN jenis_laporan = 'umum' THEN 1 ELSE 0 END) as total_umum,
        SUM(CASE WHEN jenis_laporan = 'kekerasan_bullying' THEN 1 ELSE 0 END) as total_bullying,
        SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as status_menunggu,
        SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as status_diproses,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as status_selesai,
        SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as status_ditolak,
        SUM(CASE WHEN jenis_laporan = 'kekerasan_bullying' AND status IN ('menunggu', 'diproses') THEN 1 ELSE 0 END) as bullying_aktif
    FROM pengaduan
");
$stat = $stmt->fetch();

// 2. Statistik User Terdaftar
$stmtUsers = $db->query("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN tipe_pelapor = 'siswa' THEN 1 ELSE 0 END) as total_siswa,
        SUM(CASE WHEN tipe_pelapor = 'wali_murid' THEN 1 ELSE 0 END) as total_wali,
        SUM(CASE WHEN tipe_pelapor = 'masyarakat' THEN 1 ELSE 0 END) as total_masyarakat
    FROM users WHERE role = 'pelapor'
");
$userStats = $stmtUsers->fetch();

// 3. Laporan yang Membutuhkan Perhatian Segera (Prioritas Tinggi / Darurat / Menunggu)
$stmtPerlu = $db->query("
    SELECT p.*, k.nama_kategori, u.nama_lengkap as nama_pelapor, u.tipe_pelapor
    FROM pengaduan p
    LEFT JOIN kategori_pengaduan k ON p.kategori_id = k.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.status IN ('menunggu', 'diproses')
    ORDER BY 
        CASE WHEN p.jenis_laporan = 'kekerasan_bullying' THEN 1 ELSE 2 END,
        CASE WHEN p.prioritas = 'darurat' THEN 1 WHEN p.prioritas = 'tinggi' THEN 2 ELSE 3 END,
        p.id DESC
    LIMIT 6
");
$laporanPerlu = $stmtPerlu->fetchAll();

$pageTitle = 'Dashboard Admin';
$activeMenu = 'dashboard';
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
                            'yellow-hover': '#d97706',
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
        <!-- Top bar -->
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-30 shadow-xs">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-black text-slate-900 tracking-tight">Dashboard Ringkasan Pengaduan</h1>
                <span class="hidden sm:inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-100 text-sky-800 border border-sky-200">
                    TPPK & Layanan Sekolah
                </span>
            </div>

            <div class="flex items-center gap-4">
                <a href="<?= base_url('admin/laporan.php?status=menunggu') ?>" class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Laporan Menunggu">
                    <i class="fa-solid fa-bell text-lg"></i>
                    <?php if ($stat['status_menunggu'] > 0): ?>
                        <span class="absolute top-1 right-1 w-2.5 h-2.5 rounded-full bg-amber-500 ring-2 ring-white"></span>
                    <?php endif; ?>
                </a>

                <div class="h-6 w-px bg-slate-200"></div>

                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-smk-blue text-white flex items-center justify-center font-bold text-xs">
                        <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                    </div>
                    <span class="text-xs font-bold text-slate-700 hidden sm:inline"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                </div>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="p-6 sm:p-8 space-y-8">
            
            <!-- Alert Banner jika ada laporan Bullying aktif -->
            <?php if ($stat['bullying_aktif'] > 0): ?>
                <div class="p-4 rounded-2xl bg-gradient-to-r from-rose-600 to-rose-700 text-white shadow-lg flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-triangle-exclamation text-amber-300 animate-bounce"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-sm">Perhatian: Ada <?= $stat['bullying_aktif'] ?> Kasus Bullying / Kekerasan Perlu Tindakan!</h4>
                            <p class="text-xs text-rose-100 mt-0.5">Segera tinjau kronologi kejadian dan lakukan koordinasi perlindungan korban.</p>
                        </div>
                    </div>
                    <a href="<?= base_url('admin/laporan.php?jenis=kekerasan_bullying&status=menunggu') ?>" class="px-4 py-2 bg-white text-rose-700 font-extrabold text-xs rounded-xl shadow hover:bg-rose-50 transition whitespace-nowrap">
                        Tindak Lanjut Kasus
                    </a>
                </div>
            <?php endif; ?>

            <!-- 4 Kartu Metrik Utama -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Total Laporan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Pengaduan</p>
                        <p class="text-3xl font-black text-slate-900 mt-1"><?= number_format($stat['total_laporan']) ?></p>
                        <p class="text-[11px] text-slate-500 mt-1">
                            <span class="text-sky-600 font-bold"><?= $stat['total_umum'] ?></span> Umum • 
                            <span class="text-rose-600 font-bold"><?= $stat['total_bullying'] ?></span> Bullying
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 text-smk-blue flex items-center justify-center text-xl">
                        <i class="fa-solid fa-inbox"></i>
                    </div>
                </div>

                <!-- Menunggu Verifikasi -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Menunggu Tindakan</p>
                        <p class="text-3xl font-black text-amber-600 mt-1"><?= number_format($stat['status_menunggu']) ?></p>
                        <a href="<?= base_url('admin/laporan.php?status=menunggu') ?>" class="text-[11px] text-amber-700 font-bold hover:underline mt-1 inline-block">
                            Lihat antrean baru &rarr;
                        </a>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>

                <!-- Sedang Ditindaklanjuti -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sedang Diproses</p>
                        <p class="text-3xl font-black text-sky-600 mt-1"><?= number_format($stat['status_diproses']) ?></p>
                        <a href="<?= base_url('admin/laporan.php?status=diproses') ?>" class="text-[11px] text-sky-700 font-bold hover:underline mt-1 inline-block">
                            Pantau pengerjaan &rarr;
                        </a>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                    </div>
                </div>

                <!-- Selesai -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Selesai Ditangani</p>
                        <p class="text-3xl font-black text-emerald-600 mt-1"><?= number_format($stat['status_selesai']) ?></p>
                        <p class="text-[11px] text-slate-500 mt-1">
                            <?= $stat['total_laporan'] > 0 ? round(($stat['status_selesai'] / $stat['total_laporan']) * 100) : 0 ?>% Tingkat Penyelesaian
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
            </div>

            <!-- Perbandingan 2 Kategori Utama: Umum vs Bullying -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Kartu Pengaduan Umum -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold">
                                <i class="fa-solid fa-bullhorn"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-base text-slate-900">Pengaduan Umum & Sarpras</h3>
                                <p class="text-xs text-slate-500">Fasilitas kelas, bengkel, lingkungan sekolah</p>
                            </div>
                        </div>
                        <span class="text-xl font-black text-sky-700"><?= $stat['total_umum'] ?></span>
                    </div>
                    <div class="p-3 bg-sky-50 rounded-xl text-xs text-sky-900 flex justify-between items-center">
                        <span>Ditangani oleh Bagian Sarpras & Tata Usaha</span>
                        <a href="<?= base_url('admin/laporan.php?jenis=umum') ?>" class="font-bold text-sky-700 hover:underline">
                            Lihat Daftar &rarr;
                        </a>
                    </div>
                </div>

                <!-- Kartu Laporan Kekerasan & Bullying -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold">
                                <i class="fa-solid fa-shield-cat"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-base text-slate-900">Kekerasan & Bullying (TPPK)</h3>
                                <p class="text-xs text-slate-500">Perundungan fisik, verbal, pemalakan, cyberbullying</p>
                            </div>
                        </div>
                        <span class="text-xl font-black text-rose-700"><?= $stat['total_bullying'] ?></span>
                    </div>
                    <div class="p-3 bg-rose-50 rounded-xl text-xs text-rose-900 flex justify-between items-center">
                        <span>Ditangani Satgas Pencegahan Kekerasan & BK</span>
                        <a href="<?= base_url('admin/laporan.php?jenis=kekerasan_bullying') ?>" class="font-bold text-rose-700 hover:underline">
                            Lihat Daftar &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tabel Laporan Perlu Tindakan Segera -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Antrean Laporan Prioritas Ditindaklanjuti</h3>
                        <p class="text-xs text-slate-500">Menampilkan laporan yang belum selesai ditangani</p>
                    </div>
                    <a href="<?= base_url('admin/laporan.php') ?>" class="text-xs font-bold text-smk-blue hover:text-smk-blue-dark">
                        Buka Semua Laporan (<?= $stat['total_laporan'] ?>) &rarr;
                    </a>
                </div>

                <?php if (empty($laporanPerlu)): ?>
                    <div class="p-12 text-center text-slate-400 text-xs">
                        <i class="fa-solid fa-circle-check text-3xl text-emerald-500 mb-2"></i>
                        <p class="font-bold text-slate-700">Semua Laporan Telah Ditangani!</p>
                        <p class="text-slate-500">Tidak ada pengaduan yang berstatus menunggu atau diproses saat ini.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th class="py-3.5 px-6">Tiket & Tanggal</th>
                                    <th class="py-3.5 px-6">Pelapor</th>
                                    <th class="py-3.5 px-6">Jenis & Kategori</th>
                                    <th class="py-3.5 px-6">Judul Laporan</th>
                                    <th class="py-3.5 px-6">Prioritas</th>
                                    <th class="py-3.5 px-6">Status</th>
                                    <th class="py-3.5 px-6 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                <?php foreach ($laporanPerlu as $lap): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-4 px-6">
                                            <span class="font-mono font-bold text-slate-700 block"><?= htmlspecialchars($lap['kode_tiket']) ?></span>
                                            <span class="text-[11px] text-slate-400"><?= date('d/m/Y', strtotime($lap['created_at'])) ?></span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="font-bold text-slate-800 block">
                                                <?= $lap['status_privasi'] === 'rahasia' ? '<i class="fa-solid fa-lock text-rose-500 mr-1" title="Laporan Rahasia"></i>' : '' ?>
                                                <?= htmlspecialchars($lap['nama_pelapor']) ?>
                                            </span>
                                            <span class="text-[10px] text-slate-400 capitalize"><?= htmlspecialchars($lap['tipe_pelapor']) ?></span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <?= render_jenis_badge($lap['jenis_laporan']) ?>
                                            <span class="text-[11px] text-slate-500 block mt-1"><?= htmlspecialchars($lap['nama_kategori'] ?? 'Umum') ?></span>
                                        </td>
                                        <td class="py-4 px-6 max-w-xs">
                                            <span class="font-bold text-slate-900 block truncate"><?= htmlspecialchars($lap['judul']) ?></span>
                                            <span class="text-[11px] text-slate-400 block truncate"><?= htmlspecialchars($lap['lokasi_kejadian']) ?></span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= in_array($lap['prioritas'], ['darurat', 'tinggi']) ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700' ?>">
                                                <?= $lap['prioritas'] ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <?= render_status_badge($lap['status']) ?>
                                        </td>
                                        <td class="py-4 px-6 text-center">
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

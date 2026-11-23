<?php
// pelapor/detail.php
require_once __DIR__ . '/../config/helpers.php';
require_login();

$user = current_user();
$db = get_db_connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    set_flash('danger', 'Laporan tidak ditemukan.');
    header('Location: ' . base_url('pelapor/dashboard.php'));
    exit;
}

// Ambil pengaduan
$stmt = $db->prepare("
    SELECT p.*, k.nama_kategori, u.nama_lengkap as nama_pelapor, u.tipe_pelapor, u.no_telp as telp_pelapor, u.email as email_pelapor
    FROM pengaduan p
    LEFT JOIN kategori_pengaduan k ON p.kategori_id = k.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$lap = $stmt->fetch();

if (!$lap) {
    set_flash('danger', 'Laporan tidak ditemukan.');
    header('Location: ' . base_url('pelapor/dashboard.php'));
    exit;
}

// Hak Akses: Hanya pemilik atau admin/petugas yang boleh lihat
if ($lap['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'petugas'])) {
    set_flash('danger', 'Anda tidak memiliki hak untuk melihat detail laporan ini.');
    header('Location: ' . base_url('pelapor/dashboard.php'));
    exit;
}

// Ambil riwayat tindak lanjut
$stmtTL = $db->prepare("
    SELECT tl.*, u.nama_lengkap as nama_petugas, u.role as role_petugas
    FROM tindak_lanjut tl
    JOIN users u ON tl.petugas_id = u.id
    WHERE tl.pengaduan_id = ?
    ORDER BY tl.id ASC
");
$stmtTL->execute([$id]);
$tindakLanjutList = $stmtTL->fetchAll();

// Hitung status stepper
$currentStatus = $lap['status']; // 'menunggu', 'diproses', 'selesai', 'ditolak'

$pageTitle = 'Detail Laporan ' . $lap['kode_tiket'];
$activeNav = 'pelapor_dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-8 sm:py-12 px-4 sm:px-6 lg:px-8 bg-slate-50 min-h-[85vh]">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <!-- Top Action & Breadcrumb -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <nav class="flex items-center gap-2 text-xs text-slate-500 font-medium">
                <a href="<?= base_url('index.php') ?>" class="hover:text-smk-blue">Beranda</a>
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                <a href="<?= base_url($user['role'] === 'pelapor' ? 'pelapor/dashboard.php' : 'admin/laporan.php') ?>" class="hover:text-smk-blue">
                    <?= $user['role'] === 'pelapor' ? 'Riwayat Pengaduan' : 'Kelola Laporan' ?>
                </a>
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                <span class="text-slate-900 font-bold font-mono"><?= htmlspecialchars($lap['kode_tiket']) ?></span>
            </nav>

            <div class="flex flex-wrap items-center gap-2.5">
                <a href="<?= base_url('pelapor/cetak-tiket.php?id=' . $lap['id']) ?>" target="_blank" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white text-slate-700 hover:text-smk-blue hover:border-sky-300 border border-slate-200 shadow-xs transition flex items-center gap-1.5">
                    <i class="fa-solid fa-print"></i>
                    <span>Cetak Tanda Terima</span>
                </a>

                <?php if ($lap['user_id'] == $user['id'] && $lap['status'] === 'menunggu'): ?>
                    <a href="<?= base_url('pelapor/edit-laporan.php?id=' . $lap['id']) ?>" class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-400 hover:bg-amber-300 text-slate-950 shadow-xs transition flex items-center gap-1.5">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span>Edit Laporan / Foto</span>
                    </a>
                <?php elseif ($lap['user_id'] == $user['id']): ?>
                    <span class="px-3 py-1.5 rounded-xl text-[11px] font-semibold bg-slate-100 text-slate-500 border border-slate-200" title="Laporan yang sedang atau sudah diproses terkunci demi keaslian data penanganan">
                        <i class="fa-solid fa-lock text-[10px] mr-1"></i> Data Terkunci
                    </span>
                <?php endif; ?>

                <?php if (in_array($user['role'], ['admin', 'petugas'])): ?>
                    <a href="<?= base_url('admin/detail.php?id=' . $lap['id']) ?>" class="px-4 py-2 rounded-xl text-xs font-extrabold bg-amber-400 text-slate-950 hover:bg-amber-300 shadow-xs transition flex items-center gap-1.5">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                        <span>Panel Tindak Lanjut Admin</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            
            <!-- Header Banner -->
            <div class="p-6 sm:p-8 <?= $lap['jenis_laporan'] === 'kekerasan_bullying' ? 'bg-gradient-to-r from-rose-950 via-rose-900 to-slate-900' : 'bg-gradient-to-r from-sky-950 via-sky-900 to-slate-900' ?> text-white">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-white/15 backdrop-blur border border-white/20 text-white">
                            <?= htmlspecialchars($lap['kode_tiket']) ?>
                        </span>
                        <?= render_jenis_badge($lap['jenis_laporan']) ?>
                        <?php if ($lap['status_privasi'] === 'rahasia'): ?>
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-slate-950/80 text-amber-300 border border-amber-300/30">
                                <i class="fa-solid fa-lock text-[9px]"></i> Rahasia / TPPK
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?= render_status_badge($lap['status']) ?>
                    </div>
                </div>

                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white leading-snug">
                    <?= htmlspecialchars($lap['judul']) ?>
                </h1>

                <div class="text-xs text-slate-300 mt-3 flex items-center gap-4 flex-wrap pt-2 border-t border-white/10">
                    <span class="flex items-center gap-1.5"><i class="fa-regular fa-clock text-slate-400"></i> Dikirim: <?= format_tanggal_indonesia($lap['created_at'], true) ?></span>
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-calendar-day text-slate-400"></i> Kejadian: <?= format_tanggal_indonesia($lap['tanggal_kejadian']) ?></span>
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-location-dot text-slate-400"></i> <?= htmlspecialchars($lap['lokasi_kejadian']) ?></span>
                </div>
            </div>

            <!-- Visual Stepper Progress Bar -->
            <div class="p-6 bg-slate-50/80 border-b border-slate-200">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-4 text-center sm:text-left">
                    Tahapan Penanganan Laporan:
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                    
                    <!-- Step 1: Laporan Diterima -->
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white border border-emerald-200 shadow-xs">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-bold text-xs shrink-0">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div>
                            <span class="font-extrabold text-slate-900 block leading-tight">1. Terkirim</span>
                            <span class="text-[10px] text-emerald-600 font-semibold">Tersimpan di sistem</span>
                        </div>
                    </div>

                    <!-- Step 2: Verifikasi Petugas -->
                    <?php 
                        $step2Done = in_array($currentStatus, ['diproses', 'selesai', 'ditolak']);
                        $step2Active = ($currentStatus === 'menunggu');
                    ?>
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white border <?= $step2Done ? 'border-emerald-200' : ($step2Active ? 'border-amber-400 bg-amber-50/40' : 'border-slate-200 opacity-60') ?> shadow-xs">
                        <div class="w-8 h-8 rounded-xl <?= $step2Done ? 'bg-emerald-500 text-white' : ($step2Active ? 'bg-amber-500 text-white animate-pulse' : 'bg-slate-200 text-slate-500') ?> flex items-center justify-center font-bold text-xs shrink-0">
                            <i class="<?= $step2Done ? 'fa-solid fa-check' : 'fa-solid fa-hourglass-half' ?>"></i>
                        </div>
                        <div>
                            <span class="font-extrabold text-slate-900 block leading-tight">2. Verifikasi</span>
                            <span class="text-[10px] <?= $step2Done ? 'text-emerald-600' : ($step2Active ? 'text-amber-600 font-bold' : 'text-slate-400') ?>">
                                <?= $step2Done ? 'Sudah ditinjau' : ($step2Active ? 'Dalam antrean' : 'Menunggu') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Step 3: Tindak Lanjut -->
                    <?php 
                        $step3Done = ($currentStatus === 'selesai');
                        $step3Active = ($currentStatus === 'diproses');
                    ?>
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white border <?= $step3Done ? 'border-emerald-200' : ($step3Active ? 'border-sky-400 bg-sky-50/40' : 'border-slate-200 opacity-60') ?> shadow-xs">
                        <div class="w-8 h-8 rounded-xl <?= $step3Done ? 'bg-emerald-500 text-white' : ($step3Active ? 'bg-sky-500 text-white' : 'bg-slate-200 text-slate-500') ?> flex items-center justify-center font-bold text-xs shrink-0">
                            <i class="<?= $step3Done ? 'fa-solid fa-check' : ($step3Active ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-screwdriver-wrench') ?>"></i>
                        </div>
                        <div>
                            <span class="font-extrabold text-slate-900 block leading-tight">3. Penanganan</span>
                            <span class="text-[10px] <?= $step3Done ? 'text-emerald-600' : ($step3Active ? 'text-sky-600 font-bold' : 'text-slate-400') ?>">
                                <?= $step3Done ? 'Tindakan selesai' : ($step3Active ? 'Sedang dikerjakan' : 'Belum dimulai') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Step 4: Selesai / Ditolak -->
                    <?php 
                        $step4Done = ($currentStatus === 'selesai');
                        $step4Rejected = ($currentStatus === 'ditolak');
                    ?>
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white border <?= $step4Done ? 'border-emerald-200' : ($step4Rejected ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 opacity-60') ?> shadow-xs">
                        <div class="w-8 h-8 rounded-xl <?= $step4Done ? 'bg-emerald-600 text-white' : ($step4Rejected ? 'bg-rose-600 text-white' : 'bg-slate-200 text-slate-500') ?> flex items-center justify-center font-bold text-xs shrink-0">
                            <i class="<?= $step4Done ? 'fa-solid fa-circle-check' : ($step4Rejected ? 'fa-solid fa-circle-xmark' : 'fa-solid fa-flag-checkered') ?>"></i>
                        </div>
                        <div>
                            <span class="font-extrabold text-slate-900 block leading-tight">4. Hasil</span>
                            <span class="text-[10px] <?= $step4Done ? 'text-emerald-600 font-bold' : ($step4Rejected ? 'text-rose-600 font-bold' : 'text-slate-400') ?>">
                                <?= $step4Done ? 'Selesai tuntas' : ($step4Rejected ? 'Laporan ditolak' : 'Menunggu hasil') ?>
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Content Details -->
            <div class="p-6 sm:p-8 space-y-8">
                
                <!-- Quick Meta Badges -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200/70 text-xs">
                    <div>
                        <span class="text-slate-400 font-bold block text-[10px] uppercase">Kategori</span>
                        <span class="font-extrabold text-slate-900 mt-0.5 block"><?= htmlspecialchars($lap['nama_kategori'] ?? 'Umum') ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold block text-[10px] uppercase">Privasi Laporan</span>
                        <span class="font-extrabold mt-0.5 block <?= $lap['status_privasi'] === 'rahasia' ? 'text-rose-600' : 'text-slate-800' ?>">
                            <i class="<?= $lap['status_privasi'] === 'rahasia' ? 'fa-solid fa-lock' : 'fa-solid fa-globe' ?> mr-1 text-[11px]"></i>
                            <?= ucfirst($lap['status_privasi']) ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold block text-[10px] uppercase">Tingkat Prioritas</span>
                        <span class="font-extrabold uppercase mt-0.5 block tracking-wide <?= $lap['prioritas'] === 'darurat' || $lap['prioritas'] === 'tinggi' ? 'text-rose-600' : 'text-slate-800' ?>">
                            <?= htmlspecialchars($lap['prioritas']) ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold block text-[10px] uppercase">Status Pelapor</span>
                        <span class="font-extrabold text-slate-900 mt-0.5 block capitalize"><?= htmlspecialchars($lap['tipe_pelapor'] ?? 'Pelapor') ?></span>
                    </div>
                </div>

                <!-- Isi Deskripsi Laporan -->
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                        Deskripsi / Rincian Laporan
                    </h3>
                    <div class="bg-slate-50/80 p-5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-800 leading-relaxed whitespace-pre-line">
                        <?= htmlspecialchars($lap['isi_laporan']) ?>
                    </div>
                </div>

                <!-- Foto Bukti -->
                <?php if (!empty($lap['foto_bukti'])): ?>
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                            Foto Bukti Pendukung
                        </h3>
                        <div class="inline-block relative rounded-2xl overflow-hidden border border-slate-200 group shadow-xs">
                            <img src="<?= base_url('uploads/' . $lap['foto_bukti']) ?>" alt="Foto Bukti" class="max-h-72 object-cover rounded-2xl group-hover:scale-105 transition duration-300">
                            <a href="<?= base_url('uploads/' . $lap['foto_bukti']) ?>" target="_blank" class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-bold gap-1.5">
                                <i class="fa-solid fa-magnifying-glass-plus text-base"></i>
                                <span>Perbesar Foto</span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Khusus Kasus Kekerasan/Bullying: Hotline Banner -->
                <?php if ($lap['jenis_laporan'] === 'kekerasan_bullying'): ?>
                    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 text-xs">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center text-base shrink-0">
                                <i class="fa-solid fa-shield-cat"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-rose-900">Perlindungan Saksi & Korban TPPK</h4>
                                <p class="text-[11px] text-rose-700">Jika Anda membutuhkan pendampingan konseling tatap muka segera, hubungi Satgas.</p>
                            </div>
                        </div>
                        <a href="https://wa.me/6281234567890?text=Halo%20TPPK%20SMKN%201%20Cipeundeuy,%20saya%20pelapor%20dengan%20Kode%20Tiket:%20<?= urlencode($lap['kode_tiket']) ?>" target="_blank" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 whitespace-nowrap">
                            <i class="fa-brands fa-whatsapp text-sm"></i>
                            <span>Chat Satgas TPPK</span>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Progres Tracker / Timeline Tindak Lanjut -->
                <div class="pt-6 border-t border-slate-200">
                    <h3 class="text-sm font-black text-slate-900 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-timeline text-smk-blue"></i>
                        <span>Progres & Riwayat Tindak Lanjut Sekolah</span>
                    </h3>

                    <?php if (empty($tindakLanjutList)): ?>
                        <div class="p-5 rounded-2xl bg-amber-50/70 border border-amber-200 text-amber-900 text-xs flex items-center gap-3">
                            <i class="fa-solid fa-clock-rotate-left text-amber-600 text-lg flex-shrink-0"></i>
                            <div>
                                <p class="font-bold">Laporan sedang dalam antrean verifikasi petugas.</p>
                                <p class="text-[11px] text-amber-700 mt-0.5">
                                    Tim Satgas TPPK / Bagian Terkait SMKN 1 Cipeundeuy akan segera meninjau dan memperbarui catatan tindak lanjut di sini.
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="relative pl-6 space-y-6 before:content-[''] before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                            <?php foreach ($tindakLanjutList as $tl): ?>
                                <div class="relative group">
                                    <!-- Dot on the line -->
                                    <div class="absolute -left-6 top-1 w-4 h-4 rounded-full bg-white border-4 border-smk-blue shadow-xs"></div>

                                    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs space-y-2">
                                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                            <div class="flex items-center gap-2">
                                                <span class="font-extrabold text-slate-900"><?= htmlspecialchars($tl['nama_petugas']) ?></span>
                                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-sky-100 text-sky-800">
                                                    <?= htmlspecialchars($tl['role_petugas']) ?>
                                                </span>
                                            </div>
                                            <span class="text-[11px] text-slate-400">
                                                <i class="fa-regular fa-clock mr-1"></i> <?= format_tanggal_indonesia($tl['created_at'], true) ?>
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-2 text-xs font-semibold">
                                            <span class="text-slate-400">Perubahan Status:</span>
                                            <?= render_status_badge($tl['status_terbaru']) ?>
                                        </div>

                                        <p class="text-xs sm:text-sm text-slate-700 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-100 whitespace-pre-line">
                                            <?= htmlspecialchars($tl['catatan']) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

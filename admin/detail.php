<?php
// admin/detail.php
require_once __DIR__ . '/../config/helpers.php';
require_role(['admin', 'petugas']);

$db = get_db_connection();
$user = current_user();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    set_flash('danger', 'Laporan tidak valid.');
    header('Location: ' . base_url('admin/laporan.php'));
    exit;
}

// Ambil pengaduan beserta data pelapor
$stmt = $db->prepare("
    SELECT p.*, k.nama_kategori, 
           u.nama_lengkap as nama_pelapor, u.username as username_pelapor, u.tipe_pelapor, 
           u.email as email_pelapor, u.no_telp as telp_pelapor, u.nomor_identitas, u.alamat as alamat_pelapor
    FROM pengaduan p
    LEFT JOIN kategori_pengaduan k ON p.kategori_id = k.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$lap = $stmt->fetch();

if (!$lap) {
    set_flash('danger', 'Laporan tidak ditemukan.');
    header('Location: ' . base_url('admin/laporan.php'));
    exit;
}

$error = '';
$success = '';

// Proses Submit Tindak Lanjut
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status_baru = $_POST['status_terbaru'] ?? '';
    $catatan     = trim($_POST['catatan'] ?? '');

    if (!in_array($status_baru, ['menunggu', 'diproses', 'selesai', 'ditolak'])) {
        $error = 'Pilihan status tidak valid.';
    } elseif (empty($catatan)) {
        $error = 'Catatan tindak lanjut / keterangan penanganan wajib diisi!';
    } else {
        $status_lama = $lap['status'];

        // 1. Simpan ke tabel tindak_lanjut
        $stmtTL = $db->prepare("
            INSERT INTO tindak_lanjut (pengaduan_id, petugas_id, status_sebelumnya, status_terbaru, catatan)
            VALUES (?, ?, ?, ?, ?)
        ");
        $saved = $stmtTL->execute([$id, $user['id'], $status_lama, $status_baru, $catatan]);

        // 2. Update status pengaduan
        $stmtUp = $db->prepare("UPDATE pengaduan SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmtUp->execute([$status_baru, $id]);

        if ($saved) {
            set_flash('success', 'Tindak lanjut laporan berhasil disimpan dan status diperbarui menjadi "' . strtoupper($status_baru) . '".');
            header('Location: ' . base_url('admin/detail.php?id=' . $id));
            exit;
        } else {
            $error = 'Gagal menyimpan riwayat tindak lanjut.';
        }
    }
}

// Ambil riwayat tindak lanjut
$stmtHistory = $db->prepare("
    SELECT tl.*, u.nama_lengkap as nama_petugas, u.role as role_petugas
    FROM tindak_lanjut tl
    JOIN users u ON tl.petugas_id = u.id
    WHERE tl.pengaduan_id = ?
    ORDER BY tl.id DESC
");
$stmtHistory->execute([$id]);
$history = $stmtHistory->fetchAll();

$pageTitle = 'Tindak Lanjut Laporan ' . $lap['kode_tiket'];
$activeMenu = $lap['jenis_laporan'] === 'kekerasan_bullying' ? 'laporan_bullying' : 'semua_laporan';
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
        <!-- Top bar -->
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 sticky top-0 z-30 shadow-xs">
            <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
                <a href="<?= base_url('admin/dashboard.php') ?>" class="hover:text-smk-blue">Dashboard</a>
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                <a href="<?= base_url('admin/laporan.php') ?>" class="hover:text-smk-blue">Semua Laporan</a>
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                <span class="text-slate-800 font-bold"><?= htmlspecialchars($lap['kode_tiket']) ?></span>
            </div>

            <div class="flex items-center gap-2">
                <a href="<?= base_url('pelapor/cetak-tiket.php?id=' . $lap['id']) ?>" target="_blank" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center gap-1.5 border border-slate-200">
                    <i class="fa-solid fa-print"></i> Cetak Dokumen
                </a>
                <a href="<?= base_url('admin/laporan.php') ?>" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition border border-slate-200">
                    &larr; Kembali
                </a>
            </div>
        </header>

        <!-- Body -->
        <main class="p-6 sm:p-8 space-y-6 max-w-6xl">
            
            <?php if (!empty($error)): ?>
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2.5">
                    <i class="fa-solid fa-triangle-exclamation text-rose-500 text-base"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Banner Header Laporan -->
            <div class="rounded-3xl p-6 sm:p-8 text-white shadow-md <?= $lap['jenis_laporan'] === 'kekerasan_bullying' ? 'bg-gradient-to-r from-rose-950 via-rose-900 to-slate-900' : 'bg-gradient-to-r from-sky-950 via-sky-900 to-slate-900' ?>">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-bold text-xs px-3 py-1 rounded-full bg-white/15 backdrop-blur border border-white/20">
                            <?= htmlspecialchars($lap['kode_tiket']) ?>
                        </span>
                        <?= render_jenis_badge($lap['jenis_laporan']) ?>
                        <?php if ($lap['status_privasi'] === 'rahasia'): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-400 text-slate-950">
                                <i class="fa-solid fa-user-shield"></i> Pelapor Anonim / Rahasia
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?= render_status_badge($lap['status']) ?>
                    </div>
                </div>

                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                    <?= htmlspecialchars($lap['judul']) ?>
                </h1>

                <p class="text-xs text-slate-300 mt-2 flex items-center gap-4 flex-wrap">
                    <span><i class="fa-regular fa-clock mr-1 text-slate-400"></i> Masuk: <?= format_tanggal_indonesia($lap['created_at'], true) ?></span>
                    <span><i class="fa-solid fa-calendar-day mr-1 text-slate-400"></i> Kejadian: <?= format_tanggal_indonesia($lap['tanggal_kejadian']) ?></span>
                    <span><i class="fa-solid fa-location-dot mr-1 text-slate-400"></i> <?= htmlspecialchars($lap['lokasi_kejadian']) ?></span>
                </p>
            </div>

            <!-- Grid 2 Kolom: Detail Laporan & Form Tindak Lanjut -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- Kolom Kiri: Rincian Laporan, Pelapor, & Bukti (7 col) -->
                <div class="lg:col-span-7 space-y-6">
                    <!-- Rincian Isi -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                            Kronologi & Deskripsi Pengaduan
                        </h3>
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-800 leading-relaxed whitespace-pre-line">
                            <?= htmlspecialchars($lap['isi_laporan']) ?>
                        </div>

                        <!-- Foto Bukti -->
                        <?php if (!empty($lap['foto_bukti'])): ?>
                            <div class="pt-2">
                                <h4 class="text-xs font-bold uppercase text-slate-400 mb-2">Foto / Dokumen Bukti Kejadian:</h4>
                                <div class="inline-block relative rounded-2xl overflow-hidden border border-slate-200 shadow-xs group">
                                    <img src="<?= base_url('uploads/' . $lap['foto_bukti']) ?>" alt="Bukti" class="max-h-80 object-cover rounded-2xl group-hover:scale-105 transition duration-300">
                                    <a href="<?= base_url('uploads/' . $lap['foto_bukti']) ?>" target="_blank" class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-bold gap-1.5">
                                        <i class="fa-solid fa-magnifying-glass-plus text-base"></i>
                                        <span>Buka Ukuran Asli</span>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-xs text-slate-400 italic">Pelapor tidak melampirkan foto bukti.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Identitas Pelapor (Khusus Admin/Petugas) -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                                <i class="fa-solid fa-id-card text-sky-600"></i> Informasi Pelapor
                            </h3>
                            <?php if ($lap['status_privasi'] === 'rahasia'): ?>
                                <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                                    <i class="fa-solid fa-lock"></i> Rahasia untuk Publik
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Nama Lengkap</span>
                                <span class="font-extrabold text-slate-900 text-sm"><?= htmlspecialchars($lap['nama_pelapor']) ?></span>
                                <span class="text-[11px] text-sky-700 capitalize font-medium block">Role: <?= htmlspecialchars($lap['tipe_pelapor']) ?></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Kontak WhatsApp</span>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lap['telp_pelapor']) ?>" target="_blank" class="font-bold text-emerald-600 hover:underline flex items-center gap-1 mt-0.5">
                                    <i class="fa-brands fa-whatsapp text-sm"></i> <?= htmlspecialchars($lap['telp_pelapor']) ?>
                                </a>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">NISN / NIK</span>
                                <span class="font-mono text-slate-700"><?= htmlspecialchars($lap['nomor_identitas'] ?: '-') ?></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Alamat / Keterangan</span>
                                <span class="text-slate-700"><?= htmlspecialchars($lap['alamat_pelapor'] ?: '-') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Form Tindak Lanjut & Riwayat (5 col) -->
                <div class="lg:col-span-5 space-y-6">
                    
                    <!-- Form Input Tindak Lanjut -->
                    <div class="bg-white p-6 rounded-3xl border-2 border-sky-200 shadow-md">
                        <div class="flex items-center gap-2.5 mb-4">
                            <div class="w-8 h-8 rounded-xl bg-smk-blue text-white flex items-center justify-center text-sm">
                                <i class="fa-solid fa-screwdriver-wrench"></i>
                            </div>
                            <div>
                                <h3 class="font-black text-sm text-slate-900">Form Tindak Lanjut Laporan</h3>
                                <p class="text-[11px] text-slate-500">Perbarui status dan berikan catatan resmi</p>
                            </div>
                        </div>

                        <form method="POST" action="" class="space-y-4">
                            <div>
                                <label for="status_terbaru" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Perbarui Status Penanganan <span class="text-rose-500">*</span>
                                </label>
                                <select id="status_terbaru" name="status_terbaru" required
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                                    <option value="menunggu" <?= $lap['status'] === 'menunggu' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                                    <option value="diproses" <?= $lap['status'] === 'diproses' ? 'selected' : '' ?>>Sedang Ditindaklanjuti (Diproses)</option>
                                    <option value="selesai" <?= $lap['status'] === 'selesai' ? 'selected' : '' ?>>Selesai Ditangani</option>
                                    <option value="ditolak" <?= $lap['status'] === 'ditolak' ? 'selected' : '' ?>>Tolak Laporan (Tidak Valid / Fiktif)</option>
                                </select>
                            </div>

                            <div>
                                <label for="catatan" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Catatan / Tanggapan Tindak Lanjut <span class="text-rose-500">*</span>
                                </label>
                                <textarea id="catatan" name="catatan" rows="4" required
                                    placeholder="Tuliskan tindakan yang telah atau akan diambil oleh sekolah (misal: koordinasi dengan wali kelas, pemanggilan saksi, perbaikan fasilitas oleh teknisi, dsb)..."
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition leading-relaxed"></textarea>
                                <p class="text-[10px] text-slate-400 mt-1">
                                    Catatan ini akan dapat dilihat oleh pelapor pada halaman pelacakan status.
                                </p>
                            </div>

                            <button type="submit" class="w-full py-3 bg-smk-blue hover:bg-smk-blue-hover text-white font-extrabold text-xs rounded-xl shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Simpan & Kirim Tindak Lanjut</span>
                            </button>
                        </form>
                    </div>

                    <!-- Riwayat Tindak Lanjut Sebelumnya -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-1.5">
                            <i class="fa-solid fa-history text-slate-400"></i> Log Riwayat Tindak Lanjut
                        </h3>

                        <?php if (empty($history)): ?>
                            <div class="p-6 text-center text-slate-400 text-xs bg-slate-50 rounded-2xl border border-slate-100">
                                <i class="fa-solid fa-clock-rotate-left text-2xl mb-1 text-slate-300"></i>
                                <p class="font-semibold">Belum Ada Tindak Lanjut</p>
                                <p class="text-[11px] text-slate-400">Isi formulir di atas untuk menindaklanjuti laporan ini.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($history as $h): ?>
                                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-extrabold text-slate-900"><?= htmlspecialchars($h['nama_petugas']) ?></span>
                                            <span class="text-[10px] text-slate-400"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-[11px]">
                                            <span class="text-slate-500">Status:</span>
                                            <span class="font-bold uppercase text-slate-700"><?= $h['status_sebelumnya'] ?></span>
                                            <i class="fa-solid fa-arrow-right text-[9px] text-slate-400"></i>
                                            <?= render_status_badge($h['status_terbaru']) ?>
                                        </div>
                                        <p class="text-slate-700 leading-relaxed bg-white p-2.5 rounded-xl border border-slate-100 whitespace-pre-line text-[11px]">
                                            <?= htmlspecialchars($h['catatan']) ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            </div>

        </main>
    </div>

    <!-- Flash message SweetAlert2 -->
    <?php $flash = get_flash(); if (!empty($flash)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: '<?= $flash['type'] === 'danger' ? 'error' : 'success' ?>',
                    title: '<?= $flash['type'] === 'danger' ? 'Peringatan' : 'Berhasil!' ?>',
                    text: <?= json_encode($flash['message']) ?>,
                    confirmButtonColor: '#0284c7'
                });
            });
        </script>
    <?php endif; ?>

</body>
</html>

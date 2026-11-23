<?php
// pelapor/edit-laporan.php
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

// Ambil data pengaduan
$stmt = $db->prepare("SELECT * FROM pengaduan WHERE id = ?");
$stmt->execute([$id]);
$lap = $stmt->fetch();

if (!$lap) {
    set_flash('danger', 'Laporan tidak ditemukan.');
    header('Location: ' . base_url('pelapor/dashboard.php'));
    exit;
}

// Hak Akses: Hanya pemilik (atau admin) yang dapat mengedit
if ($lap['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'petugas'])) {
    set_flash('danger', 'Anda tidak memiliki hak untuk mengedit laporan ini.');
    header('Location: ' . base_url('pelapor/dashboard.php'));
    exit;
}

// Aturan Keamanan: Laporan hanya boleh diedit jika status masih 'menunggu'
if ($lap['status'] !== 'menunggu' && !in_array($user['role'], ['admin'])) {
    set_flash('warning', 'Laporan yang sedang diproses atau telah selesai ditangani tidak dapat diubah kembali demi menjaga validitas bukti penanganan.');
    header('Location: ' . base_url('pelapor/detail.php?id=' . $id));
    exit;
}

// Ambil semua kategori dari DB
$stmtKat = $db->query("SELECT * FROM kategori_pengaduan ORDER BY jenis_laporan, nama_kategori ASC");
$allKategori = $stmtKat->fetchAll();

$errors = [];
$selectedJenis = $_POST['jenis_laporan'] ?? $lap['jenis_laporan'];

$formData = [
    'jenis_laporan'    => $selectedJenis,
    'kategori_id'      => $_POST['kategori_id'] ?? $lap['kategori_id'],
    'judul'            => $_POST['judul'] ?? $lap['judul'],
    'tanggal_kejadian' => $_POST['tanggal_kejadian'] ?? $lap['tanggal_kejadian'],
    'lokasi_kejadian'  => $_POST['lokasi_kejadian'] ?? $lap['lokasi_kejadian'],
    'isi_laporan'      => $_POST['isi_laporan'] ?? $lap['isi_laporan'],
    'status_privasi'   => $_POST['status_privasi'] ?? $lap['status_privasi'],
    'prioritas'        => $_POST['prioritas'] ?? $lap['prioritas'],
    'foto_bukti'       => $lap['foto_bukti'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_laporan    = trim($_POST['jenis_laporan'] ?? 'umum');
    $kategori_id      = !empty($_POST['kategori_id']) ? (int)$_POST['kategori_id'] : null;
    $judul            = trim($_POST['judul'] ?? '');
    $tanggal_kejadian = trim($_POST['tanggal_kejadian'] ?? '');
    $lokasi_kejadian  = trim($_POST['lokasi_kejadian'] ?? '');
    $isi_laporan      = trim($_POST['isi_laporan'] ?? '');
    $status_privasi   = in_array($_POST['status_privasi'] ?? '', ['publik', 'rahasia']) ? $_POST['status_privasi'] : 'publik';
    $prioritas        = in_array($_POST['prioritas'] ?? '', ['rendah', 'sedang', 'tinggi', 'darurat']) ? $_POST['prioritas'] : 'sedang';
    $hapus_foto       = isset($_POST['hapus_foto']) && $_POST['hapus_foto'] === '1';

    // Update repopulation
    $formData['jenis_laporan']    = $jenis_laporan;
    $formData['kategori_id']      = $kategori_id;
    $formData['judul']            = $judul;
    $formData['tanggal_kejadian'] = $tanggal_kejadian;
    $formData['lokasi_kejadian']  = $lokasi_kejadian;
    $formData['isi_laporan']      = $isi_laporan;
    $formData['status_privasi']   = $status_privasi;
    $formData['prioritas']        = $prioritas;

    // Validasi
    if (empty($judul)) {
        $errors[] = 'Judul pengaduan/laporan wajib diisi.';
    } elseif (strlen($judul) < 5) {
        $errors[] = 'Judul laporan terlalu singkat (minimal 5 karakter).';
    }

    if (empty($tanggal_kejadian)) {
        $errors[] = 'Tanggal kejadian wajib diisi.';
    } elseif (strtotime($tanggal_kejadian) > time() + 86400) {
        $errors[] = 'Tanggal kejadian tidak boleh di masa mendatang.';
    }

    if (empty($lokasi_kejadian)) {
        $errors[] = 'Lokasi kejadian di sekolah wajib diisi.';
    }

    if (empty($isi_laporan)) {
        $errors[] = 'Kronologi peristiwa wajib diisi secara jelas.';
    } elseif (strlen($isi_laporan) < 15) {
        $errors[] = 'Deskripsi laporan terlalu singkat (minimal 15 karakter).';
    }

    // Penanganan Foto Bukti
    $finalFoto = $lap['foto_bukti'];

    if ($hapus_foto && $finalFoto) {
        $oldFilePath = __DIR__ . '/../uploads/' . $finalFoto;
        if (file_exists($oldFilePath)) {
            @unlink($oldFilePath);
        }
        $finalFoto = null;
    }

    // Jika ada unggahan berkas baru
    if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = handle_image_upload('foto_bukti');
        if (!$uploadResult['status']) {
            $errors[] = $uploadResult['error'];
        } else {
            // Hapus foto lama jika diganti
            if ($finalFoto && file_exists(__DIR__ . '/../uploads/' . $finalFoto)) {
                @unlink(__DIR__ . '/../uploads/' . $finalFoto);
            }
            $finalFoto = $uploadResult['filename'];
        }
    }

    // Eksekusi Update ke Database
    if (empty($errors)) {
        $stmtUpdate = $db->prepare("
            UPDATE pengaduan SET
                jenis_laporan = ?,
                kategori_id = ?,
                judul = ?,
                isi_laporan = ?,
                tanggal_kejadian = ?,
                lokasi_kejadian = ?,
                foto_bukti = ?,
                status_privasi = ?,
                prioritas = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $updated = $stmtUpdate->execute([
            $jenis_laporan,
            $kategori_id,
            $judul,
            $isi_laporan,
            $tanggal_kejadian,
            $lokasi_kejadian,
            $finalFoto,
            $status_privasi,
            $prioritas,
            $id
        ]);

        if ($updated) {
            set_flash('success', 'Laporan pengaduan ' . $lap['kode_tiket'] . ' berhasil diperbarui!');
            header('Location: ' . base_url('pelapor/detail.php?id=' . $id));
            exit;
        } else {
            $errors[] = 'Gagal menyimpan perubahan ke database.';
        }
    }
}

$pageTitle = 'Edit Laporan ' . $lap['kode_tiket'];
$activeNav = 'pelapor_dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-10 px-4 sm:px-6 lg:px-8 bg-slate-50 min-h-[85vh]">
    <div class="max-w-3xl mx-auto">
        
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-4 font-medium">
            <a href="<?= base_url('index.php') ?>" class="hover:text-smk-blue">Beranda</a>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
            <a href="<?= base_url('pelapor/dashboard.php') ?>" class="hover:text-smk-blue">Dashboard</a>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
            <a href="<?= base_url('pelapor/detail.php?id=' . $lap['id']) ?>" class="hover:text-smk-blue"><?= htmlspecialchars($lap['kode_tiket']) ?></a>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
            <span class="text-slate-800 font-bold">Edit Laporan</span>
        </nav>

        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-100 overflow-hidden">
            
            <!-- Header Card -->
            <div class="p-6 sm:p-8 bg-gradient-to-r from-sky-950 via-sky-900 to-slate-900 text-white">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-white/15 backdrop-blur text-white mb-2">
                            <i class="fa-solid fa-pen-to-square text-amber-300"></i>
                            <span>EDIT PENGADUAN</span>
                            <span class="font-mono text-amber-300 ml-1"><?= htmlspecialchars($lap['kode_tiket']) ?></span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                            Ubah Rincian & Tambah Foto Bukti
                        </h1>
                        <p class="text-xs sm:text-sm text-white/80 mt-1 max-w-xl">
                            Anda dapat memperbarui data kejadian, menambah atau mengganti foto bukti pendukung selama status laporan masih menunggu verifikasi.
                        </p>
                    </div>

                    <div class="bg-amber-400/20 backdrop-blur border border-amber-300/30 p-3 rounded-2xl text-xs text-amber-200 shrink-0">
                        <span class="block text-[10px] uppercase font-bold text-amber-300">Status Saat Ini:</span>
                        <span class="font-black text-white uppercase text-xs"><?= $lap['status'] ?></span>
                    </div>
                </div>
            </div>

            <div class="p-6 sm:p-8">
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                        <div class="font-bold flex items-center gap-2 mb-1.5 text-rose-800">
                            <i class="fa-solid fa-triangle-exclamation text-rose-500"></i> Harap perbaiki formulir:
                        </div>
                        <ul class="list-disc pl-5 space-y-1">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                    
                    <!-- Kategori Switcher: Umum vs Bullying -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Jenis Laporan <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label id="cardUmum" class="relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition <?= $formData['jenis_laporan'] === 'umum' ? 'border-smk-blue bg-sky-50/50 shadow-sm' : 'border-slate-200 hover:border-slate-300' ?>">
                                <input type="radio" name="jenis_laporan" value="umum" class="sr-only" <?= $formData['jenis_laporan'] === 'umum' ? 'checked' : '' ?> onchange="onJenisChange('umum')">
                                <div class="w-10 h-10 rounded-xl bg-smk-blue text-white flex items-center justify-center text-lg flex-shrink-0 mr-3">
                                    <i class="fa-solid fa-bullhorn"></i>
                                </div>
                                <div>
                                    <span class="block text-sm font-extrabold text-slate-900">Pengaduan Umum</span>
                                    <span class="block text-xs text-slate-500">Fasilitas, kebersihan, KBM, tata usaha</span>
                                </div>
                            </label>

                            <label id="cardBullying" class="relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition <?= $formData['jenis_laporan'] === 'kekerasan_bullying' ? 'border-rose-500 bg-rose-50/50 shadow-sm' : 'border-slate-200 hover:border-slate-300' ?>">
                                <input type="radio" name="jenis_laporan" value="kekerasan_bullying" class="sr-only" <?= $formData['jenis_laporan'] === 'kekerasan_bullying' ? 'checked' : '' ?> onchange="onJenisChange('kekerasan_bullying')">
                                <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center text-lg flex-shrink-0 mr-3">
                                    <i class="fa-solid fa-shield-cat"></i>
                                </div>
                                <div>
                                    <span class="block text-sm font-extrabold text-slate-900">Kekerasan / Bullying</span>
                                    <span class="block text-xs text-slate-500">Perundungan fisik, verbal, pemalakan</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Sub Kategori -->
                    <div>
                        <label for="kategori_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Topik / Sub-Kategori Spesifik
                        </label>
                        <select id="kategori_id" name="kategori_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition font-medium">
                            <option value="" id="placeholderOption">-- Pilih Topik Laporan --</option>
                            <?php foreach ($allKategori as $kat): ?>
                                <option value="<?= $kat['id'] ?>" 
                                        data-jenis="<?= $kat['jenis_laporan'] ?>" 
                                        class="kategori-item"
                                        <?= (int)$formData['kategori_id'] === (int)$kat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1" id="subKatHint"></p>
                    </div>

                    <!-- Judul Laporan -->
                    <div>
                        <label for="judul" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Judul Pengaduan / Pokok Laporan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="judul" name="judul" value="<?= htmlspecialchars($formData['judul']) ?>" required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition font-bold">
                    </div>

                    <!-- Tanggal Kejadian & Lokasi -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="tanggal_kejadian" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Tanggal Kejadian <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" id="tanggal_kejadian" name="tanggal_kejadian" value="<?= htmlspecialchars($formData['tanggal_kejadian']) ?>" max="<?= date('Y-m-d') ?>" required
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                        </div>
                        <div>
                            <label for="lokasi_kejadian" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Lokasi Tempat Kejadian <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="lokasi_kejadian" name="lokasi_kejadian" value="<?= htmlspecialchars($formData['lokasi_kejadian']) ?>" required
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                        </div>
                    </div>

                    <!-- Rincian Kronologi -->
                    <div>
                        <label for="isi_laporan" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Kronologi / Penjelasan Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="isi_laporan" name="isi_laporan" rows="6" required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition leading-relaxed"><?= htmlspecialchars($formData['isi_laporan']) ?></textarea>
                    </div>

                    <!-- Kelola Foto Bukti -->
                    <div class="pt-2 border-t border-slate-100">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Foto Bukti Kejadian
                        </label>

                        <?php if (!empty($formData['foto_bukti'])): ?>
                            <div class="mb-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <img src="<?= base_url('uploads/' . $formData['foto_bukti']) ?>" alt="Foto Bukti Saat Ini" class="w-16 h-16 rounded-xl object-cover border border-slate-200 shadow-xs">
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 block">Foto Bukti Saat Ini</span>
                                        <a href="<?= base_url('uploads/' . $formData['foto_bukti']) ?>" target="_blank" class="text-[11px] text-sky-600 hover:underline">
                                            Lihat Foto Ukuran Penuh &rarr;
                                        </a>
                                    </div>
                                </div>

                                <label class="flex items-center gap-2 p-2 px-3 rounded-xl bg-white border border-rose-200 text-rose-700 text-xs font-bold cursor-pointer hover:bg-rose-50">
                                    <input type="checkbox" name="hapus_foto" value="1" class="text-rose-600 rounded">
                                    <span>Hapus Foto Ini</span>
                                </label>
                            </div>
                        <?php endif; ?>

                        <!-- Upload / Ganti Foto Baru -->
                        <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-2xl hover:border-sky-400 transition bg-slate-50/60">
                            <div class="space-y-2 text-center">
                                <div id="previewContainer" class="hidden mb-3">
                                    <img id="imagePreview" src="#" alt="Pratinjau Foto Baru" class="max-h-48 mx-auto rounded-xl shadow-md border border-slate-200 object-contain">
                                    <button type="button" onclick="clearImagePreview()" class="mt-2 text-xs font-bold text-rose-600 hover:text-rose-700">
                                        <i class="fa-solid fa-trash mr-1"></i> Batalkan Foto Baru
                                    </button>
                                </div>
                                <div id="uploadPrompt" class="flex flex-col items-center">
                                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-sky-600 mb-2"></i>
                                    <div class="flex text-xs text-slate-600">
                                        <label for="foto_bukti" class="relative cursor-pointer bg-white rounded-md font-bold text-smk-blue hover:text-smk-blue-dark focus-within:outline-none">
                                            <span><?= !empty($formData['foto_bukti']) ? 'Ganti Dengan Foto Baru' : 'Unggah Foto Bukti' ?></span>
                                            <input id="foto_bukti" name="foto_bukti" type="file" accept="image/jpeg,image/png,image/webp,image/jpg" class="sr-only" onchange="previewImage(this)">
                                        </label>
                                        <p class="pl-1">atau seret berkas ke sini</p>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1">Format: JPG, JPEG, PNG, atau WEBP (Maksimal 5 MB)</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Privasi & Prioritas -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Status Privasi Laporan
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 text-xs">
                                    <input type="radio" name="status_privasi" value="publik" <?= $formData['status_privasi'] === 'publik' ? 'checked' : '' ?> class="text-smk-blue">
                                    <div>
                                        <span class="font-bold text-slate-800">Publik</span>
                                        <span class="block text-[11px] text-slate-500">Dapat ditampilkan secara transparan</span>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 text-xs">
                                    <input type="radio" name="status_privasi" value="rahasia" <?= $formData['status_privasi'] === 'rahasia' ? 'checked' : '' ?> class="text-rose-600">
                                    <div>
                                        <span class="font-bold text-rose-700 flex items-center gap-1">
                                            <i class="fa-solid fa-lock text-[10px]"></i> Rahasia / TPPK
                                        </span>
                                        <span class="block text-[11px] text-slate-500">Identitas terlindungi, hanya dilihat petugas</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="prioritas" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Tingkat Urgensi
                            </label>
                            <select id="prioritas" name="prioritas" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                                <option value="sedang" <?= $formData['prioritas'] === 'sedang' ? 'selected' : '' ?>>Sedang (Kebutuhan reguler)</option>
                                <option value="rendah" <?= $formData['prioritas'] === 'rendah' ? 'selected' : '' ?>>Rendah (Saran perbaikan)</option>
                                <option value="tinggi" <?= $formData['prioritas'] === 'tinggi' ? 'selected' : '' ?>>Tinggi (Membutuhkan penanganan segera)</option>
                                <option value="darurat" <?= $formData['prioritas'] === 'darurat' ? 'selected' : '' ?>>Darurat (Ancaman keselamatan fisik / bullying)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <a href="<?= base_url('pelapor/detail.php?id=' . $lap['id']) ?>" class="text-xs font-bold text-slate-500 hover:text-slate-800">
                            <i class="fa-solid fa-arrow-left mr-1"></i> Batal & Kembali
                        </a>

                        <button type="submit" class="w-full sm:w-auto px-8 py-3.5 bg-smk-blue hover:bg-smk-blue-hover text-white font-extrabold rounded-xl shadow-lg hover:shadow-xl transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Simpan Perubahan Laporan</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

<script>
// Filter opsi sub-kategori agar HANYA menampilkan topik dari jenis pengaduan yang dipilih
function filterKategori(jenis) {
    const select = document.getElementById('kategori_id');
    const placeholder = document.getElementById('placeholderOption');
    const hint = document.getElementById('subKatHint');
    const options = select.querySelectorAll('.kategori-item');

    if (jenis === 'kekerasan_bullying') {
        placeholder.textContent = '-- Pilih Topik Kekerasan / Bullying (TPPK) --';
        if (hint) hint.textContent = 'Daftar topik khusus perundungan, kekerasan fisik, verbal, cyberbullying, dan pemalakan';
    } else {
        placeholder.textContent = '-- Pilih Topik Pengaduan Umum --';
        if (hint) hint.textContent = 'Daftar topik sarana kelas, bengkel kejuruan, kebersihan, dan layanan administrasi';
    }

    options.forEach(opt => {
        const itemJenis = opt.getAttribute('data-jenis');
        if (itemJenis === jenis) {
            opt.hidden = false;
            opt.disabled = false;
            opt.style.display = '';
        } else {
            opt.hidden = true;
            opt.disabled = true;
            opt.style.display = 'none';
        }
    });
}

function onJenisChange(jenis) {
    filterKategori(jenis);
    const cardUmum = document.getElementById('cardUmum');
    const cardBullying = document.getElementById('cardBullying');
    if (cardUmum && cardBullying) {
        if (jenis === 'umum') {
            cardUmum.className = 'relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition border-smk-blue bg-sky-50/50 shadow-sm';
            cardBullying.className = 'relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition border-slate-200 hover:border-slate-300';
        } else {
            cardBullying.className = 'relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition border-rose-500 bg-rose-50/50 shadow-sm';
            cardUmum.className = 'relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition border-slate-200 hover:border-slate-300';
        }
    }
}

function previewImage(input) {
    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const uploadPrompt = document.getElementById('uploadPrompt');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            previewContainer.classList.remove('hidden');
            uploadPrompt.classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function clearImagePreview() {
    const fileInput = document.getElementById('foto_bukti');
    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const uploadPrompt = document.getElementById('uploadPrompt');

    fileInput.value = '';
    imagePreview.src = '#';
    previewContainer.classList.add('hidden');
    uploadPrompt.classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    const activeRadio = document.querySelector('input[name="jenis_laporan"]:checked');
    const activeJenis = activeRadio ? activeRadio.value : '<?= $selectedJenis ?>';
    filterKategori(activeJenis);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

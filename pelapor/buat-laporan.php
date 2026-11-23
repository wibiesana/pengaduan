<?php
// pelapor/buat-laporan.php
require_once __DIR__ . '/../config/helpers.php';
require_login();

$user = current_user();
$db = get_db_connection();

// Ambil semua kategori dari DB
$stmtKat = $db->query("SELECT * FROM kategori_pengaduan ORDER BY jenis_laporan, nama_kategori ASC");
$allKategori = $stmtKat->fetchAll();

$errors = [];
$selectedJenis = $_GET['jenis'] ?? ($_POST['jenis_laporan'] ?? 'umum');
if (!in_array($selectedJenis, ['umum', 'kekerasan_bullying'])) {
    $selectedJenis = 'umum';
}

$formData = [
    'jenis_laporan'    => $selectedJenis,
    'kategori_id'      => $_POST['kategori_id'] ?? '',
    'judul'            => $_POST['judul'] ?? '',
    'tanggal_kejadian' => $_POST['tanggal_kejadian'] ?? date('Y-m-d'),
    'lokasi_kejadian'  => $_POST['lokasi_kejadian'] ?? '',
    'isi_laporan'      => $_POST['isi_laporan'] ?? '',
    'status_privasi'   => $_POST['status_privasi'] ?? ($selectedJenis === 'kekerasan_bullying' ? 'rahasia' : 'publik'),
    'prioritas'        => $_POST['prioritas'] ?? ($selectedJenis === 'kekerasan_bullying' ? 'tinggi' : 'sedang'),
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

    // Update form data for repopulation
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
        $errors[] = 'Isi rincian pengaduan wajib diisi secara jelas.';
    } elseif (strlen($isi_laporan) < 15) {
        $errors[] = 'Deskripsi laporan terlalu singkat (minimal 15 karakter agar mudah ditindaklanjuti).';
    }

    // Handle Upload Gambar
    $foto_bukti = null;
    if (empty($errors)) {
        $uploadResult = handle_image_upload('foto_bukti');
        if (!$uploadResult['status']) {
            $errors[] = $uploadResult['error'];
        } else {
            $foto_bukti = $uploadResult['filename'];
        }
    }

    // Simpan Pengaduan ke Database
    if (empty($errors)) {
        $kode_tiket = generate_kode_tiket();

        $stmtInsert = $db->prepare("
            INSERT INTO pengaduan 
            (kode_tiket, user_id, jenis_laporan, kategori_id, judul, isi_laporan, tanggal_kejadian, lokasi_kejadian, foto_bukti, status_privasi, prioritas, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'menunggu')
        ");

        $inserted = $stmtInsert->execute([
            $kode_tiket,
            $user['id'],
            $jenis_laporan,
            $kategori_id,
            $judul,
            $isi_laporan,
            $tanggal_kejadian,
            $lokasi_kejadian,
            $foto_bukti,
            $status_privasi,
            $prioritas
        ]);

        if ($inserted) {
            $pengaduanId = $db->lastInsertId();
            set_flash('success', 'Laporan Anda berhasil dikirim dengan Kode Tiket: ' . $kode_tiket . '. Tim sekolah akan segera menelaah dan menindaklanjuti.');
            header('Location: ' . base_url('pelapor/detail.php?id=' . $pengaduanId));
            exit;
        } else {
            $errors[] = 'Gagal menyimpan laporan ke basis data. Silakan coba beberapa saat lagi.';
        }
    }
}

$pageTitle = 'Buat Laporan / Pengaduan Baru';
$activeNav = $selectedJenis === 'kekerasan_bullying' ? 'lapor_bullying' : 'lapor_umum';
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
            <span class="text-slate-800 font-bold">Formulir Laporan</span>
        </nav>

        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-100 overflow-hidden">
            <!-- Header Card with Dynamic Badge -->
            <div class="p-6 sm:p-8 <?= $selectedJenis === 'kekerasan_bullying' ? 'bg-gradient-to-r from-rose-900 to-rose-700' : 'bg-gradient-to-r from-sky-900 to-sky-700' ?> text-white">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-white/15 backdrop-blur text-white mb-2">
                            <i class="<?= $selectedJenis === 'kekerasan_bullying' ? 'fa-solid fa-shield-cat text-amber-300' : 'fa-solid fa-bullhorn text-sky-300' ?>"></i>
                            <span><?= $selectedJenis === 'kekerasan_bullying' ? 'JALUR KHUSUS TPPK' : 'JALUR PENGADUAN UMUM' ?></span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                            <?= $selectedJenis === 'kekerasan_bullying' ? 'Lapor Perundungan / Kekerasan' : 'Form Pengaduan Sarana & Umum' ?>
                        </h1>
                        <p class="text-xs sm:text-sm text-white/80 mt-1 max-w-xl">
                            <?= $selectedJenis === 'kekerasan_bullying' 
                                ? 'Laporkan tindak kekerasan fisik, psikis, pemerasan, atau cyberbullying. Kerahasiaan Anda dilindungi penuh.' 
                                : 'Sampaikan keluhan fasilitas sekolah, sarana bengkel praktik, kebersihan, atau pelayanan administrasi.' ?>
                        </p>
                    </div>

                    <!-- Pelapor Profile Info -->
                    <div class="bg-white/10 backdrop-blur p-3 rounded-2xl border border-white/15 text-xs text-right hidden sm:block">
                        <span class="text-white/60 block text-[10px]">Pelapor Terverifikasi:</span>
                        <span class="font-bold text-white"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                        <span class="block text-[11px] text-amber-300 capitalize"><?= $user['tipe_pelapor'] ?? 'Pelapor' ?></span>
                    </div>
                </div>
            </div>

            <div class="p-6 sm:p-8">
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                        <div class="font-bold flex items-center gap-2 mb-1.5 text-rose-800">
                            <i class="fa-solid fa-triangle-exclamation text-rose-500"></i> Mohon lengkapi bagian berikut:
                        </div>
                        <ul class="list-disc pl-5 space-y-1">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                    <!-- Tab Switcher: Umum vs Kekerasan/Bullying -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Pilih Kategori Laporan <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label id="cardUmum" class="relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition <?= $formData['jenis_laporan'] === 'umum' ? 'border-smk-blue bg-sky-50/50 shadow-sm' : 'border-slate-200 hover:border-slate-300' ?>">
                                <input type="radio" name="jenis_laporan" value="umum" class="sr-only" <?= $formData['jenis_laporan'] === 'umum' ? 'checked' : '' ?> onchange="onJenisChange('umum')">
                                <div class="w-10 h-10 rounded-xl bg-smk-blue text-white flex items-center justify-center text-lg flex-shrink-0 mr-3">
                                    <i class="fa-solid fa-bullhorn"></i>
                                </div>
                                <div>
                                    <span class="block text-sm font-extrabold text-slate-900">Pengaduan Umum</span>
                                    <span class="block text-xs text-slate-500">Fasilitas, kebersihan, KBM, dan tata usaha</span>
                                </div>
                            </label>

                            <label id="cardBullying" class="relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition <?= $formData['jenis_laporan'] === 'kekerasan_bullying' ? 'border-rose-500 bg-rose-50/50 shadow-sm' : 'border-slate-200 hover:border-slate-300' ?>">
                                <input type="radio" name="jenis_laporan" value="kekerasan_bullying" class="sr-only" <?= $formData['jenis_laporan'] === 'kekerasan_bullying' ? 'checked' : '' ?> onchange="onJenisChange('kekerasan_bullying')">
                                <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center text-lg flex-shrink-0 mr-3">
                                    <i class="fa-solid fa-shield-cat"></i>
                                </div>
                                <div>
                                    <span class="block text-sm font-extrabold text-slate-900">Kekerasan / Bullying</span>
                                    <span class="block text-xs text-slate-500">Perundungan fisik, verbal, pemalakan, pelecehan</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Sub Kategori (Tersaring Berdasarkan Jenis yang Dipilih) -->
                    <div>
                        <label for="kategori_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Topik / Sub-Kategori Spesifik
                        </label>
                        <select id="kategori_id" name="kategori_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition font-medium">
                            <option value="" id="placeholderOption">
                                <?= $selectedJenis === 'kekerasan_bullying' ? '-- Pilih Topik Kekerasan / Bullying --' : '-- Pilih Topik Pengaduan Umum --' ?>
                            </option>
                            <?php foreach ($allKategori as $kat): ?>
                                <option value="<?= $kat['id'] ?>" 
                                        data-jenis="<?= $kat['jenis_laporan'] ?>" 
                                        class="kategori-item"
                                        <?= (int)$formData['kategori_id'] === (int)$kat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1" id="subKatHint">
                            <?= $selectedJenis === 'kekerasan_bullying' ? 'Daftar topik khusus perundungan, kekerasan fisik, verbal, cyberbullying, dan pemalakan' : 'Daftar topik sarana kelas, bengkel kejuruan, kebersihan, dan layanan administrasi' ?>
                        </p>
                    </div>

                    <!-- Judul Laporan -->
                    <div>
                        <label for="judul" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Judul Pengaduan / Pokok Laporan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="judul" name="judul" value="<?= htmlspecialchars($formData['judul']) ?>" required
                            placeholder="Contoh: Stopkontak Bengkel TKRO Rusak / Pemalakan di Lorong Belakang Kantin"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
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
                                placeholder="Contoh: Ruang Kelas XI TKRO 1, Lab Komputer, Kantin, dll."
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                        </div>
                    </div>

                    <!-- Rincian Isi Laporan -->
                    <div>
                        <label for="isi_laporan" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Kronologi / Penjelasan Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="isi_laporan" name="isi_laporan" rows="5" required
                            placeholder="Tuliskan kronologi peristiwa dengan sejelas-jelasnya: apa yang terjadi, waktu kejadian, pihak yang terlibat (jika diketahui), dan dampak yang dirasakan..."
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition leading-relaxed"><?= htmlspecialchars($formData['isi_laporan']) ?></textarea>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Semakin jelas informasi yang disampaikan, semakin cepat tim sekolah dapat mengambil tindakan solutif.
                        </p>
                    </div>

                    <!-- Upload Foto / Bukti -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Unggah Foto Bukti Pendukung <span class="text-slate-400 font-normal">(Opsional)</span>
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-2xl hover:border-sky-400 transition bg-slate-50/60">
                            <div class="space-y-2 text-center">
                                <div id="previewContainer" class="hidden mb-3">
                                    <img id="imagePreview" src="#" alt="Pratinjau Foto" class="max-h-48 mx-auto rounded-xl shadow-md border border-slate-200 object-contain">
                                    <button type="button" onclick="clearImagePreview()" class="mt-2 text-xs font-bold text-rose-600 hover:text-rose-700">
                                        <i class="fa-solid fa-trash mr-1"></i> Hapus Foto
                                    </button>
                                </div>
                                <div id="uploadPrompt" class="flex flex-col items-center">
                                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-sky-600 mb-2"></i>
                                    <div class="flex text-xs text-slate-600">
                                        <label for="foto_bukti" class="relative cursor-pointer bg-white rounded-md font-bold text-smk-blue hover:text-smk-blue-dark focus-within:outline-none">
                                            <span>Pilih Berkas Foto</span>
                                            <input id="foto_bukti" name="foto_bukti" type="file" accept="image/jpeg,image/png,image/webp,image/jpg" class="sr-only" onchange="previewImage(this)">
                                        </label>
                                        <p class="pl-1">atau seret ke area ini</p>
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
                                        <span class="block text-[11px] text-slate-500">Dapat ditampilkan di beranda untuk transparansi</span>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 text-xs">
                                    <input type="radio" name="status_privasi" value="rahasia" <?= $formData['status_privasi'] === 'rahasia' ? 'checked' : '' ?> class="text-rose-600">
                                    <div>
                                        <span class="font-bold text-rose-700 flex items-center gap-1">
                                            <i class="fa-solid fa-lock text-[10px]"></i> Rahasia / Anonim
                                        </span>
                                        <span class="block text-[11px] text-slate-500">Hanya dapat dilihat oleh Tim Penindak / TPPK</span>
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
                            <p class="text-[11px] text-slate-400 mt-1">
                                Pilih sesuai dengan tingkat keparahan situasi yang Anda hadapi.
                            </p>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <a href="<?= base_url('pelapor/dashboard.php') ?>" class="text-xs font-bold text-slate-500 hover:text-slate-800">
                            <i class="fa-solid fa-arrow-left mr-1"></i> Batal & Kembali ke Dashboard
                        </a>

                        <button type="submit" class="w-full sm:w-auto px-8 py-3.5 <?= $selectedJenis === 'kekerasan_bullying' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-smk-blue hover:bg-smk-blue-hover' ?> text-white font-extrabold rounded-xl shadow-lg hover:shadow-xl transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Kirim Laporan Pengaduan</span>
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

    let isSelectedStillValid = false;
    options.forEach(opt => {
        const itemJenis = opt.getAttribute('data-jenis');
        if (itemJenis === jenis) {
            opt.hidden = false;
            opt.disabled = false;
            opt.style.display = '';
            if (opt.selected) isSelectedStillValid = true;
        } else {
            opt.hidden = true;
            opt.disabled = true;
            opt.style.display = 'none';
            if (opt.selected) opt.selected = false;
        }
    });

    // Jika opsi yang sebelumnya dipilih bukan dari jenis yang sekarang aktif, reset ke placeholder
    if (!isSelectedStillValid) {
        select.value = '';
    }
}

// Handler saat user memilih tab Pengaduan Umum vs Kekerasan/Bullying
function onJenisChange(jenis) {
    // 1. Filter dropdown kategori secara instan
    filterKategori(jenis);

    // 2. Perbarui tampilan visual card radio selector
    const cardUmum = document.getElementById('cardUmum');
    const cardBullying = document.getElementById('cardBullying');
    if (cardUmum && cardBullying) {
        if (jenis === 'umum') {
            cardUmum.className = 'relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition border-smk-blue bg-sky-50/50 shadow-sm';
            cardBullying.className = 'relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition border-slate-200 hover:border-slate-300';
            
            // Set default privasi & prioritas untuk umum
            const privPublik = document.querySelector('input[name="status_privasi"][value="publik"]');
            if (privPublik) privPublik.checked = true;
            const prioSelect = document.getElementById('prioritas');
            if (prioSelect) prioSelect.value = 'sedang';
        } else {
            cardBullying.className = 'relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition border-rose-500 bg-rose-50/50 shadow-sm';
            cardUmum.className = 'relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition border-slate-200 hover:border-slate-300';
            
            // Set default privasi & prioritas untuk bullying (lebih terlindungi)
            const privRahasia = document.querySelector('input[name="status_privasi"][value="rahasia"]');
            if (privRahasia) privRahasia.checked = true;
            const prioSelect = document.getElementById('prioritas');
            if (prioSelect) prioSelect.value = 'tinggi';
        }
    }

    // 3. Perbarui URL query tanpa reload agar form tidak ter-reset
    if (window.history.replaceState) {
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?jenis=' + jenis;
        window.history.replaceState({ path: newUrl }, '', newUrl);
    }
}

// Inisialisasi penyaringan saat dokumen pertama kali dimuat
document.addEventListener('DOMContentLoaded', () => {
    const activeRadio = document.querySelector('input[name="jenis_laporan"]:checked');
    const activeJenis = activeRadio ? activeRadio.value : '<?= $selectedJenis ?>';
    filterKategori(activeJenis);
});

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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

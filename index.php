<?php
// index.php
require_once __DIR__ . '/config/helpers.php';

$db = get_db_connection();

// Statistik Total Pengaduan
$stmtStat = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN jenis_laporan = 'umum' THEN 1 ELSE 0 END) as total_umum,
        SUM(CASE WHEN jenis_laporan = 'kekerasan_bullying' THEN 1 ELSE 0 END) as total_bullying,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as total_selesai,
        SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as total_diproses
    FROM pengaduan
");
$stat = $stmtStat->fetch() ?: ['total' => 0, 'total_umum' => 0, 'total_bullying' => 0, 'total_selesai' => 0, 'total_diproses' => 0];

// Pengaduan Publik Terbaru (yang status privasinya publik)
$stmtPublik = $db->query("
    SELECT p.*, k.nama_kategori, u.nama_lengkap, u.tipe_pelapor
    FROM pengaduan p
    LEFT JOIN kategori_pengaduan k ON p.kategori_id = k.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.status_privasi = 'publik'
    ORDER BY p.id DESC
    LIMIT 6
");
$laporanPublik = $stmtPublik->fetchAll();

$pageTitle = 'Layanan Aspirasi & Pengaduan SMKN 1 Cipeundeuy';
$activeNav = 'home';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="relative overflow-hidden bg-gradient-to-b from-sky-900 via-sky-800 to-slate-900 text-white py-16 sm:py-24">
    <!-- Decorative background glow and shapes -->
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-sky-500/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 -left-20 w-80 h-80 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <!-- Left Headline -->
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur border border-white/20 text-xs text-sky-200">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                    <span class="font-semibold tracking-wide">Pusat Layanan Terpadu Siswa & Warga Sekolah</span>
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight">
                    Suarakan Aspirasi, <br class="hidden sm:inline">
                    Wujudkan SMKN 1 Cipeundeuy <br class="hidden sm:inline">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-amber-300 to-sky-300">
                        Aman, Nyaman & Bebas Bullying
                    </span>
                </h1>

                <p class="text-slate-300 text-sm sm:text-base max-w-2xl mx-auto lg:mx-0 leading-relaxed font-normal">
                    Platform pengaduan resmi bagi <strong>Siswa, Guru, Orang Tua/Wali, dan Masyarakat</strong>. Laporan ditangani langsung secara profesional, transparan, serta mengedepankan perlindungan identitas pelapor.
                </p>

                <!-- Action Buttons: 2 Kategori Utama -->
                <div class="flex flex-col sm:flex-row gap-4 pt-2 justify-center lg:justify-start">
                    <a href="<?= base_url('pelapor/buat-laporan.php?jenis=umum') ?>" class="px-6 py-3.5 rounded-xl font-extrabold text-sm text-slate-900 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 shadow-lg shadow-amber-500/30 transition transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-bullhorn text-slate-950"></i>
                        <span>Lapor Pengaduan Umum</span>
                    </a>

                    <a href="<?= base_url('pelapor/buat-laporan.php?jenis=kekerasan_bullying') ?>" class="px-6 py-3.5 rounded-xl font-extrabold text-sm text-white bg-rose-600 hover:bg-rose-500 shadow-lg shadow-rose-600/30 border border-rose-400/40 transition transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-shield-cat text-amber-300"></i>
                        <span>Lapor Kekerasan & Bullying</span>
                    </a>
                </div>

                <div class="flex items-center justify-center lg:justify-start gap-4 text-xs text-sky-200/90 pt-3">
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-check-double text-emerald-400"></i>
                        <span>Respon Cepat Tim TPPK</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-user-shield text-amber-400"></i>
                        <span>Pilihan Privasi Anonim</span>
                    </div>
                </div>
            </div>

            <!-- Right Logo / Badge Card -->
            <div class="lg:col-span-5 flex justify-center">
                <div class="relative w-full max-w-sm">
                    <div class="absolute inset-0 bg-gradient-to-tr from-sky-500 to-amber-500 rounded-3xl blur-2xl opacity-20 transform -rotate-3"></div>
                    
                    <div class="relative bg-slate-900/80 backdrop-blur-xl border border-white/10 rounded-3xl p-6 sm:p-8 text-center shadow-2xl">
                        <div class="relative mx-auto w-32 h-32 mb-4 bg-white/5 rounded-2xl p-2 flex items-center justify-center border border-white/10 shadow-inner">
                            <img src="<?= base_url('asset/img/logo.png') ?>" alt="Logo SMKN 1 Cipeundeuy" class="max-h-full max-w-full object-contain drop-shadow-lg">
                        </div>

                        <h3 class="text-lg font-black text-white">SMKN 1 CIPEUNDEUY</h3>
                        <p class="text-xs text-amber-400 font-bold uppercase tracking-wider mt-0.5">Cadisdik Wilayah VI • NPSN: 20279928</p>
                        <p class="text-[11px] text-slate-400 mt-2">
                            Jl. Cirata Margalaksana RT/RW: 001/012 Kec. Cipeundeuy, Kab. Bandung Barat
                        </p>

                        <!-- Fast Stat Highlights -->
                        <div class="grid grid-cols-2 gap-3 mt-6 pt-6 border-t border-white/10 text-left">
                            <div class="bg-white/5 p-3 rounded-xl border border-white/5">
                                <span class="text-[10px] text-slate-400 block font-medium">Total Laporan</span>
                                <span class="text-xl font-black text-amber-400"><?= number_format($stat['total']) ?></span>
                            </div>
                            <div class="bg-white/5 p-3 rounded-xl border border-white/5">
                                <span class="text-[10px] text-slate-400 block font-medium">Telah Selesai</span>
                                <span class="text-xl font-black text-emerald-400"><?= number_format($stat['total_selesai']) ?></span>
                            </div>
                        </div>

                        <?php if (!is_logged_in()): ?>
                            <div class="mt-5">
                                <a href="<?= base_url('auth/login.php') ?>" class="block w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-sky-500/20 text-sky-200 border border-sky-400/30 hover:bg-sky-500/30 transition">
                                    <i class="fa-solid fa-lock mr-1.5"></i> Masuk Terlebih Dahulu Untuk Lapor
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="mt-5">
                                <a href="<?= base_url('pelapor/dashboard.php') ?>" class="block w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-emerald-500/20 text-emerald-200 border border-emerald-400/30 hover:bg-emerald-500/30 transition">
                                    <i class="fa-solid fa-folder-open mr-1.5"></i> Buka Dashboard Saya (<?= htmlspecialchars($currentUser['nama_lengkap']) ?>)
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- 2 Kategori Utama Section -->
<section class="py-16 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="text-xs font-extrabold text-smk-blue uppercase tracking-wider bg-sky-50 px-3 py-1 rounded-full border border-sky-200">
                Layanan Terbagi 2 Jalur Utama
            </span>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mt-3 tracking-tight">
                Pilih Jenis Laporan Sesuai Kebutuhan Anda
            </h2>
            <p class="text-sm text-slate-600 mt-2">
                Kami memisahkan jalur penanganan agar laporan fasilitas sekolah segera diperbaiki, dan laporan kekerasan/bullying ditangani secara cepat dengan kerahasiaan penuh oleh Tim Khusus.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Card 1: Pengaduan Umum -->
            <div class="relative bg-gradient-card-umum p-8 rounded-3xl border-2 border-sky-200 hover:border-sky-400 shadow-md hover:shadow-xl transition-all duration-300 group flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-smk-blue text-white flex items-center justify-center text-2xl shadow-lg shadow-sky-500/30 mb-6 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-bullhorn"></i>
                    </div>
                    <span class="text-xs font-bold uppercase tracking-wider text-smk-blue">Jalur 1</span>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">Pengaduan Umum & Sarana</h3>
                    <p class="text-xs sm:text-sm text-slate-600 mt-3 leading-relaxed">
                        Layanan untuk menyampaikan aspirasi atau kendala umum di lingkungan sekolah, seperti fasilitas kelas rusak, toilet, penerangan laboratorium, kebersihan area sekolah, hingga pelayanan administrasi.
                    </p>
                    
                    <ul class="mt-5 space-y-2 text-xs text-slate-700">
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-sky-600"></i>
                            <span>Kerusakan fasilitas ruang belajar & bengkel</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-sky-600"></i>
                            <span>Keluhan kebersihan, lingkungan & keamanan sekolah</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-sky-600"></i>
                            <span>Pelayanan administrasi tata usaha & KBM</span>
                        </li>
                    </ul>
                </div>

                <div class="mt-8 pt-6 border-t border-sky-100 flex items-center justify-between">
                    <span class="text-xs font-semibold text-sky-800">Ditangani Tim Sarpras & Manajemen</span>
                    <a href="<?= base_url('pelapor/buat-laporan.php?jenis=umum') ?>" class="px-5 py-2.5 rounded-xl font-bold text-xs bg-smk-blue text-white hover:bg-smk-blue-hover shadow transition flex items-center gap-1.5">
                        <span>Buat Pengaduan Umum</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- Card 2: Laporan Kekerasan & Bullying -->
            <div class="relative bg-gradient-card-bullying p-8 rounded-3xl border-2 border-rose-200 hover:border-rose-400 shadow-md hover:shadow-xl transition-all duration-300 group flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-rose-600 text-white flex items-center justify-center text-2xl shadow-lg shadow-rose-600/30 mb-6 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-shield-cat"></i>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Jalur Khusus Prioritas</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-300">TPPK Respon</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">Laporan Kekerasan & Bullying</h3>
                    <p class="text-xs sm:text-sm text-slate-600 mt-3 leading-relaxed">
                        Perlindungan penuh bagi siswa dari tindakan perundungan fisik, verbal, cyberbullying, pemalakan, pemerasan, hingga pelecehan. Privasi pelapor dan korban dilindungi penuh.
                    </p>

                    <ul class="mt-5 space-y-2 text-xs text-slate-700">
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-rose-600"></i>
                            <span>Perundungan fisik (pemukulan, intimidasi fisik)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-rose-600"></i>
                            <span>Perundungan verbal, ejekan bernuansa sara, pemerasan uang</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-rose-600"></i>
                            <span>Cyberbullying media sosial & pelecehan seksual</span>
                        </li>
                    </ul>
                </div>

                <div class="mt-8 pt-6 border-t border-rose-100 flex items-center justify-between">
                    <span class="text-xs font-semibold text-rose-800 flex items-center gap-1">
                        <i class="fa-solid fa-lock text-rose-600"></i> Kerahasiaan Dilindungi TPPK
                    </span>
                    <a href="<?= base_url('pelapor/buat-laporan.php?jenis=kekerasan_bullying') ?>" class="px-5 py-2.5 rounded-xl font-bold text-xs bg-rose-600 text-white hover:bg-rose-700 shadow transition flex items-center gap-1.5">
                        <span>Lapor Kasus Bullying</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Alur Lapor Section -->
<section id="alur" class="py-16 bg-slate-50 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <span class="text-xs font-extrabold text-smk-blue uppercase tracking-wider bg-sky-100/70 px-3 py-1 rounded-full">
                Tata Cara & Prosedur
            </span>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">
                4 Langkah Mudah Melapor
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Laporan diproses secara terstruktur dan terukur oleh pihak berwenang di sekolah
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Step 1 -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm relative group hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-sky-100 text-smk-blue font-black flex items-center justify-center text-base mb-4">
                    1
                </div>
                <h4 class="font-extrabold text-sm text-slate-900 mb-1.5">Masuk / Daftar Akun</h4>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Pelapor wajib login dengan akun Siswa, Wali, atau Masyarakat agar laporan valid dan bebas dari laporan palsu/fiktif.
                </p>
            </div>

            <!-- Step 2 -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm relative group hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 font-black flex items-center justify-center text-base mb-4">
                    2
                </div>
                <h4 class="font-extrabold text-sm text-slate-900 mb-1.5">Isi Formulir & Bukti Foto</h4>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Pilih jenis laporan (Umum / Bullying), isi detail kejadian, dan unggah foto bukti pendukung (foto tempat, luka, chat, dsb).
                </p>
            </div>

            <!-- Step 3 -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm relative group hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 font-black flex items-center justify-center text-base mb-4">
                    3
                </div>
                <h4 class="font-extrabold text-sm text-slate-900 mb-1.5">Verifikasi & Tindak Lanjut</h4>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Tim Sarpras atau Satgas TPPK menelaah fakta, melakukan investigasi atau perbaikan di lapangan, dan mencatat progresnya.
                </p>
            </div>

            <!-- Step 4 -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm relative group hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 font-black flex items-center justify-center text-base mb-4">
                    4
                </div>
                <h4 class="font-extrabold text-sm text-slate-900 mb-1.5">Pantau Progres & Selesai</h4>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Pelapor dapat memantau status secara live, membaca balasan resmi admin, dan mengunduh bukti tanda terima laporan.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Pengaduan Publik Terbaru -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 gap-4">
            <div>
                <span class="text-xs font-extrabold text-sky-600 uppercase tracking-wider bg-sky-50 px-3 py-1 rounded-full border border-sky-100">
                    Transparansi Layanan
                </span>
                <h2 class="text-2xl font-black text-slate-900 mt-2">
                    Pengaduan Publik Terkini
                </h2>
                <p class="text-xs text-slate-500">
                    Menampilkan laporan publik yang telah diverifikasi (laporan rahasia tidak ditampilkan di sini).
                </p>
            </div>
            <a href="<?= base_url('pelapor/buat-laporan.php') ?>" class="text-xs font-bold text-smk-blue hover:text-smk-blue-dark flex items-center gap-1">
                <span>Ingin menyampaikan laporan? Klik di sini</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <?php if (empty($laporanPublik)): ?>
            <div class="p-8 text-center bg-slate-50 rounded-2xl border border-slate-200 text-slate-500 text-xs">
                <i class="fa-solid fa-clipboard-check text-3xl text-slate-400 mb-2"></i>
                <p>Belum ada pengaduan publik yang dipublikasikan.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($laporanPublik as $lap): ?>
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <?= render_jenis_badge($lap['jenis_laporan']) ?>
                                <?= render_status_badge($lap['status']) ?>
                            </div>

                            <div class="text-[11px] font-mono font-bold text-slate-400 mb-1">
                                <?= htmlspecialchars($lap['kode_tiket']) ?>
                            </div>

                            <h4 class="font-extrabold text-sm text-slate-900 mb-2 line-clamp-2">
                                <?= htmlspecialchars($lap['judul']) ?>
                            </h4>

                            <p class="text-xs text-slate-600 line-clamp-3 mb-4 leading-relaxed">
                                <?= htmlspecialchars($lap['isi_laporan']) ?>
                            </p>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                            <div class="flex items-center gap-1.5">
                                <i class="fa-solid fa-location-dot text-slate-400"></i>
                                <span class="truncate max-w-[140px]"><?= htmlspecialchars($lap['lokasi_kejadian']) ?></span>
                            </div>
                            <span><?= date('d M Y', strtotime($lap['tanggal_kejadian'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

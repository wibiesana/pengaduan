<?php
// config/helpers.php
// Helper functions & Session Management

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

// Data Resmi Sekolah (Sesuai KOP Resmi SMKN 1 Cipeundeuy)
define('SCHOOL_NAME', 'SMK NEGERI 1 CIPEUNDEUY');
define('SCHOOL_PEMDA', 'PEMERINTAH DAERAH PROVINSI JAWA BARAT');
define('SCHOOL_DINAS', 'DINAS PENDIDIKAN');
define('SCHOOL_CABDIN', 'CABANG DINAS PENDIDIKAN WILAYAH VI');
define('SCHOOL_NPSN', '20279928');
define('SCHOOL_ALAMAT', 'Jl. Cirata Margalaksana RT/RW: 001/012 Kec. Cipeundeuy');
define('SCHOOL_KOTA', 'Kab. Bandung Barat Prov. Jawa Barat');
define('SCHOOL_KODEPOS', '40558');
define('SCHOOL_TELP', '02263200004');
define('SCHOOL_FAX', '0');
define('SCHOOL_EMAIL', 'smkn1cpdykbb@gmail.com');
define('SCHOOL_WEBSITE', 'www.smkn1cipeundeuy.sch.id');

// Base URL helper
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Auto-detect subfolder if running in htdocs or subpath
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    
    // Get project root relative to domain
    $projectDir = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $subDir = '';
    if (!empty($docRoot) && strpos($projectDir, $docRoot) === 0) {
        $subDir = substr($projectDir, strlen($docRoot));
    }
    
    $cleanPath = ltrim($path, '/');
    $prefix = rtrim($subDir, '/');
    
    return $prefix ? $prefix . '/' . $cleanPath : '/' . $cleanPath;
}

// Flash Message Helpers
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Authentication Helpers
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nama_lengkap' => $_SESSION['nama_lengkap'],
        'role' => $_SESSION['role'],
        'tipe_pelapor' => $_SESSION['tipe_pelapor'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'no_telp' => $_SESSION['no_telp'] ?? null,
    ];
}

function require_login() {
    if (!is_logged_in()) {
        set_flash('danger', 'Silakan login terlebih dahulu untuk mengakses layanan pengaduan.');
        header('Location: ' . base_url('auth/login.php'));
        exit;
    }
}

function require_role($allowed_roles = ['admin', 'petugas']) {
    require_login();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, (array)$allowed_roles)) {
        set_flash('danger', 'Anda tidak memiliki hak akses untuk halaman tersebut.');
        header('Location: ' . base_url('index.php'));
        exit;
    }
}

// Sanitize String
function sanitize($str) {
    return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
}

// Generate Kode Tiket Pengaduan (e.g. LAP-202611-0023)
function generate_kode_tiket() {
    $db = get_db_connection();
    $prefix = 'LAP-' . date('Ym') . '-';
    $stmt = $db->prepare("SELECT kode_tiket FROM pengaduan WHERE kode_tiket LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    
    if ($last) {
        $lastNumber = (int)substr($last, -4);
        $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $nextNumber = '0001';
    }
    
    return $prefix . $nextNumber;
}

// Format Tanggal Indonesia
function format_tanggal_indonesia($dateStr, $withTime = false) {
    if (empty($dateStr)) return '-';
    $timestamp = strtotime($dateStr);
    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $tgl = date('d', $timestamp);
    $bln = (int)date('m', $timestamp);
    $thn = date('Y', $timestamp);
    
    $res = $tgl . ' ' . ($bulanIndo[$bln] ?? '') . ' ' . $thn;
    if ($withTime) {
        $res .= ' pukul ' . date('H:i', $timestamp) . ' WIB';
    }
    return $res;
}

// Status Badge Renderer
function render_status_badge($status) {
    switch ($status) {
        case 'menunggu':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> Menunggu Verifikasi</span>';
        case 'diproses':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-800 border border-sky-300"><i class="fa-solid fa-spinner fa-spin"></i> Sedang Ditindaklanjuti</span>';
        case 'selesai':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-circle-check"></i> Selesai Ditangani</span>';
        case 'ditolak':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-300"><i class="fa-solid fa-circle-xmark"></i> Laporan Ditolak</span>';
        default:
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">' . htmlspecialchars(ucfirst($status)) . '</span>';
    }
}

// Jenis Laporan Badge
function render_jenis_badge($jenis) {
    if ($jenis === 'kekerasan_bullying') {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200"><i class="fa-solid fa-shield-cat"></i> Kekerasan / Bullying</span>';
    }
    return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200"><i class="fa-solid fa-bullhorn"></i> Pengaduan Umum</span>';
}

// Upload Foto Handler
function handle_image_upload($fileInputName, $targetDir = null) {
    if ($targetDir === null) {
        $targetDir = __DIR__ . '/../uploads/';
    }
    
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['status' => true, 'filename' => null];
    }
    
    $file = $_FILES[$fileInputName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['status' => false, 'error' => 'Terjadi kesalahan upload berkas (Kode: ' . $file['error'] . ')'];
    }
    
    // Validasi ukuran (max 5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['status' => false, 'error' => 'Ukuran file terlalu besar! Maksimal 5MB.'];
    }
    
    // Validasi ekstensi & MIME type
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowedMimes)) {
        return ['status' => false, 'error' => 'Format file tidak diizinkan! Harap unggah foto berekstensi JPG, JPEG, PNG, atau WEBP.'];
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $cleanExt = strtolower($ext);
    if (!in_array($cleanExt, ['jpg', 'jpeg', 'png', 'webp'])) {
        $cleanExt = 'jpg';
    }
    
    $newFileName = 'bukti_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $cleanExt;
    $destination = rtrim($targetDir, '/') . '/' . $newFileName;
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['status' => true, 'filename' => $newFileName];
    } else {
        return ['status' => false, 'error' => 'Gagal memindahkan file yang diunggah ke server.'];
    }
}

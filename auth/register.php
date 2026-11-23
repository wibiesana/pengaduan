<?php
// auth/register.php
require_once __DIR__ . '/../config/helpers.php';

if (is_logged_in()) {
    header('Location: ' . base_url('pelapor/dashboard.php'));
    exit;
}

$errors = [];
$formData = [
    'tipe_pelapor' => $_POST['tipe_pelapor'] ?? 'siswa',
    'nama_lengkap' => $_POST['nama_lengkap'] ?? '',
    'username'     => $_POST['username'] ?? '',
    'email'        => $_POST['email'] ?? '',
    'no_telp'      => $_POST['no_telp'] ?? '',
    'nomor_identitas' => $_POST['nomor_identitas'] ?? '',
    'alamat'       => $_POST['alamat'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipe_pelapor   = trim($_POST['tipe_pelapor'] ?? 'siswa');
    $nama_lengkap   = trim($_POST['nama_lengkap'] ?? '');
    $username       = strtolower(trim($_POST['username'] ?? ''));
    $email          = strtolower(trim($_POST['email'] ?? ''));
    $no_telp        = trim($_POST['no_telp'] ?? '');
    $nomor_identitas= trim($_POST['nomor_identitas'] ?? '');
    $alamat         = trim($_POST['alamat'] ?? '');
    $password       = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validasi input
    if (empty($nama_lengkap)) {
        $errors[] = 'Nama lengkap wajib diisi.';
    }
    if (empty($username)) {
        $errors[] = 'Username wajib diisi.';
    } elseif (!preg_match('/^[a-z0-9_]{4,20}$/', $username)) {
        $errors[] = 'Username hanya boleh huruf kecil, angka, dan underscore (4 - 20 karakter).';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Alamat email valid wajib diisi.';
    }
    if (empty($no_telp)) {
        $errors[] = 'Nomor WhatsApp / HP aktif wajib diisi untuk verifikasi laporan.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Kata sandi minimal 6 karakter.';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'Konfirmasi kata sandi tidak cocok.';
    }

    if (empty($errors)) {
        $db = get_db_connection();

        // Cek duplikasi username & email
        $checkStmt = $db->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
        $checkStmt->execute([$username, $email]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            if ($existing['username'] === $username) {
                $errors[] = 'Username "' . htmlspecialchars($username) . '" sudah digunakan. Silakan pilih username lain.';
            } else {
                $errors[] = 'Alamat email "' . htmlspecialchars($email) . '" sudah terdaftar.';
            }
        } else {
            // Hash password & Simpan user
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            $insertStmt = $db->prepare("
                INSERT INTO users 
                (username, password, nama_lengkap, email, no_telp, role, tipe_pelapor, nomor_identitas, alamat, status_aktif) 
                VALUES (?, ?, ?, ?, ?, 'pelapor', ?, ?, ?, 1)
            ");
            
            $success = $insertStmt->execute([
                $username,
                $hashedPassword,
                $nama_lengkap,
                $email,
                $no_telp,
                $tipe_pelapor,
                $nomor_identitas,
                $alamat
            ]);

            if ($success) {
                $newUserId = $db->lastInsertId();

                // Auto login setelah pendaftaran berhasil
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['username'] = $username;
                $_SESSION['nama_lengkap'] = $nama_lengkap;
                $_SESSION['role'] = 'pelapor';
                $_SESSION['tipe_pelapor'] = $tipe_pelapor;
                $_SESSION['email'] = $email;
                $_SESSION['no_telp'] = $no_telp;

                set_flash('success', 'Pendaftaran akun berhasil! Anda kini dapat menyampaikan pengaduan atau laporan.');
                header('Location: ' . base_url('pelapor/dashboard.php'));
                exit;
            } else {
                $errors[] = 'Terjadi kesalahan sistem saat menyimpan data pendaftaran.';
            }
        }
    }
}

$pageTitle = 'Pendaftaran Akun Pelapor';
$activeNav = 'register';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-10 px-4 sm:px-6 lg:px-8 bg-slate-50 min-h-[85vh]">
    <div class="max-w-2xl mx-auto">
        <!-- Form Container -->
        <div class="bg-white p-6 sm:p-10 rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-100">
            <div class="text-center mb-8">
                <a href="<?= base_url('index.php') ?>" class="inline-block">
                    <img src="<?= base_url('asset/img/logo.png') ?>" alt="Logo SMKN 1 Cipeundeuy" class="h-16 w-16 mx-auto object-contain drop-shadow">
                </a>
                <h2 class="mt-4 text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    Registrasi Akun Pelapor
                </h2>
                <p class="mt-1 text-xs sm:text-sm text-slate-500 font-medium">
                    Untuk Siswa, Orang Tua / Wali Murid, dan Masyarakat Umum SMKN 1 Cipeundeuy
                </p>
                <div class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-800 border border-sky-200">
                    <i class="fa-solid fa-shield-halved text-sky-600"></i> Akun terverifikasi mencegah pelaporan fiktif / spam
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                    <div class="font-bold flex items-center gap-2 mb-1.5 text-rose-800">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500"></i> Harap perbaiki beberapa hal berikut:
                    </div>
                    <ul class="list-disc pl-5 space-y-1">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-5">
                <!-- Tipe Pelapor Selector -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Status / Kategori Pelapor <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="cursor-pointer border rounded-2xl p-3 text-center transition flex flex-col items-center justify-center gap-1 hover:border-sky-400 has-[:checked]:border-smk-blue has-[:checked]:bg-sky-50/70 has-[:checked]:text-smk-blue">
                            <input type="radio" name="tipe_pelapor" value="siswa" class="sr-only" <?= $formData['tipe_pelapor'] === 'siswa' ? 'checked' : '' ?>>
                            <i class="fa-solid fa-user-graduate text-lg"></i>
                            <span class="text-xs font-bold">Siswa</span>
                            <span class="text-[10px] text-slate-500">Pelajar Aktif</span>
                        </label>
                        <label class="cursor-pointer border rounded-2xl p-3 text-center transition flex flex-col items-center justify-center gap-1 hover:border-sky-400 has-[:checked]:border-smk-blue has-[:checked]:bg-sky-50/70 has-[:checked]:text-smk-blue">
                            <input type="radio" name="tipe_pelapor" value="wali_murid" class="sr-only" <?= $formData['tipe_pelapor'] === 'wali_murid' ? 'checked' : '' ?>>
                            <i class="fa-solid fa-users text-lg"></i>
                            <span class="text-xs font-bold">Wali Murid</span>
                            <span class="text-[10px] text-slate-500">Orang Tua Siswa</span>
                        </label>
                        <label class="cursor-pointer border rounded-2xl p-3 text-center transition flex flex-col items-center justify-center gap-1 hover:border-sky-400 has-[:checked]:border-smk-blue has-[:checked]:bg-sky-50/70 has-[:checked]:text-smk-blue">
                            <input type="radio" name="tipe_pelapor" value="masyarakat" class="sr-only" <?= $formData['tipe_pelapor'] === 'masyarakat' ? 'checked' : '' ?>>
                            <i class="fa-solid fa-building-user text-lg"></i>
                            <span class="text-xs font-bold">Masyarakat</span>
                            <span class="text-[10px] text-slate-500">Warga / Umum</span>
                        </label>
                    </div>
                </div>

                <!-- Nama Lengkap & Nomor Identitas -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nama_lengkap" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Nama Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($formData['nama_lengkap']) ?>" required
                            placeholder="Contoh: Muhammad Rizky"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                    </div>
                    <div>
                        <label for="nomor_identitas" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            NISN / NIK KTP <span class="text-slate-400 font-normal">(Opsional)</span>
                        </label>
                        <input type="text" id="nomor_identitas" name="nomor_identitas" value="<?= htmlspecialchars($formData['nomor_identitas']) ?>"
                            placeholder="NISN bagi Siswa atau NIK"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                    </div>
                </div>

                <!-- Username & No Telp -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="username" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Username <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($formData['username']) ?>" required
                            placeholder="huruf kecil & angka (cth: rizky26)"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                    </div>
                    <div>
                        <label for="no_telp" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Nomor WhatsApp / HP <span class="text-rose-500">*</span>
                        </label>
                        <input type="tel" id="no_telp" name="no_telp" value="<?= htmlspecialchars($formData['no_telp']) ?>" required
                            placeholder="Contoh: 081234567890"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                    </div>
                </div>

                <!-- Email & Alamat -->
                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Alamat Email Aktif <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" required
                        placeholder="contoh: rizky@gmail.com"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                </div>

                <div>
                    <label for="alamat" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Alamat Tempat Tinggal / Kelas <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <textarea id="alamat" name="alamat" rows="2"
                        placeholder="Contoh: Kelas XI TKRO 2 atau Ds. Sirnagalih RT 01/RW 03 Cipeundeuy"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition"><?= htmlspecialchars($formData['alamat']) ?></textarea>
                </div>

                <!-- Password & Konfirmasi -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Kata Sandi <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" id="password" name="password" required minlength="6"
                            placeholder="Minimal 6 karakter"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                    </div>
                    <div>
                        <label for="password_confirm" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Ulangi Kata Sandi <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="6"
                            placeholder="Ketik ulang kata sandi"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                    </div>
                </div>

                <div class="p-3 bg-sky-50 rounded-xl border border-sky-100 text-[11px] text-sky-800 flex items-start gap-2">
                    <i class="fa-solid fa-circle-info text-sky-600 mt-0.5"></i>
                    <span>Dengan mendaftar, Anda menyatakan bahwa data yang diberikan adalah benar dan berkomitmen menyampaikan laporan yang dapat dipertanggungjawabkan demi kemajuan bersama SMKN 1 Cipeundeuy.</span>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3.5 px-4 bg-smk-blue hover:bg-smk-blue-hover text-white font-extrabold rounded-xl shadow-lg hover:shadow-xl transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-user-check"></i>
                        <span>Selesaikan Pendaftaran & Masuk</span>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-600">
                    Sudah memiliki akun sebelumnya?
                    <a href="<?= base_url('auth/login.php') ?>" class="text-smk-blue font-bold hover:underline">
                        Masuk di Sini
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

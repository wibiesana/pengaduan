<?php
// auth/login.php
require_once __DIR__ . '/../config/helpers.php';

if (is_logged_in()) {
    $role = $_SESSION['role'] ?? 'pelapor';
    if (in_array($role, ['admin', 'petugas'])) {
        header('Location: ' . base_url('admin/dashboard.php'));
    } else {
        header('Location: ' . base_url('pelapor/dashboard.php'));
    }
    exit;
}

$error = '';
$username_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $username_val = $identifier;

    if (empty($identifier) || empty($password)) {
        $error = 'Silakan isi username/email dan password Anda!';
    } else {
        $db = get_db_connection();
        // Cari user berdasarkan username ATAU email
        $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ((int)$user['status_aktif'] !== 1) {
                $error = 'Akun Anda sedang dinonaktifkan oleh administrator. Silakan hubungi pihak sekolah.';
            } else {
                // Set sesi login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['tipe_pelapor'] = $user['tipe_pelapor'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['no_telp'] = $user['no_telp'];

                set_flash('success', 'Selamat datang kembali, ' . $user['nama_lengkap'] . '!');

                // Jika user adalah admin atau petugas, SELALU arahkan langsung ke Dashboard Admin
                if (in_array($user['role'], ['admin', 'petugas'])) {
                    header('Location: ' . base_url('admin/dashboard.php'));
                    exit;
                }

                // Untuk pelapor, cek apakah ada redirect sebelumnya (misal sedang buat laporan)
                $redirect = $_GET['redirect'] ?? '';
                if (!empty($redirect) && strpos($redirect, 'http') === false && strpos($redirect, 'admin') === false) {
                    header('Location: ' . base_url($redirect));
                    exit;
                }

                header('Location: ' . base_url('pelapor/dashboard.php'));
                exit;
            }
        } else {
            $error = 'Username/email atau kata sandi yang Anda masukkan salah.';
        }
    }
}

$pageTitle = 'Masuk Akun Pengaduan';
$activeNav = 'login';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="max-w-md w-full space-y-6">
        <!-- Card Login -->
        <div class="bg-white p-8 rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100">
            <div class="text-center mb-6">
                <a href="<?= base_url('index.php') ?>" class="inline-block">
                    <img src="<?= base_url('asset/img/logo.png') ?>" alt="Logo SMKN 1 Cipeundeuy" class="h-16 w-16 mx-auto object-contain drop-shadow">
                </a>
                <h2 class="mt-4 text-2xl font-black text-slate-900 tracking-tight">
                    Masuk Layanan Pengaduan
                </h2>
                <p class="mt-1 text-xs text-slate-500 font-medium">
                    SMKN 1 Cipeundeuy • Wajib masuk sebelum menyampaikan laporan
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-base flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="identifier" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Username atau Email
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="fa-solid fa-user text-sm"></i>
                        </span>
                        <input type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($username_val) ?>" required autofocus
                            placeholder="Masukkan username atau email"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Kata Sandi (Password)
                        </label>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </span>
                        <input type="password" id="password" name="password" required
                            placeholder="••••••••"
                            class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-eye" id="pwdIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3 px-4 bg-smk-blue hover:bg-smk-blue-hover text-white font-bold rounded-xl shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        <span>Masuk ke Akun</span>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-600">
                    Belum memiliki akun pelapor?
                    <a href="<?= base_url('auth/register.php') ?>" class="text-smk-blue font-bold hover:underline">
                        Daftar Akun Baru Sekarang
                    </a>
                </p>
            </div>
        </div>


    </div>
</div>

<script>
function togglePasswordVisibility() {
    const pwdInput = document.getElementById('password');
    const pwdIcon = document.getElementById('pwdIcon');
    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        pwdIcon.classList.remove('fa-eye');
        pwdIcon.classList.add('fa-eye-slash');
    } else {
        pwdInput.type = 'password';
        pwdIcon.classList.remove('fa-eye-slash');
        pwdIcon.classList.add('fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

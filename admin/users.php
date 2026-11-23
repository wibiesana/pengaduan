<?php
// admin/users.php
require_once __DIR__ . '/../config/helpers.php';
require_role(['admin']);

$db = get_db_connection();
$user = current_user();

// Toggle Status Aktif User
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $targetId = (int)$_GET['id'];
    $newStatus = (int)$_GET['toggle_status'];

    // Cegah admin menonaktifkan dirinya sendiri
    if ($targetId === (int)$user['id']) {
        set_flash('danger', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
    } else {
        $stmtToggle = $db->prepare("UPDATE users SET status_aktif = ? WHERE id = ?");
        $stmtToggle->execute([$newStatus, $targetId]);
        set_flash('success', 'Status akun pengguna berhasil diperbarui.');
    }
    header('Location: ' . base_url('admin/users.php'));
    exit;
}

$search = trim($_GET['search'] ?? '');
$tipe   = $_GET['tipe'] ?? 'all';

$sql = "
    SELECT u.*, COUNT(p.id) as total_laporan
    FROM users u
    LEFT JOIN pengaduan p ON u.id = p.user_id
    WHERE u.role = 'pelapor'
";
$params = [];

if (in_array($tipe, ['siswa', 'wali_murid', 'masyarakat'])) {
    $sql .= " AND u.tipe_pelapor = ?";
    $params[] = $tipe;
}

if (!empty($search)) {
    $sql .= " AND (u.nama_lengkap LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.nomor_identitas LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$sql .= " GROUP BY u.id ORDER BY u.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

$pageTitle = 'Manajemen Pengguna Pelapor';
$activeMenu = 'users';
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
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">Data Pengguna & Pelapor Terdaftar</h1>
                <p class="text-xs text-slate-500 mt-0.5">Mencegah akun palsu dan memantau keaktifan pelapor</p>
            </div>
        </header>

        <!-- Body -->
        <main class="p-6 sm:p-8 space-y-6">
            
            <!-- Filter Bar -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                <form method="GET" action="" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1 relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, NISN/NIK, email, atau username..."
                            class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>

                    <select name="tipe" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:outline-none">
                        <option value="all">Semua Kategori</option>
                        <option value="siswa" <?= $tipe === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                        <option value="wali_murid" <?= $tipe === 'wali_murid' ? 'selected' : '' ?>>Wali Murid</option>
                        <option value="masyarakat" <?= $tipe === 'masyarakat' ? 'selected' : '' ?>>Masyarakat</option>
                    </select>

                    <button type="submit" class="px-5 py-2 bg-smk-blue hover:bg-smk-blue-hover text-white font-bold text-xs rounded-xl transition flex items-center justify-center gap-1.5 shadow-xs">
                        <i class="fa-solid fa-filter"></i> Cari
                    </button>
                </form>
            </div>

            <!-- Tabel Pengguna -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
                <?php if (empty($usersList)): ?>
                    <div class="py-16 text-center text-slate-400 text-xs">
                        <i class="fa-solid fa-users-slash text-3xl text-slate-300 mb-2"></i>
                        <p class="font-bold text-slate-700">Tidak Ada Pengguna Ditemukan</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th class="py-3.5 px-6">Pengguna</th>
                                    <th class="py-3.5 px-6">Kategori</th>
                                    <th class="py-3.5 px-6">Identitas (NISN/NIK)</th>
                                    <th class="py-3.5 px-6">Kontak</th>
                                    <th class="py-3.5 px-6 text-center">Jml Laporan</th>
                                    <th class="py-3.5 px-6">Status Akun</th>
                                    <th class="py-3.5 px-6 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                <?php foreach ($usersList as $u): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-sky-100 text-smk-blue font-bold flex items-center justify-center text-xs">
                                                    <?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <span class="font-extrabold text-slate-900 block"><?= htmlspecialchars($u['nama_lengkap']) ?></span>
                                                    <span class="text-[11px] text-slate-400">@<?= htmlspecialchars($u['username']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold capitalize <?= $u['tipe_pelapor'] === 'siswa' ? 'bg-sky-50 text-sky-800 border border-sky-200' : ($u['tipe_pelapor'] === 'wali_murid' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-800') ?>">
                                                <?= htmlspecialchars($u['tipe_pelapor']) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 font-mono text-slate-700">
                                            <?= htmlspecialchars($u['nomor_identitas'] ?: '-') ?>
                                        </td>
                                        <td class="py-4 px-6">
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $u['no_telp']) ?>" target="_blank" class="text-emerald-600 font-semibold block hover:underline">
                                                <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($u['no_telp']) ?>
                                            </a>
                                            <span class="text-[11px] text-slate-400 block"><?= htmlspecialchars($u['email']) ?></span>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <span class="font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded-full text-[11px]">
                                                <?= $u['total_laporan'] ?> laporan
                                            </span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <?php if ((int)$u['status_aktif'] === 1): ?>
                                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                                    <i class="fa-solid fa-circle-check text-[9px]"></i> Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700 bg-rose-50 px-2.5 py-0.5 rounded-full border border-rose-200">
                                                    <i class="fa-solid fa-ban text-[9px]"></i> Dinonaktifkan
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <?php if ((int)$u['status_aktif'] === 1): ?>
                                                <a href="<?= base_url('admin/users.php?toggle_status=0&id=' . $u['id']) ?>" onclick="return confirm('Nonaktifkan akun pengguna ini?')" class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition">
                                                    Nonaktifkan
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= base_url('admin/users.php?toggle_status=1&id=' . $u['id']) ?>" onclick="return confirm('Aktifkan kembali akun ini?')" class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition">
                                                    Aktifkan
                                                </a>
                                            <?php endif; ?>
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

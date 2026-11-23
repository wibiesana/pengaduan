<?php
// includes/header.php
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/helpers.php';
}

$currentUser = current_user();
$flash = get_flash();
$pageTitle = $pageTitle ?? 'Sistem Pengaduan Sekolah - SMKN 1 Cipeundeuy';
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | SMKN 1 Cipeundeuy</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= base_url('asset/img/logo.png') ?>">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        smk: {
                            blue: '#0284c7',        // Sky 600 - Main shield blue
                            'blue-hover': '#0369a1',
                            'blue-dark': '#075985',
                            'blue-deep': '#0c4a6e', // Dark navy
                            'blue-light': '#e0f2fe',
                            yellow: '#f59e0b',      // Amber 500 - Kujang gold
                            'yellow-hover': '#d97706',
                            'yellow-light': '#fef3c7',
                            green: '#10b981',       // Mountain green
                            'green-light': '#d1fae5'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .gradient-smk {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 50%, #0c4a6e 100%);
        }
        .gradient-card-umum {
            background: linear-gradient(135deg, #e0f2fe 0%, #ffffff 100%);
        }
        .gradient-card-bullying {
            background: linear-gradient(135deg, #fee2e2 0%, #ffffff 100%);
        }
    </style>
</head>
<body class="flex flex-col min-h-screen text-slate-800 antialiased selection:bg-sky-500 selection:text-white">

    <!-- Top Announcement Bar / Helpline TPPK -->
    <header class="bg-slate-900 text-white text-[11px] sm:text-xs py-2 px-4 border-b border-slate-800">
        <div class="max-w-7xl mx-auto flex flex-wrap justify-between items-center gap-2">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center bg-amber-400 text-slate-950 font-black px-2 py-0.5 rounded text-[10px] tracking-wide uppercase shadow-sm">
                    <i class="fa-solid fa-bullhorn mr-1"></i> TPPK SMKN 1 CIPEUNDEUY
                </span>
                <span class="text-slate-300 hidden sm:inline">Layanan Aspirasi, Pengaduan Sarana & Perlindungan dari Tindak Kekerasan</span>
            </div>
            <div class="flex items-center gap-3 text-slate-300">
                <a href="tel:02263200004" class="hover:text-amber-400 transition flex items-center gap-1">
                    <i class="fa-solid fa-phone text-[10px]"></i> <span>Telp. 02263200004</span>
                </a>
                <span class="text-slate-600">|</span>
                <a href="mailto:smkn1cpdykbb@gmail.com" class="hover:text-sky-300 transition flex items-center gap-1">
                    <i class="fa-solid fa-envelope text-[10px]"></i> <span class="hidden sm:inline">smkn1cpdykbb@gmail.com</span>
                </a>
                <span class="text-slate-600">|</span>
                <a href="https://wa.me/6281234567890" target="_blank" class="hover:text-emerald-300 transition flex items-center gap-1 font-semibold text-emerald-400">
                    <i class="fa-brands fa-whatsapp text-xs"></i> <span>Hotline Pengaduan</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Navigation Bar -->
    <nav class="sticky top-0 z-40 bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                
                <!-- Brand / Logo -->
                <div class="flex items-center shrink-0">
                    <a href="<?= base_url('index.php') ?>" class="flex items-center gap-2.5 sm:gap-3 group">
                        <div class="w-10 h-10 sm:w-11 sm:h-11 flex-shrink-0 flex items-center justify-center">
                            <img src="<?= base_url('asset/img/logo.png') ?>" alt="Logo SMKN 1 Cipeundeuy" class="max-w-full max-h-full object-contain drop-shadow group-hover:scale-105 transition-transform duration-200">
                        </div>
                        <div class="leading-tight">
                            <div class="flex items-center gap-1.5">
                                <span class="font-black text-base sm:text-lg text-slate-900 tracking-tight group-hover:text-smk-blue transition whitespace-nowrap">
                                    SIPAS <span class="text-smk-blue">CIPEUNDEUY</span>
                                </span>
                                <span class="hidden 2xl:inline-block px-1.5 py-0.5 text-[9px] uppercase font-extrabold tracking-wider bg-sky-100 text-sky-800 rounded-md border border-sky-200">
                                    Resmi
                                </span>
                            </div>
                            <p class="text-[10px] sm:text-[11px] text-slate-500 font-medium whitespace-nowrap">
                                SMKN 1 Cipeundeuy • KBB
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links (Desktop) -->
                <div class="hidden lg:flex items-center space-x-1 xl:space-x-2">
                    <a href="<?= base_url('index.php') ?>" class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs xl:text-sm font-bold <?= (!isset($activeNav) || $activeNav === 'home') ? 'text-smk-blue bg-sky-50' : 'text-slate-600 hover:text-smk-blue hover:bg-slate-50' ?> transition">
                        <i class="fa-solid fa-house text-sm"></i>
                        <span>Beranda</span>
                    </a>
                    
                    <a href="<?= base_url('pelapor/buat-laporan.php?jenis=umum') ?>" class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs xl:text-sm font-bold <?= (isset($activeNav) && $activeNav === 'lapor_umum') ? 'text-smk-blue bg-sky-50' : 'text-slate-600 hover:text-smk-blue hover:bg-slate-50' ?> transition">
                        <i class="fa-solid fa-bullhorn text-sky-500 text-sm"></i>
                        <span>Pengaduan Umum</span>
                    </a>

                    <a href="<?= base_url('pelapor/buat-laporan.php?jenis=kekerasan_bullying') ?>" class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs xl:text-sm font-bold <?= (isset($activeNav) && $activeNav === 'lapor_bullying') ? 'text-rose-600 bg-rose-50' : 'text-rose-600 hover:text-rose-700 hover:bg-rose-50' ?> transition">
                        <i class="fa-solid fa-shield-cat text-rose-500 text-sm"></i>
                        <span>Lapor Bullying</span>
                    </a>

                    <a href="<?= base_url('index.php#alur') ?>" class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs xl:text-sm font-bold text-slate-600 hover:text-smk-blue hover:bg-slate-50 transition">
                        <i class="fa-solid fa-diagram-project text-sm"></i>
                        <span>Alur Lapor</span>
                    </a>

                    <?php if ($currentUser): ?>
                        <!-- Logged In Menu -->
                        <div class="h-6 w-px bg-slate-200 mx-1"></div>
                        
                        <?php if (in_array($currentUser['role'], ['admin', 'petugas'])): ?>
                            <a href="<?= base_url('admin/dashboard.php') ?>" class="whitespace-nowrap inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs xl:text-sm font-extrabold bg-amber-400 text-slate-950 hover:bg-amber-300 transition shadow-xs">
                                <i class="fa-solid fa-gauge-high"></i>
                                <span>Panel Admin</span>
                            </a>
                        <?php else: ?>
                            <a href="<?= base_url('pelapor/dashboard.php') ?>" class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs xl:text-sm font-bold <?= (isset($activeNav) && $activeNav === 'pelapor_dashboard') ? 'text-smk-blue bg-sky-50' : 'text-slate-700 hover:text-smk-blue hover:bg-slate-50' ?> transition">
                                <i class="fa-solid fa-folder-open text-sky-600"></i>
                                <span>Laporan Saya</span>
                            </a>
                        <?php endif; ?>

                        <!-- User Profile Dropdown Button -->
                        <?php 
                            // Bersihkan nama agar rapi dan pas di navbar (contoh: "Ahmad Fauzi (Siswa XII TKRO)" -> "Ahmad Fauzi")
                            $displayName = explode(' (', $currentUser['nama_lengkap'])[0];
                        ?>
                        <div class="relative group ml-1 shrink-0">
                            <button class="flex items-center gap-2 p-1.5 px-2.5 rounded-xl border border-slate-200 hover:border-sky-300 hover:bg-sky-50 transition text-left">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-sky-600 to-amber-400 text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                                    <?= strtoupper(substr($displayName, 0, 1)) ?>
                                </div>
                                <div class="hidden sm:block text-left leading-tight">
                                    <div class="text-xs font-extrabold text-slate-800 whitespace-nowrap"><?= htmlspecialchars($displayName) ?></div>
                                    <div class="text-[10px] text-sky-700 font-semibold capitalize whitespace-nowrap"><?= htmlspecialchars($currentUser['tipe_pelapor'] ?? 'Pelapor') ?></div>
                                </div>
                                <i class="fa-solid fa-chevron-down text-slate-400 text-[10px] ml-0.5"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 hidden group-hover:block transition-all z-50">
                                <div class="px-4 py-2 border-b border-slate-100">
                                    <p class="text-[11px] text-slate-400">Masuk sebagai:</p>
                                    <p class="text-xs font-extrabold text-slate-800 truncate"><?= htmlspecialchars($currentUser['nama_lengkap']) ?></p>
                                    <span class="inline-block px-2 py-0.5 text-[9px] font-bold rounded-full bg-sky-100 text-sky-800 mt-1 capitalize">
                                        <?= $currentUser['role'] ?> • <?= $currentUser['tipe_pelapor'] ?? 'Umum' ?>
                                    </span>
                                </div>
                                <?php if ($currentUser['role'] === 'pelapor'): ?>
                                    <a href="<?= base_url('pelapor/dashboard.php') ?>" class="block px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-sky-50 hover:text-smk-blue">
                                        <i class="fa-solid fa-list-check w-5 text-slate-400"></i> Riwayat Pengaduan
                                    </a>
                                    <a href="<?= base_url('pelapor/buat-laporan.php') ?>" class="block px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-sky-50 hover:text-smk-blue">
                                        <i class="fa-solid fa-plus-circle w-5 text-slate-400"></i> Buat Laporan Baru
                                    </a>
                                <?php else: ?>
                                    <a href="<?= base_url('admin/dashboard.php') ?>" class="block px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-sky-50 hover:text-smk-blue">
                                        <i class="fa-solid fa-gauge-high w-5 text-slate-400"></i> Dashboard Admin
                                    </a>
                                    <a href="<?= base_url('admin/laporan.php') ?>" class="block px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-sky-50 hover:text-smk-blue">
                                        <i class="fa-solid fa-inbox w-5 text-slate-400"></i> Kelola Semua Laporan
                                    </a>
                                <?php endif; ?>
                                <div class="border-t border-slate-100 my-1"></div>
                                <a href="<?= base_url('auth/logout.php') ?>" class="block px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50">
                                    <i class="fa-solid fa-right-from-bracket w-5 text-rose-400"></i> Keluar (Logout)
                                </a>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- Guest Menu -->
                        <div class="flex items-center space-x-2 ml-2">
                            <a href="<?= base_url('auth/login.php') ?>" class="whitespace-nowrap inline-flex items-center gap-1.5 px-3.5 py-2 text-xs lg:text-sm font-bold text-smk-blue hover:text-smk-blue-dark hover:bg-sky-50 rounded-xl transition border border-transparent hover:border-sky-200">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                <span>Masuk</span>
                            </a>
                            <a href="<?= base_url('auth/register.php') ?>" class="whitespace-nowrap inline-flex items-center gap-1.5 px-4 py-2 text-xs lg:text-sm font-extrabold bg-smk-blue hover:bg-smk-blue-hover text-white rounded-xl shadow-xs transition">
                                <i class="fa-solid fa-user-plus text-xs"></i>
                                <span>Daftar Akun</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex items-center md:hidden">
                    <button type="button" id="mobile-menu-btn" class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none">
                        <i class="fa-solid fa-bars text-xl" id="menu-icon"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation Drawer -->
            <div id="mobile-menu" class="hidden md:hidden border-t border-slate-200 py-3 space-y-1">
                <a href="<?= base_url('index.php') ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-sky-50 hover:text-smk-blue">
                    <i class="fa-solid fa-house w-5 text-slate-400"></i>
                    <span>Beranda</span>
                </a>
                <a href="<?= base_url('pelapor/buat-laporan.php?jenis=umum') ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-sky-50 hover:text-smk-blue">
                    <i class="fa-solid fa-bullhorn w-5 text-sky-500"></i>
                    <span>Pengaduan Umum</span>
                </a>
                <a href="<?= base_url('pelapor/buat-laporan.php?jenis=kekerasan_bullying') ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-bold text-rose-600 hover:bg-rose-50">
                    <i class="fa-solid fa-shield-cat w-5 text-rose-500"></i>
                    <span>Lapor Bullying & Kekerasan</span>
                </a>
                <a href="<?= base_url('index.php#alur') ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-sky-50">
                    <i class="fa-solid fa-diagram-project w-5 text-slate-400"></i>
                    <span>Alur Lapor</span>
                </a>

                <div class="border-t border-slate-200 pt-3 mt-2">
                    <?php if ($currentUser): ?>
                        <div class="px-3 py-2.5 bg-sky-50 rounded-xl mb-2 flex items-center justify-between">
                            <div>
                                <p class="text-[10px] text-slate-400">Login sebagai:</p>
                                <p class="text-xs font-black text-slate-800"><?= htmlspecialchars($currentUser['nama_lengkap']) ?></p>
                            </div>
                            <span class="text-[10px] text-smk-blue uppercase font-bold bg-white px-2 py-0.5 rounded border border-sky-200"><?= $currentUser['role'] ?></span>
                        </div>
                        <?php if (in_array($currentUser['role'], ['admin', 'petugas'])): ?>
                            <a href="<?= base_url('admin/dashboard.php') ?>" class="block px-3 py-2 rounded-xl text-xs font-bold bg-amber-400 text-slate-950 mb-1">
                                <i class="fa-solid fa-gauge-high mr-2"></i> Panel Dashboard Admin
                            </a>
                        <?php else: ?>
                            <a href="<?= base_url('pelapor/dashboard.php') ?>" class="block px-3 py-2 rounded-xl text-xs font-bold text-slate-700 hover:bg-sky-50 mb-1">
                                <i class="fa-solid fa-folder-open mr-2 text-sky-600"></i> Riwayat Pengaduan Saya
                            </a>
                        <?php endif; ?>
                        <a href="<?= base_url('auth/logout.php') ?>" class="block px-3 py-2 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50">
                            <i class="fa-solid fa-right-from-bracket mr-2"></i> Keluar (Logout)
                        </a>
                    <?php else: ?>
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            <a href="<?= base_url('auth/login.php') ?>" class="text-center py-2 px-3 text-xs font-bold text-smk-blue bg-sky-50 rounded-xl border border-sky-200">
                                Masuk
                            </a>
                            <a href="<?= base_url('auth/register.php') ?>" class="text-center py-2 px-3 text-xs font-bold text-white bg-smk-blue rounded-xl">
                                Daftar Akun
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Wrapper -->
    <main class="flex-grow">

<?php
// admin/index.php
// Penangan akses langsung ke URL /admin/
require_once __DIR__ . '/../config/helpers.php';

if (!is_logged_in()) {
    set_flash('info', 'Silakan masuk terlebih dahulu dengan akun administrator.');
    header('Location: ' . base_url('auth/login.php?redirect=admin/dashboard.php'));
    exit;
}

if (!in_array($_SESSION['role'] ?? '', ['admin', 'petugas'])) {
    set_flash('danger', 'Akses ditolak: Hanya administrator dan petugas yang dapat mengakses panel admin.');
    header('Location: ' . base_url('index.php'));
    exit;
}

header('Location: ' . base_url('admin/dashboard.php'));
exit;

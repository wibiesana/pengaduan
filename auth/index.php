<?php
// auth/index.php
// Mengarahkan akses folder /auth/ langsung ke login
require_once __DIR__ . '/../config/helpers.php';
header('Location: ' . base_url('auth/login.php'));
exit;

<?php
// pelapor/index.php
// Mengarahkan akses folder /pelapor/ langsung ke dashboard pelapor
require_once __DIR__ . '/../config/helpers.php';
header('Location: ' . base_url('pelapor/dashboard.php'));
exit;

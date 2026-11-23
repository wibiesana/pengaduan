<?php
// uploads/index.php
// Mencegah directory listing
header('HTTP/1.0 403 Forbidden');
exit('Akses langsung ke direktori ini tidak diizinkan.');

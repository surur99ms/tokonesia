<?php
// ============================================================
// WEB MIGRATION RUNNER (KHUSUS SHARED HOSTING)
// ============================================================
// Gunakan file ini HANYA jika cPanel Anda tidak memiliki fitur Terminal.
// Akses via browser: https://domainanda.com/migrate_web.php?token=hoster123

$token = $_GET['token'] ?? '';
$validToken = 'hoster123'; // Ubah token ini sesuai keinginan Anda agar aman

if ($token !== $validToken) {
    header('HTTP/1.0 403 Forbidden');
    die("Akses ditolak. Token tidak valid.");
}

echo "<!DOCTYPE html><html><head><title>Tokonesia - Web Migration</title>";
echo "<style>body{background:#1e1e1e;color:#00ff00;font-family:monospace;padding:20px;line-height:1.5}</style>";
echo "</head><body>";
echo "<h3>Memulai Database Migration...</h3>";
echo "<pre>";

// Mendapatkan path executable PHP yang digunakan oleh server web
$php_bin = PHP_BINARY;

// Jika server memblokir shell_exec, kita bisa fallback jalankan langsung via output buffering
if (function_exists('shell_exec') && is_callable('shell_exec')) {
    echo "Metode: shell_exec (CLI)\n";
    echo "Command: $php_bin migrate.php migrate\n\n";
    $output = shell_exec("$php_bin migrate.php migrate 2>&1");
    if ($output === null) {
        echo "[ERROR] shell_exec mengembalikan null. Mencoba metode require...\n";
        $fallback = true;
    } else {
        echo htmlspecialchars($output);
        $fallback = false;
    }
} else {
    echo "Metode shell_exec diblokir oleh hosting. Mencoba metode require...\n\n";
    $fallback = true;
}

if ($fallback) {
    // Memanipulasi $argv agar script migrate.php mengira ia dijalankan dari CLI
    global $argv;
    $argv = ['migrate.php', 'migrate'];
    
    // Tangkap output langsung
    ob_start();
    try {
        require __DIR__ . '/migrate.php';
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    $output = ob_get_clean();
    echo htmlspecialchars($output);
}

echo "</pre>";
echo "<h3>Selesai.</h3>";
echo "<p style='color:red'>PENTING: Segera hapus atau ganti nama file ini setelah selesai digunakan untuk alasan keamanan!</p>";
echo "</body></html>";

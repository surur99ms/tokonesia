<?php
// ============================================================
// Tokonesia — Web Migration Trigger & Status
// ============================================================
$valid_token = 'hoster123';
$request_token = $_GET['token'] ?? '';

if ($request_token !== $valid_token) {
    http_response_code(403);
    die("Akses Ditolak: Token tidak valid.");
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tokonesia - Web Migration</title>
    <style>
        body { background: #121212; color: #00ff66; font-family: monospace; padding: 25px; line-height: 1.6; }
        pre { background: #1e1e1e; padding: 15px; border-radius: 8px; border: 1px solid #333; color: #fff; overflow-x: auto; }
        .btn { display: inline-block; padding: 8px 16px; background: #00ff66; color: #000; text-decoration: none; font-weight: bold; border-radius: 4px; margin-right: 10px; }
        .btn:hover { background: #00cc52; }
    </style>
</head>
<body>
    <h2>🚀 Tokonesia — Database Migration Console</h2>
    <div style="margin-bottom: 20px;">
        <a class="btn" href="?token=<?= htmlspecialchars($valid_token) ?>&action=migrate">Jalankan Migrasi (Migrate)</a>
        <a class="btn" href="?token=<?= htmlspecialchars($valid_token) ?>&action=status">Cek Status Migrasi</a>
    </div>
    <pre><?php
    // Jalankan runner migrasi langsung
    require_once __DIR__ . '/migrate.php';
    ?></pre>
</body>
</html>

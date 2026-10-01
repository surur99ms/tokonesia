<?php
// ============================================================
// TOKONESIA — Admin: Hapus Gambar Produk (AJAX)
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

header('Content-Type: application/json');
$id = (int)($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['success' => false]); exit; }

$db   = getDB();
$stmt = $db->prepare('SELECT image_path FROM product_images WHERE id = ?');
$stmt->execute([$id]);
$img  = $stmt->fetch();

if ($img) {
    $file = UPLOAD_DIR . $img['image_path'];
    if (file_exists($file)) unlink($file);
    $db->prepare('DELETE FROM product_images WHERE id = ?')->execute([$id]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gambar tidak ditemukan.']);
}

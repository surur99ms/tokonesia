<?php
// ============================================================
// TOKONESIA — Helper Functions
// ============================================================

require_once __DIR__ . '/config.php';

/**
 * Format harga ke format Rupiah
 */
function formatRupiah(float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Ambil setting dari database
 */
function getSetting(string $key, string $default = ''): string {
    static $settings = null;
    if ($settings === null) {
        $stmt = getDB()->query('SELECT `key`, `value` FROM `settings`');
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $settings[$key] ?? $default;
}

/**
 * Ambil URL gambar produk (primary / secondary)
 */
function getProductImageUrl(int $productId, bool $secondary = false): string {
    $db    = getDB();
    $order = $secondary ? 1 : 0;
    $stmt  = $db->prepare(
        'SELECT image_path FROM product_images
         WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC LIMIT 2'
    );
    $stmt->execute([$productId]);
    $rows = $stmt->fetchAll();

    if ($secondary && isset($rows[1])) {
        $path = $rows[1]['image_path'];
    } elseif (isset($rows[0])) {
        $path = $rows[0]['image_path'];
    } else {
        return BASE_URL . '/assets/images/placeholder.png';
    }

    $file = UPLOAD_DIR . $path;
    if (file_exists($file)) {
        return UPLOAD_URL . $path;
    }
    return BASE_URL . '/assets/images/placeholder.png';
}

/**
 * Generate slug dari string
 */
function makeSlug(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Jumlah item di keranjang (session based)
 */
function getCartCount(): int {
    if (!empty($_SESSION['user_id'])) {
        $stmt = getDB()->prepare('SELECT SUM(quantity) FROM cart WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $sid  = session_id();
        $stmt = getDB()->prepare('SELECT SUM(quantity) FROM cart WHERE session_id = ?');
        $stmt->execute([$sid]);
    }
    return (int) ($stmt->fetchColumn() ?: 0);
}

/**
 * Produk terlaris / featured
 */
function getFeaturedProducts(int $limit = 6): array {
    $stmt = getDB()->prepare(
        'SELECT p.*, c.name AS category_name,
                (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS img_primary,
                (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 0 ORDER BY sort_order LIMIT 1) AS img_secondary
         FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.is_featured = 1 AND p.stock > 0
         ORDER BY p.sold_count DESC
         LIMIT ?'
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Semua kategori
 */
function getAllCategories(): array {
    return getDB()->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();
}

/**
 * XSS-safe output
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

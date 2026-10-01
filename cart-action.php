<?php
// ============================================================
// TOKONESIA — Cart Action (AJAX Handler)
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

$action    = $_POST['action']     ?? '';
$productId = (int)($_POST['product_id'] ?? 0);
$qty       = max(1, (int)($_POST['qty'] ?? 1));
$db        = getDB();

$userId = $_SESSION['user_id'] ?? null;
$sid    = session_id();

function cartResponse(bool $success, string $msg = ''): void {
    $count = getCartCount();
    echo json_encode(['success' => $success, 'message' => $msg, 'cart_count' => $count]);
    exit;
}

switch ($action) {
    case 'add':
        // Cek stok
        $stk = $db->prepare('SELECT stock FROM products WHERE id = ?');
        $stk->execute([$productId]);
        $stock = (int)$stk->fetchColumn();
        if ($stock < 1) cartResponse(false, 'Stok habis.');

        // Cek sudah ada di cart?
        if ($userId) {
            $chk = $db->prepare('SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?');
            $chk->execute([$userId, $productId]);
        } else {
            $chk = $db->prepare('SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?');
            $chk->execute([$sid, $productId]);
        }
        $existing = $chk->fetch();

        if ($existing) {
            $newQty = min($stock, $existing['quantity'] + $qty);
            $db->prepare('UPDATE cart SET quantity = ? WHERE id = ?')->execute([$newQty, $existing['id']]);
        } else {
            if ($userId) {
                $db->prepare('INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)')->execute([$userId, $productId, $qty]);
            } else {
                $db->prepare('INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)')->execute([$sid, $productId, $qty]);
            }
        }
        cartResponse(true, 'Produk ditambahkan ke keranjang.');

    case 'update':
        $cartId = (int)($_POST['cart_id'] ?? 0);
        if ($qty < 1) {
            $db->prepare('DELETE FROM cart WHERE id = ?')->execute([$cartId]);
        } else {
            $db->prepare('UPDATE cart SET quantity = ? WHERE id = ?')->execute([$qty, $cartId]);
        }
        cartResponse(true);

    case 'remove':
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $db->prepare('DELETE FROM cart WHERE id = ?')->execute([$cartId]);
        cartResponse(true, 'Item dihapus dari keranjang.');

    case 'toggle_select':
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $isSelected = (int)($_POST['is_selected'] ?? 0);
        $db->prepare('UPDATE cart SET is_selected = ? WHERE id = ?')->execute([$isSelected, $cartId]);
        cartResponse(true);

    case 'toggle_all':
        $isSelected = (int)($_POST['is_selected'] ?? 0);
        if ($userId) {
            $db->prepare('UPDATE cart SET is_selected = ? WHERE user_id = ?')->execute([$isSelected, $userId]);
        } else {
            $db->prepare('UPDATE cart SET is_selected = ? WHERE session_id = ?')->execute([$isSelected, $sid]);
        }
        cartResponse(true);

    default:
        cartResponse(false, 'Aksi tidak valid.');
}

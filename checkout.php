<?php
// ============================================================
// TOKONESIA — Checkout
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Checkout — ' . getSetting('site_name', 'Tokonesia');
$db        = getDB();
$userId    = $_SESSION['user_id'] ?? null;
$sid       = session_id();
$waNumber  = getSetting('whatsapp_number', '6281234567890');
$siteName  = getSetting('site_name', 'Tokonesia');

// Ambil item cart
if ($userId) {
    $stmt = $db->prepare(
        'SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.stock
         FROM cart c JOIN products p ON p.id = c.product_id WHERE c.user_id = ? AND c.is_selected = 1'
    );
    $stmt->execute([$userId]);
} else {
    $stmt = $db->prepare(
        'SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.stock
         FROM cart c JOIN products p ON p.id = c.product_id WHERE c.session_id = ? AND c.is_selected = 1'
    );
    $stmt->execute([$sid]);
}
$items = $stmt->fetchAll();
$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));

if (empty($items)) {
    redirect(BASE_URL . '/cart.php');
}

$error  = null;
$success = false;
$orderCode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['customer_name']    ?? '');
    $phone   = trim($_POST['customer_phone']   ?? '');
    $address = trim($_POST['customer_address'] ?? '');
    $notes   = trim($_POST['notes']            ?? '');
    $method  = $_POST['payment_method']        ?? 'whatsapp';

    if (!$name || !$phone || !$address) {
        $error = 'Nama, nomor HP, dan alamat wajib diisi.';
    } else {
        // Generate kode pesanan
        $orderCode = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        try {
            $db->beginTransaction();

            // Insert order
            $ins = $db->prepare(
                'INSERT INTO orders (user_id, order_code, customer_name, customer_phone, customer_address, total_amount, status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, "pending", ?)'
            );
            $ins->execute([$userId, $orderCode, $name, $phone, $address, $total, $notes]);
            $orderId = $db->lastInsertId();

            // Insert order items & update stock/sold_count
            $insItem  = $db->prepare('INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)');
            $updStock = $db->prepare('UPDATE products SET stock = stock - ?, sold_count = sold_count + ? WHERE id = ? AND stock >= ?');

            foreach ($items as $item) {
                $insItem->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                $updStock->execute([$item['quantity'], $item['quantity'], $item['product_id'], $item['quantity']]);
            }

            // Hapus cart
            if ($userId) {
                $db->prepare('DELETE FROM cart WHERE user_id = ? AND is_selected = 1')->execute([$userId]);
            } else {
                $db->prepare('DELETE FROM cart WHERE session_id = ? AND is_selected = 1')->execute([$sid]);
            }

            $db->commit();
            $success = true;

            // Siapkan pesan WhatsApp jika metode WA
            if ($method === 'whatsapp') {
                $waMsg  = "Halo *{$siteName}*, saya ingin memesan:\n\n";
                $waMsg .= "*Kode Pesanan:* {$orderCode}\n\n";
                foreach ($items as $item) {
                    $waMsg .= "- {$item['name']} x{$item['quantity']} = " . formatRupiah($item['price'] * $item['quantity']) . "\n";
                }
                $waMsg .= "\n*Total:* " . formatRupiah($total);
                $waMsg .= "\n\n*Nama:* {$name}";
                $waMsg .= "\n*No. HP:* {$phone}";
                $waMsg .= "\n*Alamat:* {$address}";
                if ($notes) $waMsg .= "\n*Catatan:* {$notes}";

                $waUrl = 'https://wa.me/' . $waNumber . '?text=' . rawurlencode($waMsg);
            }
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<script>const BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container" style="max-width:860px;padding-top:28px;padding-bottom:60px;">

<?php if ($success): ?>
  <!-- ── SUCCESS STATE ── -->
  <div class="text-center py-5">
    <div style="width:80px;height:80px;border-radius:50%;background:#22c55e22;display:flex;align-items:center;justify-content:center;font-size:2.2rem;margin:0 auto 20px;color:#22c55e;">
      <i class="bi bi-check-circle-fill"></i>
    </div>
    <h2 class="fw-800 mb-2">Pesanan Berhasil Dibuat!</h2>
    <p class="text-muted mb-1">Kode Pesanan Anda:</p>
    <div class="fw-800 fs-4 mb-4" style="color:var(--primary);letter-spacing:2px;"><?= e($orderCode) ?></div>
    <p class="text-muted small mb-4">Simpan kode ini untuk melacak status pesanan Anda.</p>

    <div class="d-flex justify-content-center gap-3 flex-wrap">
      <?php if (isset($waUrl)): ?>
        <a href="<?= e($waUrl) ?>" target="_blank" class="btn btn-success px-4 py-2 fw-700">
          <i class="bi bi-whatsapp me-2"></i>Konfirmasi via WhatsApp
        </a>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/track.php?code=<?= e($orderCode) ?>" class="btn btn-outline-primary px-4 py-2 fw-700">
        <i class="bi bi-geo-alt me-2"></i>Lacak Pesanan
      </a>
      <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary px-4 py-2">
        <i class="bi bi-house me-2"></i>Kembali Beranda
      </a>
    </div>
  </div>

<?php else: ?>
  <h1 class="fw-800 mb-4" style="font-size:1.4rem;"><i class="bi bi-bag-check me-2"></i>Checkout</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger rounded-3"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="POST" id="checkoutForm">
    <div class="row g-4 align-items-start">

      <!-- Left: Form -->
      <div class="col-md-7">
        <div class="bg-white rounded-4 border shadow-sm p-4 mb-3">
          <h6 class="fw-700 mb-3"><i class="bi bi-person me-2 text-primary"></i>Data Penerima</h6>
          <div class="mb-3">
            <label class="form-label fw-600 small">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="customer_name" class="form-control"
                   value="<?= e($_POST['customer_name'] ?? ($_SESSION['user_name'] ?? '')) ?>"
                   placeholder="Masukkan nama lengkap" required />
          </div>
          <div class="mb-3">
            <label class="form-label fw-600 small">Nomor HP / WhatsApp <span class="text-danger">*</span></label>
            <input type="tel" name="customer_phone" class="form-control"
                   value="<?= e($_POST['customer_phone'] ?? '') ?>"
                   placeholder="08xxxxxxxxxx" required />
          </div>
          <div class="mb-3">
            <label class="form-label fw-600 small">Alamat Lengkap <span class="text-danger">*</span></label>
            <textarea name="customer_address" class="form-control" rows="3"
                      placeholder="Jl. Contoh No.1, Kota, Provinsi, Kode Pos" required><?= e($_POST['customer_address'] ?? '') ?></textarea>
          </div>
          <div class="mb-0">
            <label class="form-label fw-600 small">Catatan (opsional)</label>
            <textarea name="notes" class="form-control" rows="2"
                      placeholder="Mis: Warna produk, ukuran, dll."><?= e($_POST['notes'] ?? '') ?></textarea>
          </div>
        </div>

        <!-- Metode Pembayaran -->
        <div class="bg-white rounded-4 border shadow-sm p-4">
          <h6 class="fw-700 mb-3"><i class="bi bi-credit-card me-2 text-primary"></i>Metode Konfirmasi</h6>
          <div class="d-flex flex-column gap-2">
            <label class="payment-option <?= ($_POST['payment_method'] ?? 'whatsapp') === 'whatsapp' ? 'selected' : '' ?>" style="cursor:pointer;border:2px solid var(--gray-200);border-radius:12px;padding:14px;display:flex;align-items:center;gap:12px;transition:.2s;" onclick="selectPayment(this,'whatsapp')">
              <input type="radio" name="payment_method" value="whatsapp" <?= ($_POST['payment_method'] ?? 'whatsapp') === 'whatsapp' ? 'checked' : '' ?> style="display:none;">
              <div style="width:40px;height:40px;border-radius:10px;background:#25D36622;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#25D366;flex-shrink:0;">
                <i class="bi bi-whatsapp"></i>
              </div>
              <div>
                <div class="fw-700 small">Konfirmasi via WhatsApp</div>
                <div class="text-muted" style="font-size:.75rem;">Pesanan akan dikirim ke WhatsApp CS kami</div>
              </div>
              <i class="bi bi-check-circle-fill ms-auto" style="color:#25D366;font-size:1.2rem;"></i>
            </label>
            <label class="payment-option <?= ($_POST['payment_method'] ?? '') === 'transfer' ? 'selected' : '' ?>" style="cursor:pointer;border:2px solid var(--gray-200);border-radius:12px;padding:14px;display:flex;align-items:center;gap:12px;transition:.2s;" onclick="selectPayment(this,'transfer')">
              <input type="radio" name="payment_method" value="transfer" <?= ($_POST['payment_method'] ?? '') === 'transfer' ? 'checked' : '' ?> style="display:none;">
              <div style="width:40px;height:40px;border-radius:10px;background:#6C63FF22;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--primary);flex-shrink:0;">
                <i class="bi bi-bank"></i>
              </div>
              <div>
                <div class="fw-700 small">Transfer Bank Manual</div>
                <div class="text-muted" style="font-size:.75rem;">Konfirmasi pembayaran via chat setelah transfer</div>
              </div>
              <i class="bi bi-circle ms-auto" style="color:var(--gray-300);font-size:1.2rem;"></i>
            </label>
          </div>
        </div>
      </div>

      <!-- Right: Order Summary -->
      <div class="col-md-5">
        <div class="bg-white rounded-4 border shadow-sm p-4">
          <h6 class="fw-700 mb-3"><i class="bi bi-receipt me-2 text-primary"></i>Ringkasan Pesanan</h6>

          <!-- Items List -->
          <div style="max-height:220px;overflow-y:auto;" class="mb-3">
            <?php foreach ($items as $item): ?>
              <div class="d-flex justify-content-between align-items-center mb-2 small">
                <div class="text-truncate me-2" style="max-width:200px;">
                  <span class="fw-600"><?= e($item['name']) ?></span>
                  <span class="text-muted"> x<?= $item['quantity'] ?></span>
                </div>
                <span class="fw-700 flex-shrink-0"><?= formatRupiah($item['price'] * $item['quantity']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>

          <hr />
          <div class="d-flex justify-content-between small mb-1">
            <span class="text-muted">Subtotal</span>
            <span class="fw-600"><?= formatRupiah($total) ?></span>
          </div>
          <div class="d-flex justify-content-between small mb-3">
            <span class="text-muted">Ongkir</span>
            <span class="fw-600 text-success">Gratis</span>
          </div>
          <div class="d-flex justify-content-between fw-800 fs-5 mb-4">
            <span>Total</span>
            <span style="color:var(--primary);"><?= formatRupiah($total) ?></span>
          </div>

          <button type="submit" class="btn btn-primary-custom w-100 py-3 fw-700 fs-6">
            <i class="bi bi-bag-check me-2"></i>Buat Pesanan
          </button>
          <a href="<?= BASE_URL ?>/cart.php" class="btn btn-link text-muted w-100 mt-2 small">
            ← Kembali ke Keranjang
          </a>
        </div>
        
        <!-- QRIS Payment Section -->
        <div class="bg-white rounded-4 border shadow-sm p-4 mt-3 text-center">
          <h6 class="fw-700 mb-3"><i class="bi bi-qr-code me-2 text-primary"></i>Pembayaran QRIS</h6>
          <img src="<?= BASE_URL ?>/assets/images/qris.png" alt="QRIS" class="img-fluid rounded mb-3 border">
          <p class="fw-bold fs-5 mb-0" style="color: var(--primary);">Mohon melakukan pembayaran melalui QRIS ini.</p>
        </div>
      </div>

    </div>
  </form>

  <script>
  function selectPayment(el, val) {
    document.querySelectorAll('.payment-option').forEach(o => {
      o.style.borderColor = 'var(--gray-200)';
      o.style.background  = '';
      o.querySelector('.bi-check-circle-fill, .bi-circle').className = 'bi bi-circle ms-auto';
      o.querySelector('.bi-circle, .bi-check-circle-fill').style.color = 'var(--gray-300)';
    });
    el.style.borderColor = 'var(--primary)';
    el.style.background  = 'var(--primary-light)';
    const icon = el.querySelector('[class*="bi-c"]');
    if (icon) { icon.className = 'bi bi-check-circle-fill ms-auto'; icon.style.color = 'var(--primary)'; }
    el.querySelector('input[type=radio]').checked = true;
  }
  </script>

<?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

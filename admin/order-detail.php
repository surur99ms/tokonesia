<?php
// ============================================================
// TOKONESIA — Admin: Detail Pesanan + Update Status/Resi
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(BASE_URL . '/admin/orders.php');

$orderStmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
$orderStmt->execute([$id]);
$order = $orderStmt->fetch();
if (!$order) redirect(BASE_URL . '/admin/orders.php');

// Ambil item
$itemStmt = $db->prepare(
    'SELECT oi.*, p.name AS product_name, p.slug,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS img
     FROM order_items oi JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?'
);
$itemStmt->execute([$id]);
$items = $itemStmt->fetchAll();

// UPDATE Status/Resi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus   = $_POST['status']          ?? $order['status'];
    $tracking    = trim($_POST['tracking_number'] ?? '');
    $db->prepare('UPDATE orders SET status=?, tracking_number=? WHERE id=?')
       ->execute([$newStatus, $tracking ?: null, $id]);
    setFlash('success', 'Pesanan berhasil diperbarui.');
    redirect(BASE_URL . '/admin/order-detail.php?id=' . $id);
}

$statusColors = [
    'pending'    => ['bg' => '#f59e0b', 'label' => 'Menunggu Konfirmasi', 'icon' => 'bi-clock'],
    'processing' => ['bg' => '#6C63FF', 'label' => 'Sedang Diproses',     'icon' => 'bi-gear'],
    'shipped'    => ['bg' => '#3b82f6', 'label' => 'Dalam Pengiriman',    'icon' => 'bi-truck'],
    'delivered'  => ['bg' => '#22c55e', 'label' => 'Pesanan Diterima',    'icon' => 'bi-check-circle'],
    'cancelled'  => ['bg' => '#ef4444', 'label' => 'Dibatalkan',          'icon' => 'bi-x-circle'],
];
$sc = $statusColors[$order['status']] ?? $statusColors['pending'];

$waNumber  = getSetting('whatsapp_number', '6281234567890');
$siteName  = getSetting('site_name', 'Tokonesia');
$pageTitle = 'Detail Pesanan ' . e($order['order_code']) . ' — Admin';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Back -->
<div class="mb-3">
  <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<div class="row g-4 align-items-start">

  <!-- Left: Order Info -->
  <div class="col-lg-8">

    <!-- Header Card -->
    <div class="admin-card mb-3">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
          <div class="text-muted small mb-1">Kode Pesanan</div>
          <h5 class="fw-800 mb-1" style="font-size:1.2rem;letter-spacing:1px;"><?= e($order['order_code']) ?></h5>
          <div class="text-muted small"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?> WIB</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <span class="status-badge fs-6 px-3 py-2" style="background:<?= $sc['bg'] ?>22;color:<?= $sc['bg'] ?>;">
            <i class="bi <?= $sc['icon'] ?> me-1"></i><?= $sc['label'] ?>
          </span>
          <!-- WA Contact -->
          <?php
          $waMsg = "Halo *{$order['customer_name']}*, kami ingin mengkonfirmasi pesanan Anda (*{$order['order_code']}*). ";
          $waUrl = 'https://wa.me/' . ltrim(preg_replace('/[^0-9]/', '', $order['customer_phone']), '0');
          $waUrl = 'https://wa.me/62' . ltrim(preg_replace('/[^0-9]/', '', $order['customer_phone']), '0') . '?text=' . rawurlencode($waMsg);
          ?>
          <a href="<?= e($waUrl) ?>" target="_blank" class="btn btn-success btn-sm fw-600">
            <i class="bi bi-whatsapp me-1"></i>Chat Pembeli
          </a>
        </div>
      </div>
    </div>

    <!-- Items -->
    <div class="admin-card mb-3">
      <h6 class="fw-700 mb-3"><i class="bi bi-bag me-2"></i>Item Pesanan</h6>
      <?php foreach ($items as $item):
        $imgUrl = $item['img']
          ? (file_exists(UPLOAD_DIR . $item['img']) ? UPLOAD_URL . $item['img'] : BASE_URL . '/assets/images/placeholder.png')
          : BASE_URL . '/assets/images/placeholder.png';
      ?>
        <div class="d-flex align-items-center gap-3 py-2 border-bottom">
          <img src="<?= e($imgUrl) ?>" class="product-thumb" alt="" />
          <div class="flex-grow-1">
            <div class="fw-700 small"><?= e($item['product_name']) ?></div>
            <div class="text-muted" style="font-size:.75rem;">
              <?= formatRupiah((float)$item['price_at_purchase']) ?> × <?= $item['quantity'] ?>
            </div>
          </div>
          <div class="fw-800" style="color:var(--primary);">
            <?= formatRupiah($item['price_at_purchase'] * $item['quantity']) ?>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="d-flex justify-content-between fw-800 mt-3 pt-2" style="font-size:1rem;">
        <span>Total Pembayaran</span>
        <span style="color:var(--primary);"><?= formatRupiah((float)$order['total_amount']) ?></span>
      </div>
    </div>

    <!-- Customer Info -->
    <div class="admin-card">
      <h6 class="fw-700 mb-3"><i class="bi bi-person me-2"></i>Data Pelanggan</h6>
      <div class="row g-2" style="font-size:.875rem;">
        <div class="col-sm-6">
          <div class="text-muted small">Nama</div>
          <div class="fw-700"><?= e($order['customer_name']) ?></div>
        </div>
        <div class="col-sm-6">
          <div class="text-muted small">No. HP</div>
          <div class="fw-700"><?= e($order['customer_phone']) ?></div>
        </div>
        <div class="col-12">
          <div class="text-muted small">Alamat</div>
          <div class="fw-700"><?= e($order['customer_address']) ?></div>
        </div>
        <?php if ($order['notes']): ?>
          <div class="col-12">
            <div class="text-muted small">Catatan</div>
            <div class="fst-italic"><?= e($order['notes']) ?></div>
          </div>
        <?php endif; ?>
        <?php if ($order['tracking_number']): ?>
          <div class="col-12">
            <div class="text-muted small">Nomor Resi</div>
            <div class="fw-700 text-primary"><?= e($order['tracking_number']) ?></div>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- Right: Update Form -->
  <div class="col-lg-4">
    <div class="admin-card" style="position:sticky;top:80px;">
      <h6 class="fw-700 mb-3"><i class="bi bi-pencil-square me-2"></i>Update Pesanan</h6>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label fw-600 small">Status Pesanan</label>
          <select name="status" class="form-select">
            <?php foreach ($statusColors as $s => $meta): ?>
              <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                <?= $meta['label'] ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-4">
          <label class="form-label fw-600 small">Nomor Resi Pengiriman</label>
          <input type="text" name="tracking_number" class="form-control"
                 value="<?= e($order['tracking_number'] ?? '') ?>"
                 placeholder="Isi jika sudah dikirim" />
        </div>
        <button type="submit" class="btn btn-primary fw-700 w-100">
          <i class="bi bi-save me-2"></i>Simpan Perubahan
        </button>
      </form>

      <!-- Quick Status Buttons -->
      <hr />
      <div class="d-grid gap-2">
        <?php
        $nextStatus = [
          'pending'    => ['processing', 'Proses Pesanan',   'primary'],
          'processing' => ['shipped',    'Tandai Dikirim',   'info'],
          'shipped'    => ['delivered',  'Tandai Diterima',  'success'],
        ];
        if (isset($nextStatus[$order['status']])):
          [$ns, $label, $color] = $nextStatus[$order['status']];
        ?>
          <form method="POST">
            <input type="hidden" name="status" value="<?= $ns ?>">
            <input type="hidden" name="tracking_number" value="<?= e($order['tracking_number'] ?? '') ?>">
            <button type="submit" class="btn btn-<?= $color ?> w-100 fw-600">
              <i class="bi bi-arrow-right-circle me-2"></i><?= $label ?>
            </button>
          </form>
        <?php endif; ?>
        <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'delivered'): ?>
          <form method="POST" onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?')">
            <input type="hidden" name="status" value="cancelled">
            <input type="hidden" name="tracking_number" value="">
            <button type="submit" class="btn btn-outline-danger w-100 fw-600 small">
              <i class="bi bi-x-circle me-1"></i>Batalkan Pesanan
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

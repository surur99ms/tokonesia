<?php
// ============================================================
// TOKONESIA — Lacak Pesanan
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Lacak Pesanan — ' . getSetting('site_name', 'Tokonesia');
$order = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['order_code'] ?? '');
    if (!$code) {
        $error = 'Masukkan nomor pesanan atau resi terlebih dahulu.';
    } else {
        $stmt = getDB()->prepare(
            'SELECT o.*, GROUP_CONCAT(oi.quantity, "x ", p.name SEPARATOR "\n") AS items_summary
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE o.order_code = ? OR o.tracking_number = ?
             GROUP BY o.id
             LIMIT 1'
        );
        $stmt->execute([$code, $code]);
        $order = $stmt->fetch();
        if (!$order) {
            $error = 'Pesanan dengan kode "<strong>' . e($code) . '</strong>" tidak ditemukan.';
        }
    }
}

$statusMap = [
    'pending'    => ['label' => 'Menunggu Konfirmasi', 'color' => '#f59e0b', 'icon' => 'bi-clock'],
    'processing' => ['label' => 'Sedang Diproses',     'color' => '#6C63FF', 'icon' => 'bi-gear'],
    'shipped'    => ['label' => 'Dalam Pengiriman',    'color' => '#3b82f6', 'icon' => 'bi-truck'],
    'delivered'  => ['label' => 'Pesanan Diterima',    'color' => '#22c55e', 'icon' => 'bi-check-circle'],
    'cancelled'  => ['label' => 'Dibatalkan',          'color' => '#ef4444', 'icon' => 'bi-x-circle'],
];

require_once __DIR__ . '/includes/header.php';
?>
<script>const BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container" style="max-width:640px;padding-top:40px;padding-bottom:60px;">

  <div class="text-center mb-4">
    <div style="width:64px;height:64px;border-radius:20px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 12px;">
      <i class="bi bi-geo-alt-fill" style="color:var(--primary);"></i>
    </div>
    <h1 class="fw-800" style="font-size:1.5rem;">Lacak Pesanan</h1>
    <p class="text-muted small">Masukkan nomor pesanan atau nomor resi pengiriman</p>
  </div>

  <!-- Form -->
  <form method="POST" class="mb-4">
    <div class="input-group input-group-lg shadow-sm">
      <input type="text" name="order_code" class="form-control border-2"
             placeholder="Contoh: ORD-20260101-0001"
             value="<?= e($_POST['order_code'] ?? '') ?>"
             style="border-radius:14px 0 0 14px;" required />
      <button class="btn btn-primary fw-700" type="submit" style="border-radius:0 14px 14px 0;">
        <i class="bi bi-search me-1"></i>Lacak
      </button>
    </div>
  </form>

  <!-- Error -->
  <?php if ($error): ?>
    <div class="alert alert-danger rounded-3"><?= $error ?></div>
  <?php endif; ?>

  <!-- Result -->
  <?php if ($order):
    $st = $statusMap[$order['status']] ?? $statusMap['pending'];
    $steps = ['pending', 'processing', 'shipped', 'delivered'];
    $currentStep = array_search($order['status'], $steps);
  ?>
    <div class="bg-white rounded-4 border shadow-sm p-4">

      <!-- Status Badge -->
      <div class="d-flex align-items-center gap-3 mb-4">
        <div style="width:48px;height:48px;border-radius:14px;background:<?= $st['color'] ?>22;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:<?= $st['color'] ?>;">
          <i class="bi <?= $st['icon'] ?>"></i>
        </div>
        <div>
          <div class="fw-800" style="font-size:1.05rem;"><?= $st['label'] ?></div>
          <div class="text-muted small">Kode Pesanan: <strong><?= e($order['order_code']) ?></strong></div>
        </div>
      </div>

      <!-- Progress Timeline -->
      <?php if ($order['status'] !== 'cancelled'): ?>
        <div class="d-flex justify-content-between position-relative mb-4" style="padding:0 10px;">
          <div style="position:absolute;top:16px;left:30px;right:30px;height:3px;background:var(--gray-200);z-index:0;"></div>
          <div style="position:absolute;top:16px;left:30px;height:3px;width:<?= min(100, ($currentStep / 3) * 100) ?>%;background:var(--primary);z-index:1;transition:width 1s;"></div>
          <?php foreach ($steps as $i => $s):
            $done = $i <= $currentStep;
            $stepInfo = $statusMap[$s];
          ?>
            <div class="text-center" style="z-index:2;flex:1;">
              <div style="
                width:32px;height:32px;border-radius:50%;
                background:<?= $done ? 'var(--primary)' : 'var(--gray-200)' ?>;
                color:<?= $done ? '#fff' : 'var(--gray-500)' ?>;
                display:flex;align-items:center;justify-content:center;
                font-size:.9rem;margin:0 auto 6px;
                transition:background .4s;
                border:3px solid <?= $done ? 'var(--primary)' : 'var(--gray-200)' ?>;
              ">
                <i class="bi <?= $done ? 'bi-check-lg' : $stepInfo['icon'] ?>"></i>
              </div>
              <div style="font-size:.68rem;font-weight:600;color:<?= $done ? 'var(--primary)' : 'var(--gray-500)' ?>;">
                <?= $stepInfo['label'] ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Order Details -->
      <div class="row g-2" style="font-size:.88rem;">
        <div class="col-6">
          <div class="text-muted">Nama Penerima</div>
          <div class="fw-700"><?= e($order['customer_name']) ?></div>
        </div>
        <div class="col-6">
          <div class="text-muted">No. HP</div>
          <div class="fw-700"><?= e($order['customer_phone']) ?></div>
        </div>
        <div class="col-12">
          <div class="text-muted">Alamat Pengiriman</div>
          <div class="fw-700"><?= e($order['customer_address']) ?></div>
        </div>
        <?php if ($order['tracking_number']): ?>
          <div class="col-12">
            <div class="text-muted">Nomor Resi</div>
            <div class="fw-700 text-primary"><?= e($order['tracking_number']) ?></div>
          </div>
        <?php endif; ?>
        <div class="col-6">
          <div class="text-muted">Total Belanja</div>
          <div class="fw-800 text-primary"><?= formatRupiah((float)$order['total_amount']) ?></div>
        </div>
        <div class="col-6">
          <div class="text-muted">Tanggal Pesan</div>
          <div class="fw-700"><?= date('d M Y', strtotime($order['created_at'])) ?></div>
        </div>
        <?php if ($order['items_summary']): ?>
          <div class="col-12">
            <div class="text-muted">Item Pesanan</div>
            <div class="fw-700" style="white-space:pre-line;"><?= e($order['items_summary']) ?></div>
          </div>
        <?php endif; ?>
      </div>

    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

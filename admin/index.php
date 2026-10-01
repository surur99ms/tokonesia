<?php
// ============================================================
// TOKONESIA — Admin Dashboard
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'Dashboard Admin — ' . getSetting('site_name', 'Tokonesia');
$db = getDB();

$totalProducts = (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalOrders   = (int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalRevenue  = (float)$db->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$pendingOrders = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

$recentOrders = $db->query(
    "SELECT * FROM orders ORDER BY created_at DESC LIMIT 8"
)->fetchAll();

$statusColors = [
    'pending'    => 'warning',
    'processing' => 'primary',
    'shipped'    => 'info',
    'delivered'  => 'success',
    'cancelled'  => 'danger',
];

require_once __DIR__ . '/../includes/admin-header.php';
?>
<script>const BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container-fluid" style="padding:28px 24px 60px;">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h1 class="fw-800 mb-0" style="font-size:1.4rem;">Dashboard Admin</h1>
      <p class="text-muted small mb-0">Selamat datang, <?= e($_SESSION['user_name']) ?>! 👋</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/product-add.php" class="btn btn-primary-custom">
      <i class="bi bi-plus-lg me-1"></i>Tambah Produk
    </a>
  </div>

  <!-- Stats Cards -->
  <div class="row g-3 mb-4">
    <?php
    $cards = [
      ['icon' => 'bi-box-seam',     'color' => '#6C63FF', 'label' => 'Total Produk',    'value' => number_format($totalProducts), 'link' => BASE_URL . '/admin/products.php'],
      ['icon' => 'bi-receipt',      'color' => '#f59e0b', 'label' => 'Total Pesanan',   'value' => number_format($totalOrders),   'link' => BASE_URL . '/admin/orders.php'],
      ['icon' => 'bi-currency-dollar','color'=> '#22c55e','label' => 'Pendapatan',       'value' => formatRupiah($totalRevenue),   'link' => '#'],
      ['icon' => 'bi-clock-history', 'color' => '#ef4444', 'label' => 'Pesanan Pending', 'value' => number_format($pendingOrders),'link' => BASE_URL . '/admin/orders.php?status=pending'],
    ];
    foreach ($cards as $c): ?>
      <div class="col-6 col-md-3">
        <a href="<?= $c['link'] ?>" class="text-decoration-none">
          <div class="bg-white rounded-4 border shadow-sm p-3 h-100" style="transition:.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
            <div style="width:44px;height:44px;border-radius:12px;background:<?= $c['color'] ?>22;display:flex;align-items:center;justify-content:center;color:<?= $c['color'] ?>;font-size:1.3rem;margin-bottom:10px;">
              <i class="bi <?= $c['icon'] ?>"></i>
            </div>
            <div class="text-muted small"><?= $c['label'] ?></div>
            <div class="fw-800 fs-5"><?= $c['value'] ?></div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Recent Orders -->
  <div class="bg-white rounded-4 border shadow-sm p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="fw-700 mb-0">Pesanan Terbaru</h6>
      <a href="<?= BASE_URL ?>/admin/orders.php" class="section-link">Lihat Semua <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle small mb-0">
        <thead class="table-light">
          <tr>
            <th>Kode Pesanan</th>
            <th>Pelanggan</th>
            <th>Total</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $o): ?>
            <tr>
              <td class="fw-600"><?= e($o['order_code']) ?></td>
              <td><?= e($o['customer_name']) ?></td>
              <td class="fw-700" style="color:var(--primary);"><?= formatRupiah((float)$o['total_amount']) ?></td>
              <td>
                <span class="badge bg-<?= $statusColors[$o['status']] ?? 'secondary' ?>">
                  <?= ucfirst($o['status']) ?>
                </span>
              </td>
              <td class="text-muted"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
              <td>
                <a href="<?= BASE_URL ?>/admin/order-detail.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-eye"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($recentOrders)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada pesanan</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

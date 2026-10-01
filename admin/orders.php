<?php
// ============================================================
// TOKONESIA — Admin: Manajemen Pesanan
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db     = getDB();
$status = trim($_GET['status'] ?? '');
$search = trim($_GET['q'] ?? '');

$where  = [];
$params = [];

if ($status) {
    $where[]  = 'o.status = ?';
    $params[] = $status;
}
if ($search) {
    $where[]  = '(o.order_code LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orders = $db->prepare(
    "SELECT o.*, COUNT(oi.id) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     $whereSQL
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
$orders->execute($params);
$orders = $orders->fetchAll();

// Stats per status
$statusCounts = [];
$allStatuses  = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
foreach ($allStatuses as $s) {
    $c = $db->prepare('SELECT COUNT(*) FROM orders WHERE status = ?');
    $c->execute([$s]);
    $statusCounts[$s] = $c->fetchColumn();
}

$statusColors = [
    'pending'    => ['bg' => '#f59e0b', 'label' => 'Pending'],
    'processing' => ['bg' => '#6C63FF', 'label' => 'Diproses'],
    'shipped'    => ['bg' => '#3b82f6', 'label' => 'Dikirim'],
    'delivered'  => ['bg' => '#22c55e', 'label' => 'Diterima'],
    'cancelled'  => ['bg' => '#ef4444', 'label' => 'Dibatalkan'],
];

$pageTitle = 'Manajemen Pesanan — Admin';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Status Filter Cards -->
<div class="row g-2 mb-4">
  <div class="col-6 col-sm-4 col-lg-2">
    <a href="<?= BASE_URL ?>/admin/orders.php" class="text-decoration-none">
      <div class="admin-card text-center py-2 <?= !$status ? 'border-primary' : '' ?>" style="<?= !$status ? 'border-color:var(--primary)!important;' : '' ?>">
        <div class="fw-800 fs-5"><?= array_sum($statusCounts) ?></div>
        <div class="text-muted small">Semua</div>
      </div>
    </a>
  </div>
  <?php foreach ($statusColors as $s => $meta): ?>
    <div class="col-6 col-sm-4 col-lg-2">
      <a href="?status=<?= $s ?>" class="text-decoration-none">
        <div class="admin-card text-center py-2" style="<?= $status === $s ? 'border-color:' . $meta['bg'] . '!important;' : '' ?>">
          <div class="fw-800 fs-5" style="color:<?= $meta['bg'] ?>;"><?= $statusCounts[$s] ?></div>
          <div class="text-muted small"><?= $meta['label'] ?></div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <h5 class="admin-card-title"><i class="bi bi-receipt me-2"></i>Daftar Pesanan</h5>
    <!-- Search -->
    <form method="GET" class="d-flex gap-2">
      <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
      <input type="text" name="q" class="form-control form-control-sm" style="width:220px;"
             placeholder="Cari kode / nama..." value="<?= e($search) ?>">
      <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
      <?php if ($search || $status): ?>
        <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-sm btn-outline-danger" title="Reset"><i class="bi bi-x"></i></a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table admin-table" id="adminTable">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Pelanggan</th>
          <th>No. HP</th>
          <th>Total</th>
          <th>Item</th>
          <th>Status</th>
          <th>Resi</th>
          <th>Tanggal</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o):
          $sc = $statusColors[$o['status']] ?? ['bg' => '#999', 'label' => $o['status']];
        ?>
          <tr>
            <td><code class="fw-700"><?= e($o['order_code']) ?></code></td>
            <td class="fw-600"><?= e($o['customer_name']) ?></td>
            <td class="text-muted small"><?= e($o['customer_phone']) ?></td>
            <td class="fw-700" style="color:var(--primary);"><?= formatRupiah((float)$o['total_amount']) ?></td>
            <td class="text-center"><?= (int)$o['item_count'] ?></td>
            <td>
              <span class="status-badge" style="background:<?= $sc['bg'] ?>22;color:<?= $sc['bg'] ?>;">
                <?= $sc['label'] ?>
              </span>
            </td>
            <td class="text-muted small"><?= e($o['tracking_number'] ?: '—') ?></td>
            <td class="text-muted small"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            <td>
              <a href="<?= BASE_URL ?>/admin/order-detail.php?id=<?= $o['id'] ?>"
                 class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
          <tr><td colspan="9" class="text-center text-muted py-5">Tidak ada pesanan yang ditemukan.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

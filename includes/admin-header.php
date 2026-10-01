<?php
// ============================================================
// TOKONESIA — Admin Sidebar Include
// ============================================================
$adminMenu = [
  'Dashboard' => [
    ['icon' => 'bi-speedometer2', 'label' => 'Dashboard',      'url' => BASE_URL . '/admin/index.php'],
  ],
  'Toko' => [
    ['icon' => 'bi-box-seam',     'label' => 'Produk',         'url' => BASE_URL . '/admin/products.php'],
    ['icon' => 'bi-tags',         'label' => 'Kategori',       'url' => BASE_URL . '/admin/categories.php'],
    ['icon' => 'bi-receipt',      'label' => 'Pesanan',        'url' => BASE_URL . '/admin/orders.php'],
  ],
  'Pengaturan' => [
    ['icon' => 'bi-gear',         'label' => 'Pengaturan Toko','url' => BASE_URL . '/admin/settings.php'],
    ['icon' => 'bi-house',        'label' => 'Lihat Toko',     'url' => BASE_URL . '/index.php', 'target' => '_blank'],
    ['icon' => 'bi-box-arrow-right','label'=> 'Keluar',        'url' => BASE_URL . '/logout.php'],
  ],
];
$currentUrl = BASE_URL . '/admin/' . basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle ?? 'Admin — ' . getSetting('site_name', 'Tokonesia')) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css" />
</head>
<body style="font-family:'Plus Jakarta Sans',sans-serif;">

<div class="admin-wrapper">

  <!-- ── SIDEBAR ── -->
  <aside class="admin-sidebar" id="adminSidebar">
    <a href="<?= BASE_URL ?>/admin/index.php" class="admin-sidebar-brand">
      <i class="bi bi-bag-heart-fill"></i>
      <?= e(getSetting('site_name', 'Tokonesia')) ?>
      <span style="font-size:.65rem;color:rgba(255,255,255,.4);font-weight:600;margin-left:4px;">ADMIN</span>
    </a>

    <nav class="admin-nav">
      <?php foreach ($adminMenu as $section => $links): ?>
        <div class="admin-nav-section"><?= $section ?></div>
        <?php foreach ($links as $link):
          $isActive = str_contains($currentUrl, basename($link['url']));
        ?>
          <a href="<?= $link['url'] ?>"
             class="admin-nav-link <?= $isActive ? 'active' : '' ?>"
             <?= isset($link['target']) ? 'target="' . $link['target'] . '"' : '' ?>>
            <i class="bi <?= $link['icon'] ?>"></i>
            <?= $link['label'] ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar-footer">
      <div style="font-size:.75rem;color:rgba(255,255,255,.4);">
        <i class="bi bi-person-circle me-1"></i>
        <?= e($_SESSION['user_name'] ?? 'Admin') ?>
      </div>
    </div>
  </aside>

  <!-- Overlay (mobile) -->
  <div class="admin-overlay" id="adminOverlay" onclick="toggleSidebar()"></div>

  <!-- ── MAIN ── -->
  <div class="admin-main">
    <!-- Topbar -->
    <div class="admin-topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="admin-menu-toggle" onclick="toggleSidebar()">
          <i class="bi bi-list"></i>
        </button>
        <h6 class="mb-0 fw-700" style="font-size:.95rem;"><?= e($pageTitle ?? 'Dashboard') ?></h6>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/admin/products.php?action=add" class="btn btn-sm btn-primary fw-600 d-none d-md-flex align-items-center gap-1">
          <i class="bi bi-plus-lg"></i>Produk Baru
        </a>
        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-outline-danger">
          <i class="bi bi-box-arrow-right"></i>
        </a>
      </div>
    </div>

    <!-- Flash -->
    <?php $flash = getFlash(); if ($flash): ?>
      <div class="mx-4 mt-3 alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?> alert-dismissible fade show">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Content -->
    <div class="admin-content">

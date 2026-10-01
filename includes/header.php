<?php
// ============================================================
// TOKONESIA — Header (Navigasi Atas)
// ============================================================
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$siteName   = getSetting('site_name', 'Tokonesia');
$siteLogo   = getSetting('site_logo');
$cartCount  = getCartCount();
$categories = getAllCategories();
$currentUri = $_SERVER['REQUEST_URI'];

function isActivePage(string $path): string {
    return str_contains($_SERVER['REQUEST_URI'], $path) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle ?? $siteName) ?></title>
  <meta name="description" content="<?= e($pageDesc ?? 'Belanja online mudah dan murah di ' . $siteName) ?>" />

  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
</head>
<body>

<!-- ─── TOP NAVBAR ─── -->
<nav class="navbar navbar-expand-lg sticky-top toko-navbar">
  <div class="container">

    <!-- Logo / Brand -->
    <a class="navbar-brand fw-800" href="<?= BASE_URL ?>/index.php">
      <?php if ($siteLogo): ?>
        <img src="<?= UPLOAD_URL . e($siteLogo) ?>" alt="<?= e($siteName) ?>" height="36" />
      <?php else: ?>
        <span class="brand-text"><i class="bi bi-bag-heart-fill me-1"></i><?= e($siteName) ?></span>
      <?php endif; ?>
    </a>

    <!-- Search Bar (hidden on xs) -->
    <form class="d-none d-md-flex search-form mx-auto" action="<?= BASE_URL ?>/products.php" method="GET">
      <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="Cari produk..." value="<?= e($_GET['q'] ?? '') ?>" />
        <button class="btn btn-search" type="submit"><i class="bi bi-search"></i></button>
      </div>
    </form>

    <!-- Right Icons -->
    <div class="d-flex align-items-center gap-2 nav-right">
      <!-- Cart -->
      <a href="<?= BASE_URL ?>/cart.php" class="nav-icon-btn position-relative" title="Keranjang">
        <i class="bi bi-cart3"></i>
        <?php if ($cartCount > 0): ?>
          <span class="cart-badge"><?= $cartCount ?></span>
        <?php endif; ?>
      </a>

      <!-- User Dropdown -->
      <?php if (isLoggedIn()): ?>
        <div class="dropdown">
          <button class="nav-icon-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text fw-600"><?= e($_SESSION['user_name']) ?></span></li>
            <li><hr class="dropdown-divider" /></li>
            <?php if (isAdmin()): ?>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard Admin</a></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary-custom btn-sm d-none d-md-inline-flex">
          <i class="bi bi-person me-1"></i>Masuk
        </a>
      <?php endif; ?>

      <!-- Hamburger (mobile) -->
      <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
        <i class="bi bi-list fs-4"></i>
      </button>
    </div>

    <!-- Collapsible Nav (desktop) -->
    <div class="collapse navbar-collapse" id="mainNav">
      <!-- Mobile Search -->
      <form class="d-flex d-md-none my-2 search-form" action="<?= BASE_URL ?>/products.php" method="GET">
        <div class="input-group">
          <input type="text" name="q" class="form-control" placeholder="Cari produk..." value="<?= e($_GET['q'] ?? '') ?>" />
          <button class="btn btn-search" type="submit"><i class="bi bi-search"></i></button>
        </div>
      </form>

      <ul class="navbar-nav ms-auto mt-2 mt-lg-0 gap-lg-1">
        <li class="nav-item">
          <a class="nav-link <?= isActivePage('/index.php') || $currentUri === '/' . basename(dirname(__DIR__)) . '/' ? 'active' : '' ?>"
             href="<?= BASE_URL ?>/index.php">Beranda</a>
        </li>
        <!-- Kategori Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Produk</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/products.php">Semua Produk</a></li>
            <li><hr class="dropdown-divider" /></li>
            <?php foreach ($categories as $cat): ?>
              <li>
                <a class="dropdown-item" href="<?= BASE_URL ?>/products.php?cat=<?= e($cat['slug']) ?>">
                  <i class="bi <?= e($cat['icon']) ?> me-2"></i><?= e($cat['name']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= isActivePage('/track.php') ?>" href="<?= BASE_URL ?>/track.php">Lacak Pesanan</a>
        </li>
        <?php if (!isLoggedIn()): ?>
          <li class="nav-item d-md-none">
            <a class="nav-link" href="<?= BASE_URL ?>/login.php"><i class="bi bi-person me-1"></i>Masuk</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>

  </div>
</nav>
<!-- ─── END TOP NAVBAR ─── -->

<!-- Flash Message -->
<?php $flash = getFlash(); if ($flash): ?>
<div class="container mt-3">
  <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= e($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>

<!-- ─── MAIN CONTENT START ─── -->
<main class="main-content">

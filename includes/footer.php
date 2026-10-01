<?php
// ============================================================
// TOKONESIA — Footer (+ Bottom Navigation Mobile)
// ============================================================
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$waNumber  = getSetting('whatsapp_number', '6281234567890');
$siteName  = getSetting('site_name', 'Tokonesia');
$cartCount = getCartCount();
?>

</main>
<!-- ─── MAIN CONTENT END ─── -->

<!-- ═══════════════════════════════════════════════
     FOOTER DESKTOP
════════════════════════════════════════════════ -->
<footer class="site-footer d-none d-md-block">
  <div class="container">
    <div class="row gy-4">

      <!-- Brand -->
      <div class="col-md-4">
        <h5 class="footer-brand mb-3"><i class="bi bi-bag-heart-fill me-2"></i><?= e($siteName) ?></h5>
        <p class="text-muted small">Platform belanja online terpercaya dengan harga terbaik dan pengiriman cepat ke seluruh Indonesia.</p>
        <a href="https://wa.me/<?= e($waNumber) ?>" target="_blank" class="btn btn-success btn-sm mt-2">
          <i class="bi bi-whatsapp me-1"></i>Hubungi CS
        </a>
      </div>

      <!-- Links -->
      <div class="col-6 col-md-2">
        <h6 class="footer-heading">Belanja</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="<?= BASE_URL ?>/products.php">Semua Produk</a></li>
          <li><a href="<?= BASE_URL ?>/cart.php">Keranjang</a></li>
          <li><a href="<?= BASE_URL ?>/track.php">Lacak Pesanan</a></li>
        </ul>
      </div>

      <div class="col-6 col-md-2">
        <h6 class="footer-heading">Akun</h6>
        <ul class="list-unstyled footer-links">
          <?php if (isLoggedIn()): ?>
            <li><a href="<?= BASE_URL ?>/logout.php">Keluar</a></li>
          <?php else: ?>
            <li><a href="<?= BASE_URL ?>/login.php">Masuk</a></li>
            <li><a href="<?= BASE_URL ?>/register.php">Daftar</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="col-md-4">
        <h6 class="footer-heading">Kategori Populer</h6>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach (getAllCategories() as $cat): ?>
            <a href="<?= BASE_URL ?>/products.php?cat=<?= e($cat['slug']) ?>" class="badge-cat-link">
              <?= e($cat['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <hr class="footer-divider mt-4" />
    <p class="text-center text-muted small mb-0">
      &copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.
    </p>
  </div>
</footer>

<!-- ═══════════════════════════════════════════════
     BOTTOM NAVIGATION BAR — Mobile Only
════════════════════════════════════════════════ -->
<nav class="bottom-nav d-flex d-md-none">

  <a href="<?= BASE_URL ?>/index.php"
     class="bottom-nav-item <?= str_ends_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 'index.php') || parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/' ? 'active' : '' ?>">
    <i class="bi bi-house-fill"></i>
    <span>Beranda</span>
  </a>

  <a href="<?= BASE_URL ?>/products.php"
     class="bottom-nav-item <?= str_contains($_SERVER['REQUEST_URI'], 'products') ? 'active' : '' ?>">
    <i class="bi bi-grid-fill"></i>
    <span>Produk</span>
  </a>

  <a href="<?= BASE_URL ?>/cart.php"
     class="bottom-nav-item position-relative <?= str_contains($_SERVER['REQUEST_URI'], 'cart') ? 'active' : '' ?>">
    <i class="bi bi-cart3"></i>
    <?php if ($cartCount > 0): ?>
      <span class="bottom-cart-badge"><?= $cartCount ?></span>
    <?php endif; ?>
    <span>Keranjang</span>
  </a>

  <a href="<?= BASE_URL ?>/track.php"
     class="bottom-nav-item <?= str_contains($_SERVER['REQUEST_URI'], 'track') ? 'active' : '' ?>">
    <i class="bi bi-geo-alt-fill"></i>
    <span>Lacak</span>
  </a>

  <a href="<?= BASE_URL ?>/<?= isLoggedIn() ? 'logout.php' : 'login.php' ?>"
     class="bottom-nav-item <?= str_contains($_SERVER['REQUEST_URI'], 'login') ? 'active' : '' ?>">
    <i class="bi bi-person-fill"></i>
    <span><?= isLoggedIn() ? e(explode(' ', $_SESSION['user_name'])[0]) : 'Masuk' ?></span>
  </a>

</nav>
<!-- ─── END BOTTOM NAVIGATION ─── -->

<!-- Spacer for bottom nav on mobile -->
<div class="d-md-none" style="height: 70px;"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>

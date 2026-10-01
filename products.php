<?php
// ============================================================
// TOKONESIA — Katalog Produk
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle   = 'Semua Produk — ' . getSetting('site_name', 'Tokonesia');
$db          = getDB();
$categories  = getAllCategories();

// Filter & Sort
$catSlug = trim($_GET['cat']  ?? '');
$search  = trim($_GET['q']    ?? '');
$sort    = trim($_GET['sort'] ?? 'newest');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

// Build Query
$where  = ['p.stock >= 0'];
$params = [];

if ($catSlug) {
    $where[]  = 'c.slug = ?';
    $params[] = $catSlug;
}
if ($search) {
    $where[]  = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$orderBy = match($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'sold'       => 'p.sold_count DESC',
    'name'       => 'p.name ASC',
    default      => 'p.created_at DESC',
};

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// Count total
$countStmt = $db->prepare(
    "SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id $whereSQL"
);
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalItems / $perPage));

// Fetch products
$stmt = $db->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS img_primary,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 0 ORDER BY sort_order LIMIT 1) AS img_secondary
     FROM products p
     JOIN categories c ON c.id = p.category_id
     $whereSQL
     ORDER BY $orderBy
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<script>const BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container" style="padding-top:28px;padding-bottom:40px;">

  <!-- Page Header -->
  <div class="mb-4">
    <h1 class="fw-800" style="font-size:1.5rem;">
      <?= $catSlug ? e(array_column($categories, 'name', 'slug')[$catSlug] ?? 'Produk') : 'Semua Produk' ?>
    </h1>
    <p class="text-muted small mb-0"><?= number_format($totalItems) ?> produk ditemukan</p>
  </div>

  <div class="row g-3">
    <!-- Sidebar Filter (Desktop) -->
    <div class="col-lg-3 d-none d-lg-block">
      <div class="bg-white rounded-3 p-3 shadow-sm border">
        <h6 class="fw-700 mb-3">Kategori</h6>
        <ul class="list-unstyled mb-0">
          <li class="mb-1">
            <a href="<?= BASE_URL ?>/products.php<?= $sort ? '?sort=' . e($sort) : '' ?>"
               class="text-decoration-none d-flex justify-content-between align-items-center py-1 px-2 rounded <?= !$catSlug ? 'fw-700 text-primary bg-light' : 'text-secondary' ?>">
              <span>Semua Kategori</span>
              <span class="badge bg-secondary rounded-pill"><?= $totalItems ?></span>
            </a>
          </li>
          <?php foreach ($categories as $cat): ?>
            <li class="mb-1">
              <a href="<?= BASE_URL ?>/products.php?cat=<?= e($cat['slug']) ?><?= $sort ? '&sort=' . e($sort) : '' ?>"
                 class="text-decoration-none d-flex justify-content-between align-items-center py-1 px-2 rounded <?= $catSlug === $cat['slug'] ? 'fw-700 text-primary bg-light' : 'text-secondary' ?>">
                <span><i class="bi <?= e($cat['icon']) ?> me-1"></i><?= e($cat['name']) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <!-- Product Grid -->
    <div class="col-lg-9">
      <!-- Toolbar -->
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <!-- Mobile Category Scroll -->
        <div class="d-lg-none w-100">
          <div class="category-scroll pb-2">
            <a href="<?= BASE_URL ?>/products.php" class="badge rounded-pill px-3 py-2 text-decoration-none <?= !$catSlug ? 'bg-primary text-white' : 'bg-white border text-secondary' ?>" style="font-size:.8rem;">Semua</a>
            <?php foreach ($categories as $cat): ?>
              <a href="<?= BASE_URL ?>/products.php?cat=<?= e($cat['slug']) ?>"
                 class="badge rounded-pill px-3 py-2 text-decoration-none flex-shrink-0 <?= $catSlug === $cat['slug'] ? 'bg-primary text-white' : 'bg-white border text-secondary' ?>"
                 style="font-size:.8rem;">
                <?= e($cat['name']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        <!-- Sort -->
        <form method="GET" class="d-flex align-items-center gap-2 ms-auto">
          <?php if ($catSlug): ?><input type="hidden" name="cat" value="<?= e($catSlug) ?>"><?php endif; ?>
          <?php if ($search): ?><input type="hidden" name="q" value="<?= e($search) ?>"><?php endif; ?>
          <label class="text-muted small me-1">Urutkan:</label>
          <select name="sort" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>Terbaru</option>
            <option value="sold"       <?= $sort === 'sold'       ? 'selected' : '' ?>>Terlaris</option>
            <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Harga Terendah</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Harga Tertinggi</option>
            <option value="name"       <?= $sort === 'name'       ? 'selected' : '' ?>>Nama A-Z</option>
          </select>
        </form>
      </div>

      <!-- Grid -->
      <?php if (empty($products)): ?>
        <div class="empty-state">
          <i class="bi bi-search d-block"></i>
          <h5>Produk Tidak Ditemukan</h5>
          <p>Coba kata kunci lain atau pilih kategori berbeda.</p>
          <a href="<?= BASE_URL ?>/products.php" class="btn btn-primary mt-2">Reset Filter</a>
        </div>
      <?php else: ?>
        <div class="products-grid">
          <?php foreach ($products as $prod):
            $imgP = $prod['img_primary']
              ? (file_exists(UPLOAD_DIR . $prod['img_primary']) ? UPLOAD_URL . $prod['img_primary'] : BASE_URL . '/assets/images/placeholder.png')
              : BASE_URL . '/assets/images/placeholder.png';
            $imgS = $prod['img_secondary']
              ? (file_exists(UPLOAD_DIR . $prod['img_secondary']) ? UPLOAD_URL . $prod['img_secondary'] : $imgP)
              : $imgP;
            $oos  = (int)$prod['stock'] === 0;
          ?>
            <div class="product-card <?= $oos ? 'out-of-stock' : '' ?>">
              <div class="product-card-img">
                <img class="img-primary"   src="<?= e($imgP) ?>" alt="<?= e($prod['name']) ?>" loading="lazy" />
                <img class="img-secondary" src="<?= e($imgS) ?>" alt="<?= e($prod['name']) ?>" loading="lazy" />
                <span class="product-cat-label"><?= e($prod['category_name']) ?></span>
                <button class="btn-add-cart-icon btn-wishlist" data-product-id="<?= (int)$prod['id'] ?>" title="Tambah ke Keranjang"><i class="bi bi-cart-plus"></i></button>
              </div>
              <div class="product-card-body">
                <div class="product-name"><?= e($prod['name']) ?></div>
                <div class="product-price"><?= formatRupiah((float)$prod['price']) ?></div>
                <div class="product-meta">
                  <span><i class="bi bi-bag-check me-1"></i>Terjual <?= number_format((int)$prod['sold_count']) ?></span>
                  <span>Stok: <?= (int)$prod['stock'] ?></span>
                </div>
                <?php if ($oos): ?>
                  <button class="btn-add-cart" disabled><i class="bi bi-x-circle"></i> Stok Habis</button>
                <?php else: ?>
                  <a href="#" class="btn-buy-now btn-add-cart" data-product-id="<?= (int)$prod['id'] ?>">
                    <i class="bi bi-cart-plus"></i> Beli Sekarang
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <nav class="mt-4" aria-label="Navigasi halaman">
            <ul class="pagination justify-content-center">
              <?php for ($i = 1; $i <= $totalPages; $i++):
                $params_pg = array_filter(['cat' => $catSlug, 'q' => $search, 'sort' => $sort, 'page' => $i]);
              ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                  <a class="page-link" href="?<?= http_build_query($params_pg) ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
// ============================================================
// TOKONESIA — Detail Produk
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) redirect(BASE_URL . '/products.php');

$db   = getDB();
$stmt = $db->prepare(
    'SELECT p.*, c.name AS category_name, c.slug AS category_slug
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.slug = ? LIMIT 1'
);
$stmt->execute([$slug]);
$product = $stmt->fetch();
if (!$product) redirect(BASE_URL . '/products.php');

// Gambar produk
$imgStmt = $db->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC');
$imgStmt->execute([$product['id']]);
$images = $imgStmt->fetchAll();

// Produk terkait (same category)
$relStmt = $db->prepare(
    'SELECT p.*, c.name AS category_name,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS img_primary,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 0 ORDER BY sort_order LIMIT 1) AS img_secondary
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.category_id = ? AND p.id != ? AND p.stock > 0
     ORDER BY p.sold_count DESC LIMIT 4'
);
$relStmt->execute([$product['category_id'], $product['id']]);
$related = $relStmt->fetchAll();

$pageTitle = e($product['name']) . ' — ' . getSetting('site_name', 'Tokonesia');
$pageDesc  = e(substr(strip_tags($product['description'] ?? ''), 0, 160));

require_once __DIR__ . '/includes/header.php';
?>
<script>const BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container" style="padding-top:24px;padding-bottom:60px;">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:.82rem;">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Beranda</a></li>
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/products.php?cat=<?= e($product['category_slug']) ?>"><?= e($product['category_name']) ?></a></li>
      <li class="breadcrumb-item active text-truncate" style="max-width:200px;"><?= e($product['name']) ?></li>
    </ol>
  </nav>

  <div class="row g-4 align-items-start">

    <!-- Left: Gambar -->
    <div class="col-md-5">
      <!-- Main Image -->
      <div style="border-radius:16px;overflow:hidden;border:1px solid var(--gray-200);background:var(--gray-100);aspect-ratio:1/1;">
        <?php if ($images): ?>
          <img id="mainImg"
               src="<?= file_exists(UPLOAD_DIR . $images[0]['image_path']) ? UPLOAD_URL . e($images[0]['image_path']) : BASE_URL . '/assets/images/placeholder.png' ?>"
               alt="<?= e($product['name']) ?>"
               style="width:100%;height:100%;object-fit:cover;transition:opacity .3s;" />
        <?php else: ?>
          <img src="<?= BASE_URL ?>/assets/images/placeholder.png" alt="No Image" style="width:100%;height:100%;object-fit:cover;" />
        <?php endif; ?>
      </div>

      <!-- Thumbnails -->
      <?php if (count($images) > 1): ?>
        <div class="d-flex gap-2 mt-2 flex-wrap">
          <?php foreach ($images as $i => $img):
            $url = file_exists(UPLOAD_DIR . $img['image_path']) ? UPLOAD_URL . e($img['image_path']) : BASE_URL . '/assets/images/placeholder.png';
          ?>
            <div onclick="switchImg('<?= $url ?>', this)"
                 style="width:64px;height:64px;border-radius:10px;overflow:hidden;cursor:pointer;border:2px solid <?= $i === 0 ? 'var(--primary)' : 'var(--gray-200)' ?>;flex-shrink:0;transition:.2s;"
                 class="thumb-item">
              <img src="<?= $url ?>" alt="Gambar <?= $i+1 ?>" style="width:100%;height:100%;object-fit:cover;" />
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Right: Info -->
    <div class="col-md-7">
      <span class="badge rounded-pill mb-2" style="background:var(--primary-light);color:var(--primary);font-size:.75rem;">
        <i class="bi bi-tag me-1"></i><?= e($product['category_name']) ?>
      </span>
      <h1 class="fw-800 mb-2" style="font-size:1.5rem;line-height:1.3;"><?= e($product['name']) ?></h1>

      <!-- Meta -->
      <div class="d-flex align-items-center gap-3 mb-3 flex-wrap" style="font-size:.82rem;">
        <span class="text-muted"><i class="bi bi-bag-check me-1"></i>Terjual <strong><?= number_format((int)$product['sold_count']) ?></strong></span>
        <span style="color:var(--gray-300);">|</span>
        <?php if ($product['stock'] > 0): ?>
          <span style="color:var(--success);"><i class="bi bi-check-circle me-1"></i>Stok: <strong><?= (int)$product['stock'] ?></strong></span>
        <?php else: ?>
          <span style="color:var(--danger);"><i class="bi bi-x-circle me-1"></i>Stok Habis</span>
        <?php endif; ?>
      </div>

      <!-- Harga -->
      <div class="fw-800 mb-4" style="font-size:2rem;color:var(--primary);">
        <?= formatRupiah((float)$product['price']) ?>
      </div>

      <!-- Deskripsi singkat -->
      <?php if ($product['description']): ?>
        <div class="mb-4" style="font-size:.9rem;color:var(--gray-700);line-height:1.7;">
          <?= nl2br(e(substr($product['description'], 0, 300))) ?>
          <?= strlen($product['description']) > 300 ? '...' : '' ?>
        </div>
      <?php endif; ?>

      <!-- Qty & Tombol -->
      <?php if ($product['stock'] > 0): ?>
        <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
          <div class="d-flex align-items-center border rounded-3 overflow-hidden">
            <button onclick="changeQty(-1)" class="btn border-0 px-3 py-2 fw-700 fs-5" style="line-height:1;">−</button>
            <input type="number" id="qtyInput" value="1" min="1" max="<?= (int)$product['stock'] ?>"
                   class="border-0 text-center fw-700" style="width:50px;outline:none;font-size:1rem;" />
            <button onclick="changeQty(1)" class="btn border-0 px-3 py-2 fw-700 fs-5" style="line-height:1;">+</button>
          </div>
          <button class="btn btn-primary-custom flex-grow-1 py-2 fw-700"
                  onclick="addToCartDetail(<?= (int)$product['id'] ?>)"
                  id="btnCart">
            <i class="bi bi-cart-plus me-2"></i>Tambah ke Keranjang
          </button>
        </div>
        <a href="<?= BASE_URL ?>/checkout.php" class="btn w-100 py-2 fw-700 mb-4"
           style="background:#25D366;color:#fff;border-radius:12px;"
           onclick="addToCartDetail(<?= (int)$product['id'] ?>); return true;">
          <i class="bi bi-whatsapp me-2"></i>Beli via WhatsApp
        </a>
      <?php else: ?>
        <div class="alert alert-secondary rounded-3">
          <i class="bi bi-x-circle me-2"></i>Produk ini sedang tidak tersedia / stok habis.
        </div>
      <?php endif; ?>

      <!-- Jaminan -->
      <div class="d-flex gap-3 flex-wrap" style="font-size:.78rem;color:var(--gray-500);">
        <span><i class="bi bi-shield-check me-1 text-success"></i>Garansi Produk</span>
        <span><i class="bi bi-truck me-1 text-primary"></i>Pengiriman Cepat</span>
        <span><i class="bi bi-arrow-repeat me-1 text-warning"></i>Retur 7 Hari</span>
      </div>
    </div>

  </div>

  <!-- Deskripsi Lengkap -->
  <?php if ($product['description']): ?>
    <div class="mt-5">
      <h5 class="fw-700 mb-3">Deskripsi Produk</h5>
      <div class="bg-white rounded-4 border p-4" style="font-size:.9rem;color:var(--gray-700);line-height:1.8;">
        <?= nl2br(e($product['description'])) ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Produk Terkait -->
  <?php if ($related): ?>
    <div class="mt-5">
      <h5 class="fw-700 mb-3">Produk Terkait</h5>
      <div class="products-grid">
        <?php foreach ($related as $prod):
          $imgP = $prod['img_primary']
            ? (file_exists(UPLOAD_DIR . $prod['img_primary']) ? UPLOAD_URL . $prod['img_primary'] : BASE_URL . '/assets/images/placeholder.png')
            : BASE_URL . '/assets/images/placeholder.png';
          $imgS = $prod['img_secondary']
            ? (file_exists(UPLOAD_DIR . $prod['img_secondary']) ? UPLOAD_URL . $prod['img_secondary'] : $imgP)
            : $imgP;
        ?>
          <div class="product-card">
            <div class="product-card-img">
              <img class="img-primary"   src="<?= e($imgP) ?>" alt="<?= e($prod['name']) ?>" loading="lazy" />
              <img class="img-secondary" src="<?= e($imgS) ?>" alt="<?= e($prod['name']) ?>" loading="lazy" />
              <span class="product-cat-label"><?= e($prod['category_name']) ?></span>
            </div>
            <div class="product-card-body">
              <div class="product-name"><?= e($prod['name']) ?></div>
              <div class="product-price"><?= formatRupiah((float)$prod['price']) ?></div>
              <div class="product-meta">
                <span>Terjual <?= number_format((int)$prod['sold_count']) ?></span>
                <span>Stok: <?= (int)$prod['stock'] ?></span>
              </div>
              <a href="<?= BASE_URL ?>/product-detail.php?slug=<?= e($prod['slug']) ?>" class="btn-add-cart">
                <i class="bi bi-eye"></i> Lihat Produk
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<script>
const maxStock = <?= (int)$product['stock'] ?>;
function changeQty(delta) {
  const input = document.getElementById('qtyInput');
  let v = parseInt(input.value) + delta;
  v = Math.max(1, Math.min(maxStock, v));
  input.value = v;
}
async function addToCartDetail(productId) {
  const qty = parseInt(document.getElementById('qtyInput')?.value || 1);
  const btn = document.getElementById('btnCart');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Menambahkan...'; }
  try {
    const res = await fetch(BASE_URL + '/cart-action.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: `action=add&product_id=${productId}&qty=${qty}`
    });
    const data = await res.json();
    if (data.success && btn) {
      btn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Ditambahkan!';
      btn.style.background = '#22c55e';
      document.querySelectorAll('.cart-badge,.bottom-cart-badge').forEach(b => b.textContent = data.cart_count);
      setTimeout(() => {
        btn.innerHTML = '<i class="bi bi-cart-plus me-2"></i>Tambah ke Keranjang';
        btn.style.background = '';
        btn.disabled = false;
      }, 2000);
    }
  } catch(e) { if(btn){btn.disabled=false;btn.innerHTML='<i class="bi bi-cart-plus me-2"></i>Tambah ke Keranjang';} }
}
function switchImg(url, el) {
  document.getElementById('mainImg').style.opacity = '0';
  setTimeout(() => {
    document.getElementById('mainImg').src = url;
    document.getElementById('mainImg').style.opacity = '1';
  }, 200);
  document.querySelectorAll('.thumb-item').forEach(t => t.style.borderColor = 'var(--gray-200)');
  el.style.borderColor = 'var(--primary)';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

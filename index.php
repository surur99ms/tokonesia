<?php
// ============================================================
// TOKONESIA — Beranda (Home Page)
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// --- Ambil data dari DB ---
$heroTitle    = getSetting('hero_title',    'Belanja Lebih Mahal, Lebih Sulit!');
$heroSubtitle = getSetting('hero_subtitle', 'Temukan ribuan produk pilihan dengan harga terbaik dan pengiriman cepat ke seluruh Malaysia.');
$waNumber     = getSetting('whatsapp_number', '6281234567890');
$siteName     = getSetting('site_name', 'Tokonesia');
$featuredProds = getFeaturedProducts(6);
$categories    = getAllCategories();

$pageTitle = $siteName . ' — Belanja Online Terpercaya';
$pageDesc  = $heroSubtitle;

// Inject BASE_URL untuk JS
$baseUrl = BASE_URL;
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<!-- Inject BASE_URL ke JavaScript -->
<script>const BASE_URL = '<?= $baseUrl ?>';</script>

<!-- ═══════════════════════════════════════════════
     HERO SECTION
════════════════════════════════════════════════ -->
<section class="hero-section">
  <div class="container position-relative" style="z-index:1;">
    <div class="row align-items-center gy-4">

      <!-- Left: Copy -->
      <div class="col-lg-6">
        <div class="hero-badge">
          <i class="bi bi-lightning-charge-fill"></i>
          Flash Sale Aktif — Diskon s/d 100%
        </div>

        <h1 class="hero-title"><?= e($heroTitle) ?></h1>

        <p class="hero-subtitle"><?= e($heroSubtitle) ?></p>

        <div class="hero-btns">
          <a href="<?= BASE_URL ?>/products.php" class="btn-hero-primary">
            <i class="bi bi-bag-heart-fill"></i>
            Mulai Belanja
          </a>
          <a href="<?= BASE_URL ?>/track.php" class="btn-hero-outline">
            <i class="bi bi-geo-alt"></i>
            Lacak Misbah
          </a>
        </div>

        <!-- Quick Stats -->
        <div class="d-flex gap-4 mt-4">
          <div>
            <div class="fw-800 text-white fs-5">10K+</div>
            <div style="color:rgba(255,255,255,.7);font-size:.78rem;">Produk</div>
          </div>
          <div style="width:1px;background:rgba(255,255,255,.2);"></div>
          <div>
            <div class="fw-800 text-white fs-5">50K+</div>
            <div style="color:rgba(255,255,255,.7);font-size:.78rem;">Pembeli</div>
          </div>
          <div style="width:1px;background:rgba(255,255,255,.2);"></div>
          <div>
            <div class="fw-800 text-white fs-5">4.9★</div>
            <div style="color:rgba(255,255,255,.7);font-size:.78rem;">Rating</div>
          </div>
        </div>
      </div>

      <!-- Right: Floating Illustration -->
      <div class="col-lg-6 d-none d-lg-flex justify-content-center">
        <div class="hero-illustration">
          <div class="hero-emoji-bg">🛍️</div>
          <div class="hero-stats" style="position:absolute;right:-20px;">
            <div class="hero-float-card">
              <div class="icon-wrap">📦</div>
              <div>
                <div style="font-size:.75rem;opacity:.8;">Pesanan Baru</div>
                <div>+128 hari ini</div>
              </div>
            </div>
            <div class="hero-float-card">
              <div class="icon-wrap">⚡</div>
              <div>
                <div style="font-size:.75rem;opacity:.8;">Pengiriman</div>
                <div>Same Day Ready</div>
              </div>
            </div>
            <div class="hero-float-card">
              <div class="icon-wrap">🔒</div>
              <div>
                <div style="font-size:.75rem;opacity:.8;">Pembayaran</div>
                <div>100% Aman</div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════
     TRUST BADGES
════════════════════════════════════════════════ -->
<div class="trust-section">
  <div class="container">
    <div class="row row-cols-2 row-cols-md-4 g-2 g-md-3">
      <?php
      $trustItems = [
        ['bi-truck',         '#22c55e', 'Gratis Ongkir',    'Min. pembelian Rp 50rb'],
        ['bi-shield-check',  '#6C63FF', 'Garansi Produk',   'Uang kembali 7 hari'],
        ['bi-headset',       '#f59e0b', 'CS 24 Jam',        'Siap membantu Anda'],
        ['bi-arrow-repeat',  '#ef4444', 'Retur Mudah',      'Proses cepat & simpel'],
      ];
      foreach ($trustItems as [$icon, $color, $title, $desc]): ?>
        <div class="col">
          <div class="trust-item">
            <div class="trust-icon" style="background:<?= $color ?>22;color:<?= $color ?>;">
              <i class="bi <?= $icon ?>"></i>
            </div>
            <div>
              <h6><?= $title ?></h6>
              <p><?= $desc ?></p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════
     KATEGORI
════════════════════════════════════════════════ -->
<section class="section-wrapper">
  <div class="container">
    <div class="section-header">
      <div>
        <h2 class="section-title">Belanja Berdasarkan Kategori</h2>
        <p class="section-subtitle mb-0">Temukan produk sesuai kebutuhanmu</p>
      </div>
      <a href="<?= BASE_URL ?>/products.php" class="section-link">
        Semua <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    <div class="category-scroll">
      <!-- "Semua" pill -->
      <a href="<?= BASE_URL ?>/products.php" class="cat-pill">
        <div class="cat-pill-icon" style="background:var(--primary);color:#fff;border-color:var(--primary);">
          <i class="bi bi-grid-3x3-gap"></i>
        </div>
        <span>Semua</span>
      </a>

      <?php foreach ($categories as $cat): ?>
        <a href="<?= BASE_URL ?>/products.php?cat=<?= e($cat['slug']) ?>" class="cat-pill">
          <div class="cat-pill-icon">
            <i class="bi <?= e($cat['icon']) ?>"></i>
          </div>
          <span><?= e($cat['name']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════
     PRODUK TERLARIS (Featured)
════════════════════════════════════════════════ -->
<section class="section-wrapper" style="padding-top:0;">
  <div class="container">
    <div class="section-header">
      <div>
        <h2 class="section-title">
          <i class="bi bi-fire me-2" style="color:#f59e0b;"></i>Produk Terlaris
        </h2>
        <p class="section-subtitle mb-0">Pilihan terfavorit para pembeli kami</p>
      </div>
      <a href="<?= BASE_URL ?>/products.php?sort=sold" class="section-link">
        Lihat Semua <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    <?php if (empty($featuredProds)): ?>
      <!-- Empty State -->
      <div class="empty-state">
        <i class="bi bi-box-seam d-block"></i>
        <h5>Belum Ada Produk</h5>
        <p class="small">Produk unggulan akan muncul di sini.</p>
      </div>
    <?php else: ?>

      <div class="products-grid">
        <?php foreach ($featuredProds as $prod):
          $imgPrimary   = $prod['img_primary']
            ? (file_exists(UPLOAD_DIR . $prod['img_primary']) ? UPLOAD_URL . $prod['img_primary'] : BASE_URL . '/assets/images/placeholder.png')
            : BASE_URL . '/assets/images/placeholder.png';
          $imgSecondary = $prod['img_secondary']
            ? (file_exists(UPLOAD_DIR . $prod['img_secondary']) ? UPLOAD_URL . $prod['img_secondary'] : $imgPrimary)
            : $imgPrimary;
          $isOutOfStock = (int)$prod['stock'] === 0;
        ?>
          <div class="product-card <?= $isOutOfStock ? 'out-of-stock' : '' ?>">

            <!-- Gambar -->
            <div class="product-card-img">
              <img class="img-primary"
                   src="<?= e($imgPrimary) ?>"
                   alt="<?= e($prod['name']) ?>"
                   loading="lazy" />
              <img class="img-secondary"
                   src="<?= e($imgSecondary) ?>"
                   alt="<?= e($prod['name']) ?> - tampilan lain"
                   loading="lazy" />

              <!-- Label Kategori -->
              <span class="product-cat-label"><?= e($prod['category_name']) ?></span>

              <!-- Wishlist -->
              <button class="btn-add-cart-icon btn-wishlist" data-product-id="<?= (int)$prod['id'] ?>" title="Tambah ke Keranjang" aria-label="Tambah ke Keranjang">
                <i class="bi bi-cart-plus"></i>
              </button>
            </div>

            <!-- Info -->
            <div class="product-card-body">
              <div class="product-name"><?= e($prod['name']) ?></div>
              <div class="product-price"><?= formatRupiah((float)$prod['price']) ?></div>
              <div class="product-meta">
                <span><i class="bi bi-bag-check me-1"></i>Terjual <?= number_format((int)$prod['sold_count']) ?></span>
                <span>Stok: <?= (int)$prod['stock'] ?></span>
              </div>

              <?php if ($isOutOfStock): ?>
                <button class="btn-add-cart" disabled>
                  <i class="bi bi-x-circle"></i> Stok Habis
                </button>
              <?php else: ?>
                <div class="d-flex gap-1">
                  <a href="<?= BASE_URL ?>/product-detail.php?slug=<?= e($prod['slug']) ?>"
                     class="btn-add-cart flex-grow-1" style="background:var(--gray-100);color:var(--dark);">
                    <i class="bi bi-eye"></i> Detail
                  </a>
                  <a href="#"
                     class="btn-buy-now btn-add-cart"
                     data-product-id="<?= (int)$prod['id'] ?>"
                     title="Beli Sekarang"
                     style="flex:2;">
                    <i class="bi bi-cart-plus"></i> Beli
                  </a>
                </div>
              <?php endif; ?>
            </div>

          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

    <!-- Load More -->
    <div class="text-center mt-4">
      <a href="<?= BASE_URL ?>/products.php" class="btn btn-outline-primary px-5 py-2 fw-600" style="border-radius:50px;">
        <i class="bi bi-grid me-2"></i>Lihat Semua Produk
      </a>
    </div>

  </div>
</section>

<!-- ═══════════════════════════════════════════════
     BANNER CTA — Hubungi via WhatsApp
════════════════════════════════════════════════ -->
<section class="section-wrapper" style="padding-top:0;">
  <div class="container">
    <div class="cta-banner" style="
      background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
      border-radius: 20px;
      padding: 40px 32px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      overflow: hidden;
      position: relative;
    ">
      <!-- Background decoration -->
      <div style="position:absolute;width:300px;height:300px;border-radius:50%;background:rgba(108,99,255,.15);top:-100px;right:-50px;pointer-events:none;"></div>

      <div style="position:relative;z-index:1;">
        <div style="color:rgba(255,255,255,.6);font-size:.8rem;font-weight:600;margin-bottom:6px;">
          <i class="bi bi-headset me-1"></i>Customer Service
        </div>
        <h3 style="color:#fff;font-weight:800;margin-bottom:8px;">Ada pertanyaan? Kami siap membantu!</h3>
        <p style="color:rgba(255,255,255,.65);margin:0;font-size:.9rem;">
          Chat langsung dengan tim kami via WhatsApp, 24 jam sehari, 7 hari seminggu.
        </p>
      </div>

      <a href="https://wa.me/<?= e($waNumber) ?>?text=Halo%20<?= urlencode($siteName) ?>,%20saya%20ingin%20bertanya..."
         target="_blank"
         rel="noopener noreferrer"
         style="
           background:#25D366;color:#fff;
           border-radius:50px;
           padding:14px 28px;
           font-weight:700;
           text-decoration:none;
           display:inline-flex;align-items:center;gap:10px;
           flex-shrink:0;
           position:relative;z-index:1;
           transition:transform .2s,box-shadow .2s;
         "
         onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(37,211,102,.4)';"
         onmouseout="this.style.transform='';this.style.boxShadow='';">
        <i class="bi bi-whatsapp fs-5"></i>
        Chat WhatsApp
      </a>
    </div>
  </div>
</section>

<!-- ── WhatsApp Floating Button ── -->
<a href="https://wa.me/<?= e($waNumber) ?>" class="wa-float" target="_blank" rel="noopener" title="Chat WhatsApp">
  <i class="bi bi-whatsapp"></i>
</a>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

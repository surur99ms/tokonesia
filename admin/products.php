<?php
// ============================================================
// TOKONESIA — Admin: Manajemen Produk (CRUD)
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db     = getDB();
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// ── DELETE ──────────────────────────────────────────────
if ($action === 'delete' && $editId) {
    // Hapus gambar fisik
    $imgs = $db->prepare('SELECT image_path FROM product_images WHERE product_id = ?');
    $imgs->execute([$editId]);
    foreach ($imgs->fetchAll() as $img) {
        $file = UPLOAD_DIR . $img['image_path'];
        if (file_exists($file)) unlink($file);
    }
    $db->prepare('DELETE FROM products WHERE id = ?')->execute([$editId]);
    setFlash('success', 'Produk berhasil dihapus.');
    redirect(BASE_URL . '/admin/products.php');
}

// ── SAVE (Add / Edit) ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price       = (float)str_replace(['.', ','], ['', '.'], $_POST['price'] ?? 0);
    $stock       = (int)($_POST['stock']       ?? 0);
    $isFeatured  = isset($_POST['is_featured']) ? 1 : 0;
    $slug        = makeSlug($name);
    $errors      = [];

    if (!$name)       $errors[] = 'Nama produk wajib diisi.';
    if (!$categoryId) $errors[] = 'Kategori wajib dipilih.';
    if ($price <= 0)  $errors[] = 'Harga harus lebih dari 0.';

    if (!$errors) {
        // Ensure unique slug
        $slugCheck = $db->prepare('SELECT id FROM products WHERE slug = ? AND id != ?');
        $slugCheck->execute([$slug, $editId ?: 0]);
        if ($slugCheck->fetch()) $slug .= '-' . time();

        if ($editId) {
            // Update
            $db->prepare(
                'UPDATE products SET category_id=?, name=?, slug=?, description=?, price=?, stock=?, is_featured=? WHERE id=?'
            )->execute([$categoryId, $name, $slug, $description, $price, $stock, $isFeatured, $editId]);
            $productId = $editId;
            $msg = 'Produk berhasil diperbarui.';
        } else {
            // Insert
            $db->prepare(
                'INSERT INTO products (category_id, name, slug, description, price, stock, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([$categoryId, $name, $slug, $description, $price, $stock, $isFeatured]);
            $productId = $db->lastInsertId();
            $msg = 'Produk berhasil ditambahkan.';
        }

        // Upload gambar
        if (!empty($_FILES['images']['name'][0])) {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            foreach ($_FILES['images']['tmp_name'] as $i => $tmpName) {
                if (!$tmpName) continue;
                $origName  = $_FILES['images']['name'][$i];
                $ext       = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) continue;
                $fileName  = 'prod_' . $productId . '_' . time() . '_' . $i . '.' . $ext;
                $dest      = UPLOAD_DIR . $fileName;
                if (move_uploaded_file($tmpName, $dest)) {
                    $isPrimary = ($i === 0) ? 1 : 0;
                    if ($isPrimary && $editId) {
                        // Check existing primary
                        $hasPrimary = $db->prepare('SELECT id FROM product_images WHERE product_id = ? AND is_primary = 1');
                        $hasPrimary->execute([$productId]);
                        if ($hasPrimary->fetch()) {
                            $isPrimary = 0;
                        }
                    }
                    $db->prepare(
                        'INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, ?, ?)'
                    )->execute([$productId, $fileName, $isPrimary, $i]);
                }
            }
        }

        setFlash('success', $msg);
        redirect(BASE_URL . '/admin/products.php');
    }
}

// ── DATA ─────────────────────────────────────────────────
$categories = getAllCategories();
$editProduct = null;
$editImages  = [];

if (($action === 'edit' || ($action === 'add' && isset($errors))) && $editId) {
    $s = $db->prepare('SELECT * FROM products WHERE id = ?');
    $s->execute([$editId]);
    $editProduct = $s->fetch();
    $si = $db->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order');
    $si->execute([$editId]);
    $editImages = $si->fetchAll();
}

// Product list
$products = $db->query(
    'SELECT p.*, c.name AS category_name,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS img
     FROM products p JOIN categories c ON c.id = p.category_id
     ORDER BY p.created_at DESC'
)->fetchAll();

$pageTitle = ($action === 'list' ? 'Manajemen Produk' : ($editId ? 'Edit Produk' : 'Tambah Produk')) . ' — Admin';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<?php if ($action === 'list'): ?>
<!-- ════ LIST VIEW ════ -->
<div class="admin-card">
  <div class="admin-card-header">
    <h5 class="admin-card-title"><i class="bi bi-box-seam me-2"></i>Daftar Produk</h5>
    <a href="?action=add" class="btn btn-primary btn-sm fw-600">
      <i class="bi bi-plus-lg me-1"></i>Tambah Produk
    </a>
  </div>

  <!-- Search -->
  <div class="mb-3">
    <input type="text" id="tableSearch" class="form-control" placeholder="🔍  Cari produk...">
  </div>

  <div class="table-responsive">
    <table class="table admin-table" id="adminTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Gambar</th>
          <th>Nama Produk</th>
          <th>Kategori</th>
          <th>Harga</th>
          <th>Stok</th>
          <th>Terjual</th>
          <th>Featured</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $i => $p):
          $imgUrl = $p['img']
            ? (file_exists(UPLOAD_DIR . $p['img']) ? UPLOAD_URL . $p['img'] : BASE_URL . '/assets/images/placeholder.png')
            : BASE_URL . '/assets/images/placeholder.png';
        ?>
          <tr>
            <td class="text-muted"><?= $i + 1 ?></td>
            <td><img src="<?= e($imgUrl) ?>" class="product-thumb" alt="" /></td>
            <td>
              <div class="fw-700"><?= e($p['name']) ?></div>
              <div class="text-muted" style="font-size:.75rem;"><?= e($p['slug']) ?></div>
            </td>
            <td><?= e($p['category_name']) ?></td>
            <td class="fw-600" style="color:var(--primary);"><?= formatRupiah((float)$p['price']) ?></td>
            <td>
              <span class="fw-600 <?= $p['stock'] == 0 ? 'text-danger' : ($p['stock'] < 5 ? 'text-warning' : 'text-success') ?>">
                <?= (int)$p['stock'] ?>
              </span>
            </td>
            <td><?= number_format((int)$p['sold_count']) ?></td>
            <td>
              <?php if ($p['is_featured']): ?>
                <span class="badge bg-warning text-dark">⭐ Ya</span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="d-flex gap-1">
                <a href="<?= BASE_URL ?>/product-detail.php?slug=<?= e($p['slug']) ?>" target="_blank"
                   class="btn btn-sm btn-outline-secondary" title="Lihat"><i class="bi bi-eye"></i></a>
                <a href="?action=edit&id=<?= $p['id'] ?>"
                   class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                <button onclick="confirmDelete('?action=delete&id=<?= $p['id'] ?>','<?= e(addslashes($p['name'])) ?>')"
                        class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
          <tr><td colspan="9" class="text-center text-muted py-5">Belum ada produk. <a href="?action=add">Tambah sekarang</a></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php else: ?>
<!-- ════ FORM ADD / EDIT ════ -->
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger mb-3">
    <ul class="mb-0 ps-3"><?php foreach ($errors as $e) echo '<li>' . e($e) . '</li>'; ?></ul>
  </div>
<?php endif; ?>

<div class="row g-4 align-items-start">
  <!-- Form -->
  <div class="col-lg-8">
    <form method="POST" enctype="multipart/form-data">
      <div class="admin-card mb-3">
        <h6 class="fw-700 mb-3"><i class="bi bi-info-circle me-2"></i>Informasi Produk</h6>

        <div class="mb-3">
          <label class="form-label fw-600 small">Nama Produk <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control"
                 value="<?= e($editProduct['name'] ?? $_POST['name'] ?? '') ?>"
                 placeholder="Nama produk" required />
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-600 small">Kategori <span class="text-danger">*</span></label>
            <select name="category_id" class="form-select" required>
              <option value="">-- Pilih Kategori --</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"
                  <?= ($editProduct['category_id'] ?? $_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                  <?= e($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-600 small">Harga (Rp) <span class="text-danger">*</span></label>
            <input type="text" name="price" class="form-control"
                   value="<?= e($editProduct['price'] ?? $_POST['price'] ?? '') ?>"
                   placeholder="150000" required />
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-600 small">Stok</label>
            <input type="number" name="stock" class="form-control" min="0"
                   value="<?= e($editProduct['stock'] ?? $_POST['stock'] ?? 0) ?>" />
          </div>
          <div class="col-md-6 d-flex align-items-end pb-1">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured"
                     <?= ($editProduct['is_featured'] ?? 0) ? 'checked' : '' ?>>
              <label class="form-check-label fw-600 small" for="isFeatured">
                ⭐ Tampilkan di Produk Terlaris
              </label>
            </div>
          </div>
        </div>

        <div class="mb-0">
          <label class="form-label fw-600 small">Deskripsi</label>
          <textarea name="description" class="form-control" rows="5"
                    placeholder="Deskripsi produk..."><?= e($editProduct['description'] ?? $_POST['description'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- Upload Gambar -->
      <div class="admin-card mb-3">
        <h6 class="fw-700 mb-3"><i class="bi bi-images me-2"></i>Gambar Produk</h6>

        <!-- Existing images (edit mode) -->
        <?php if ($editImages): ?>
          <div class="mb-3">
            <div class="small text-muted mb-2">Gambar saat ini:</div>
            <div class="upload-preview" id="existingImages">
              <?php foreach ($editImages as $img):
                $url = file_exists(UPLOAD_DIR . $img['image_path']) ? UPLOAD_URL . $img['image_path'] : BASE_URL . '/assets/images/placeholder.png';
              ?>
                <div class="upload-preview-item">
                  <img src="<?= e($url) ?>" />
                  <?php if ($img['is_primary']): ?>
                    <span style="position:absolute;bottom:2px;left:2px;background:#6C63FF;color:#fff;font-size:.6rem;border-radius:4px;padding:1px 4px;">Utama</span>
                  <?php endif; ?>
                  <button class="remove-img" type="button"
                          onclick="deleteImage(<?= $img['id'] ?>, this)"
                          title="Hapus gambar"><i class="bi bi-x"></i></button>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="upload-zone" onclick="document.getElementById('imageInput').click()">
          <i class="bi bi-cloud-upload fs-2 text-muted d-block mb-2"></i>
          <div class="fw-600 small">Klik untuk upload gambar baru</div>
          <div class="text-muted" style="font-size:.75rem;">JPG, PNG, WEBP — Bisa pilih banyak (maks 5MB/file)<br/>Gambar pertama = Gambar Utama</div>
        </div>
        <input type="file" id="imageInput" name="images[]" accept="image/*" multiple style="display:none;">
        <div class="upload-preview mt-2" id="imagePreview"></div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary fw-700 px-4">
          <i class="bi bi-save me-2"></i><?= $editId ? 'Simpan Perubahan' : 'Tambah Produk' ?>
        </button>
        <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>

  <!-- Sidebar Tips -->
  <div class="col-lg-4">
    <div class="admin-card">
      <h6 class="fw-700 mb-3"><i class="bi bi-lightbulb me-2 text-warning"></i>Tips Produk</h6>
      <ul class="small text-muted ps-3" style="line-height:2;">
        <li>Gunakan nama produk yang jelas dan deskriptif</li>
        <li>Upload minimal 2 gambar: tampak depan & tampak samping</li>
        <li>Tulis deskripsi yang detail agar pembeli percaya</li>
        <li>Centang "Produk Terlaris" untuk tampil di halaman utama</li>
        <li>Pastikan harga sudah termasuk margin keuntungan</li>
      </ul>
    </div>
  </div>
</div>

<script>
async function deleteImage(imgId, btn) {
  if (!confirm('Hapus gambar ini?')) return;
  const res = await fetch('<?= BASE_URL ?>/admin/image-delete.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'id=' + imgId
  });
  const data = await res.json();
  if (data.success) btn.closest('.upload-preview-item').remove();
  else alert('Gagal menghapus gambar.');
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

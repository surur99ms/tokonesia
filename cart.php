<?php
// ============================================================
// TOKONESIA — Keranjang Belanja
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Keranjang — ' . getSetting('site_name', 'Tokonesia');
$db     = getDB();
$userId = $_SESSION['user_id'] ?? null;
$sid    = session_id();

if ($userId) {
    $stmt = $db->prepare(
        'SELECT c.id AS cart_id, c.quantity, c.is_selected, p.id, p.name, p.price, p.stock,
                (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS img
         FROM cart c
         JOIN products p ON p.id = c.product_id
         WHERE c.user_id = ?'
    );
    $stmt->execute([$userId]);
} else {
    $stmt = $db->prepare(
        'SELECT c.id AS cart_id, c.quantity, c.is_selected, p.id, p.name, p.price, p.stock,
                (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS img
         FROM cart c
         JOIN products p ON p.id = c.product_id
         WHERE c.session_id = ?'
    );
    $stmt->execute([$sid]);
}
$items = $stmt->fetchAll();
$total = array_sum(array_map(fn($i) => $i['is_selected'] ? $i['price'] * $i['quantity'] : 0, $items));
$selectedCount = array_sum(array_column($items, 'is_selected'));

require_once __DIR__ . '/includes/header.php';
?>
<script>const BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container" style="padding-top:28px;padding-bottom:60px;max-width:860px;">
  <h1 class="fw-800 mb-4" style="font-size:1.4rem;"><i class="bi bi-cart3 me-2"></i>Keranjang Belanja</h1>

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <i class="bi bi-cart-x d-block"></i>
      <h5>Keranjang Masih Kosong</h5>
      <p>Yuk, mulai belanja produk favorit kamu!</p>
      <a href="<?= BASE_URL ?>/products.php" class="btn btn-primary mt-2">Belanja Sekarang</a>
    </div>
  <?php else: ?>
    <div class="row g-4 align-items-start">
      <!-- Cart Items -->
      <div class="col-md-8">
        <div class="bg-white rounded-4 border shadow-sm overflow-hidden mb-3 p-3 d-flex align-items-center">
          <div class="form-check m-0">
            <input class="form-check-input" type="checkbox" id="checkAll" style="width:1.2rem;height:1.2rem;cursor:pointer;" onchange="toggleAll(this.checked)" <?= $selectedCount == count($items) ? 'checked' : '' ?>>
            <label class="form-check-label ms-2 fw-600" for="checkAll" style="cursor:pointer;padding-top:2px;">Pilih Semua</label>
          </div>
        </div>
        <div class="bg-white rounded-4 border shadow-sm overflow-hidden">
          <?php foreach ($items as $i => $item):
            $imgUrl = $item['img']
              ? (file_exists(UPLOAD_DIR . $item['img']) ? UPLOAD_URL . $item['img'] : BASE_URL . '/assets/images/placeholder.png')
              : BASE_URL . '/assets/images/placeholder.png';
          ?>
            <div class="d-flex align-items-center gap-3 p-3 <?= $i < count($items)-1 ? 'border-bottom' : '' ?>" data-cart-id="<?= $item['cart_id'] ?>">
              <!-- Checkbox -->
              <div class="form-check m-0">
                <input class="form-check-input item-check" type="checkbox" style="width:1.2rem;height:1.2rem;cursor:pointer;"
                       onchange="toggleItem(<?= $item['cart_id'] ?>, this.checked)"
                       <?= $item['is_selected'] ? 'checked' : '' ?>>
              </div>
              <!-- Image -->
              <img src="<?= e($imgUrl) ?>" alt="<?= e($item['name']) ?>"
                   style="width:72px;height:72px;object-fit:cover;border-radius:10px;flex-shrink:0;" />
              <!-- Info -->
              <div class="flex-grow-1 min-w-0">
                <div class="fw-700 small" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($item['name']) ?></div>
                <div class="fw-800" style="color:var(--primary);"><?= formatRupiah((float)$item['price']) ?></div>
              </div>
              <!-- Qty Control -->
              <div class="d-flex align-items-center gap-1 flex-shrink-0">
                <button class="btn btn-sm btn-outline-secondary rounded-2 px-2" onclick="updateQty(<?= $item['cart_id'] ?>, <?= $item['quantity'] - 1 ?>)">−</button>
                <span class="fw-700 px-2" id="qty-<?= $item['cart_id'] ?>"><?= $item['quantity'] ?></span>
                <button class="btn btn-sm btn-outline-secondary rounded-2 px-2" onclick="updateQty(<?= $item['cart_id'] ?>, <?= $item['quantity'] + 1 ?>)">+</button>
              </div>
              <!-- Subtotal & Delete -->
              <div class="text-end flex-shrink-0" style="min-width:80px;">
                <div class="fw-700 small" id="sub-<?= $item['cart_id'] ?>"><?= formatRupiah($item['price'] * $item['quantity']) ?></div>
                <button class="btn btn-sm text-danger border-0 p-1 mt-1" onclick="removeItem(<?= $item['cart_id'] ?>)" title="Hapus">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Summary -->
      <div class="col-md-4">
        <div class="bg-white rounded-4 border shadow-sm p-3">
          <h6 class="fw-700 mb-3">Ringkasan Pesanan</h6>
          <div class="d-flex justify-content-between small mb-2">
            <span class="text-muted" id="subtotal-count">Subtotal (<?= $selectedCount ?> produk)</span>
            <span class="fw-700" id="grand-total"><?= formatRupiah($total) ?></span>
          </div>
          <div class="d-flex justify-content-between small mb-3">
            <span class="text-muted">Ongkir</span>
            <span class="text-success fw-700">Gratis</span>
          </div>
          <hr />
          <div class="d-flex justify-content-between mb-3">
            <span class="fw-700">Total</span>
            <span class="fw-800 fs-5" style="color:var(--primary);" id="total-display"><?= formatRupiah($total) ?></span>
          </div>
          <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-primary-custom w-100 py-2">
            <i class="bi bi-credit-card me-2"></i>Lanjut Checkout
          </a>
          <a href="<?= BASE_URL ?>/products.php" class="btn btn-outline-secondary w-100 mt-2 py-2 small">
            <i class="bi bi-arrow-left me-1"></i>Lanjut Belanja
          </a>
        </div>
      </div>
    </div>

    <script>
    const prices = <?= json_encode(array_column($items, 'price', 'cart_id')) ?>;

    async function toggleItem(cartId, isSelected) {
      await fetch(BASE_URL + '/cart-action.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=toggle_select&cart_id=${cartId}&is_selected=${isSelected ? 1 : 0}`
      });
      recalcTotal();
      checkAllStatus();
    }

    async function toggleAll(isSelected) {
      document.querySelectorAll('.item-check').forEach(cb => cb.checked = isSelected);
      await fetch(BASE_URL + '/cart-action.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=toggle_all&is_selected=${isSelected ? 1 : 0}`
      });
      recalcTotal();
    }

    function checkAllStatus() {
      const allCb = document.querySelectorAll('.item-check');
      const allChecked = Array.from(allCb).every(cb => cb.checked);
      document.getElementById('checkAll').checked = allChecked;
    }

    async function updateQty(cartId, qty) {
      if (qty < 1) { return removeItem(cartId); }
      const res = await fetch(BASE_URL + '/cart-action.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=update&cart_id=${cartId}&qty=${qty}`
      });
      const data = await res.json();
      if (data.success) {
        document.getElementById('qty-' + cartId).textContent = qty;
        const sub = prices[cartId] * qty;
        document.getElementById('sub-' + cartId).textContent = 'Rp ' + sub.toLocaleString('id-ID');
        recalcTotal();
        updateBadges(data.cart_count);
      }
    }

    async function removeItem(cartId) {
      if (!confirm('Hapus item ini?')) return;
      const res = await fetch(BASE_URL + '/cart-action.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=remove&cart_id=${cartId}`
      });
      const data = await res.json();
      if (data.success) {
        document.querySelector(`[data-cart-id="${cartId}"]`)?.remove();
        delete prices[cartId];
        recalcTotal();
        updateBadges(data.cart_count);
        checkAllStatus();
        if (Object.keys(prices).length === 0) location.reload();
      }
    }

    function recalcTotal() {
      let total = 0;
      let selectedCount = 0;
      document.querySelectorAll('.item-check').forEach(cb => {
        if (cb.checked) {
          const id = cb.closest('[data-cart-id]').getAttribute('data-cart-id');
          const qty = parseInt(document.getElementById('qty-' + id)?.textContent || 0);
          total += prices[id] * qty;
          selectedCount++;
        }
      });
      const fmt = 'Rp ' + total.toLocaleString('id-ID');
      document.getElementById('grand-total').textContent = fmt;
      document.getElementById('total-display').textContent = fmt;
      document.getElementById('subtotal-count').textContent = 'Subtotal (' + selectedCount + ' produk)';
    }

    function updateBadges(count) {
      document.querySelectorAll('.cart-badge, .bottom-cart-badge').forEach(b => {
        b.textContent = count;
        b.style.display = count > 0 ? '' : 'none';
      });
    }
    </script>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

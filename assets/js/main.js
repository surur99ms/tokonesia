/* ============================================================
   TOKONESIA — JavaScript (Main)
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

  // ── Add to Cart (AJAX) ──────────────────────────────────────
  document.querySelectorAll('.btn-add-cart[data-product-id]:not(.btn-buy-now):not(.btn-add-cart-icon)').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const productId = btn.dataset.productId;
      const original  = btn.innerHTML;

      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Menambahkan...';
      btn.disabled  = true;

      try {
        const res  = await fetch(BASE_URL + '/cart-action.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body:    `action=add&product_id=${productId}&qty=1`
        });
        const data = await res.json();

        if (data.success) {
          btn.innerHTML = '<i class="bi bi-check-lg"></i> Ditambahkan!';
          btn.style.background = '#22c55e';
          // Update all cart badges
          document.querySelectorAll('.cart-badge, .bottom-cart-badge').forEach(b => {
            b.textContent = data.cart_count;
            b.style.display = data.cart_count > 0 ? '' : 'none';
          });
        } else {
          btn.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + (data.message || 'Gagal');
          btn.style.background = '#ef4444';
        }
      } catch {
        btn.innerHTML = '<i class="bi bi-wifi-off"></i> Error';
        btn.style.background = '#ef4444';
      }

      setTimeout(() => {
        btn.innerHTML = original;
        btn.style.background = '';
        btn.disabled = false;
      }, 2200);
    });
  });

  // ── Buy Now (AJAX + Redirect) ───────────────────────────────
  document.querySelectorAll('.btn-buy-now[data-product-id]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const productId = btn.dataset.productId;
      const original  = btn.innerHTML;

      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
      btn.disabled  = true;

      try {
        const res  = await fetch(BASE_URL + '/cart-action.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body:    `action=add&product_id=${productId}&qty=1`
        });
        const data = await res.json();

        if (data.success) {
          window.location.href = BASE_URL + '/checkout.php';
        } else {
          btn.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + (data.message || 'Gagal');
          btn.style.background = '#ef4444';
          setTimeout(() => {
            btn.innerHTML = original;
            btn.style.background = '';
            btn.disabled = false;
          }, 2200);
        }
      } catch {
        btn.innerHTML = '<i class="bi bi-wifi-off"></i> Error';
        btn.style.background = '#ef4444';
        setTimeout(() => {
          btn.innerHTML = original;
          btn.style.background = '';
          btn.disabled = false;
        }, 2200);
      }
    });
  });

  // ── Add to Cart via Icon (AJAX) ─────────────────────────────
  document.querySelectorAll('.btn-add-cart-icon[data-product-id]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const productId = btn.dataset.productId;
      
      const icon = btn.querySelector('i');
      if(icon) icon.className = 'bi bi-hourglass-split';
      btn.disabled = true;

      try {
        const res  = await fetch(BASE_URL + '/cart-action.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body:    `action=add&product_id=${productId}&qty=1`
        });
        const data = await res.json();

        if (data.success) {
          if(icon) icon.className = 'bi bi-check-lg';
          btn.style.color = '#22c55e';
          // Update all cart badges
          document.querySelectorAll('.cart-badge, .bottom-cart-badge').forEach(b => {
            b.textContent = data.cart_count;
            b.style.display = data.cart_count > 0 ? '' : 'none';
          });
        } else {
          if(icon) icon.className = 'bi bi-exclamation-triangle';
          btn.style.color = '#ef4444';
        }
      } catch {
        if(icon) icon.className = 'bi bi-wifi-off';
        btn.style.color = '#ef4444';
      }

      setTimeout(() => {
        if(icon) icon.className = 'bi bi-cart-plus';
        btn.style.color = '';
        btn.disabled = false;
      }, 2200);
    });
  });

  // ── Highlight active Bottom Nav ──────────────────────────────
  const path  = window.location.pathname;
  document.querySelectorAll('.bottom-nav-item').forEach(link => {
    const href = link.getAttribute('href') || '';
    if (href && path.includes(href.split('/').pop().split('?')[0])) {
      link.classList.add('active');
    }
  });

  // ── Navbar scroll effect ─────────────────────────────────────
  const navbar = document.querySelector('.toko-navbar');
  window.addEventListener('scroll', () => {
    navbar?.classList.toggle('scrolled', window.scrollY > 40);
  }, { passive: true });

  // ── Lazy image loading ───────────────────────────────────────
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          if (img.dataset.src) { img.src = img.dataset.src; }
          io.unobserve(img);
        }
      });
    }, { rootMargin: '200px' });
    document.querySelectorAll('img[data-src]').forEach(img => io.observe(img));
  }

});

// BASE_URL injected by PHP via <script>

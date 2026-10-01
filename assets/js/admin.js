/* ============================================================
   TOKONESIA — Admin JavaScript
   ============================================================ */
'use strict';

// ── Image upload preview ──────────────────────────────────
function initImageUpload(inputId, previewId) {
  const input   = document.getElementById(inputId);
  const preview = document.getElementById(previewId);
  if (!input || !preview) return;

  input.addEventListener('change', () => {
    preview.innerHTML = '';
    Array.from(input.files).forEach((file, idx) => {
      const reader = new FileReader();
      reader.onload = e => {
        const div = document.createElement('div');
        div.className = 'upload-preview-item';
        div.innerHTML = `<img src="${e.target.result}" /><button class="remove-img" type="button" onclick="this.parentElement.remove()" title="Hapus"><i class="bi bi-x"></i></button>`;
        if (idx === 0) div.querySelector('img').title = 'Gambar Utama';
        preview.appendChild(div);
      };
      reader.readAsDataURL(file);
    });
  });
}

// ── Confirm delete ────────────────────────────────────────
function confirmDelete(url, name) {
  if (confirm(`Yakin ingin menghapus "${name}"?\nTindakan ini tidak dapat dibatalkan.`)) {
    window.location.href = url;
  }
}

// ── DataTable search filter ───────────────────────────────
function filterTable(inputId, tableId) {
  const input  = document.getElementById(inputId);
  const rows   = document.querySelectorAll(`#${tableId} tbody tr`);
  if (!input) return;
  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    rows.forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  // Auto-hide alerts
  document.querySelectorAll('.alert:not(.alert-permanent)').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 4000);
  });

  // Init upload preview on admin pages
  initImageUpload('imageInput', 'imagePreview');
  filterTable('tableSearch', 'adminTable');
});

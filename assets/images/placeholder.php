<?php
// placeholder.php — redirect ke gambar placeholder SVG inline
header('Content-Type: image/svg+xml');
echo '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 400 400">
  <rect width="400" height="400" fill="#f3f4f6"/>
  <rect x="140" y="120" width="120" height="100" rx="8" fill="#d1d5db"/>
  <circle cx="200" cy="155" r="25" fill="#9ca3af"/>
  <polygon points="140,220 200,155 260,195 290,220" fill="#9ca3af"/>
  <text x="200" y="270" text-anchor="middle" font-family="sans-serif" font-size="14" fill="#6b7280">No Image</text>
</svg>';

<?php
// Placeholder PNG redirect — use SVG inline
header('Location: ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/tokonesia/assets/images/placeholder.php');
exit;

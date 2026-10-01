-- ============================================================
-- TOKONESIA — Database Schema
-- Dibuat: 2026-09-02
-- ============================================================

CREATE DATABASE IF NOT EXISTS `tokonesia` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tokonesia`;

-- ─────────────────────────────────────────────
-- 1. TABEL: users
-- ─────────────────────────────────────────────
CREATE TABLE `users` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 2. TABEL: categories
-- ─────────────────────────────────────────────
CREATE TABLE `categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `slug`       VARCHAR(120) NOT NULL UNIQUE,
  `icon`       VARCHAR(50)  DEFAULT 'bi-tag',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 3. TABEL: products
-- ─────────────────────────────────────────────
CREATE TABLE `products` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name`        VARCHAR(200) NOT NULL,
  `slug`        VARCHAR(220) NOT NULL UNIQUE,
  `description` TEXT,
  `price`       DECIMAL(15,2) NOT NULL DEFAULT 0,
  `stock`       INT NOT NULL DEFAULT 0,
  `sold_count`  INT NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 4. TABEL: product_images
-- ─────────────────────────────────────────────
CREATE TABLE `product_images` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_primary`  TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 5. TABEL: orders
-- ─────────────────────────────────────────────
CREATE TABLE `orders` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED DEFAULT NULL,
  `order_code`       VARCHAR(30) NOT NULL UNIQUE,
  `customer_name`    VARCHAR(100) NOT NULL,
  `customer_phone`   VARCHAR(20) NOT NULL,
  `customer_address` TEXT NOT NULL,
  `total_amount`     DECIMAL(15,2) NOT NULL DEFAULT 0,
  `status`           ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `tracking_number`  VARCHAR(100) DEFAULT NULL,
  `notes`            TEXT,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 6. TABEL: order_items
-- ─────────────────────────────────────────────
CREATE TABLE `order_items` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`          INT UNSIGNED NOT NULL,
  `product_id`        INT UNSIGNED NOT NULL,
  `quantity`          INT NOT NULL DEFAULT 1,
  `price_at_purchase` DECIMAL(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 7. TABEL: cart
-- ─────────────────────────────────────────────
CREATE TABLE `cart` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED DEFAULT NULL,
  `session_id` VARCHAR(100) DEFAULT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity`   INT NOT NULL DEFAULT 1,
  `is_selected` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 8. TABEL: settings
-- ─────────────────────────────────────────────
CREATE TABLE `settings` (
  `id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`   VARCHAR(100) NOT NULL UNIQUE,
  `value` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- DATA AWAL (SEED)
-- ─────────────────────────────────────────────

-- Admin default (password: admin123)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Administrator', 'admin@tokonesia.com', '$2y$10$ZoavjL0lurSb0iI5rDPwUe/RvHQsSVAXc4Vlwncx/8BtZYb2b9712', 'admin');
-- Password: admin123

-- Kategori
INSERT INTO `categories` (`name`, `slug`, `icon`) VALUES
('Elektronik',  'elektronik',  'bi-cpu'),
('Fashion',     'fashion',     'bi-bag'),
('Rumah Tangga','rumah-tangga','bi-house'),
('Olahraga',    'olahraga',    'bi-trophy'),
('Kecantikan',  'kecantikan',  'bi-stars');

-- Produk contoh
INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `price`, `stock`, `sold_count`, `is_featured`) VALUES
(1, 'Earbuds Wireless Pro X1', 'earbuds-wireless-pro-x1', 'Earbuds wireless dengan noise cancelling aktif, baterai 30 jam, dan suara jernih.', 299000, 45, 312, 1),
(1, 'Smartwatch Sporty Z7', 'smartwatch-sporty-z7', 'Jam tangan pintar dengan monitor detak jantung, GPS, dan tahan air IP68.', 750000, 20, 187, 1),
(2, 'Kaos Oversize Premium', 'kaos-oversize-premium', 'Kaos bahan cotton combed 30s, oversize fit, tersedia dalam banyak warna.', 89000, 200, 543, 1),
(2, 'Celana Cargo Tactical', 'celana-cargo-tactical', 'Celana cargo dengan banyak kantong, bahan ripstop anti-air, cocok outdoor.', 175000, 80, 224, 1),
(3, 'Set Peralatan Masak Granite', 'set-peralatan-masak-granite', 'Set 5 pcs wajan & panci lapisan granite anti lengket, aman untuk induksi.', 450000, 35, 98, 1),
(4, 'Matras Yoga Anti-Slip 8mm', 'matras-yoga-anti-slip-8mm', 'Matras yoga tebal 8mm dengan permukaan anti-slip, bahan TPE ramah lingkungan.', 185000, 60, 275, 1);

-- Gambar produk (placeholder)
INSERT INTO `product_images` (`product_id`, `image_path`, `is_primary`, `sort_order`) VALUES
(1, 'placeholder_1a.jpg', 1, 0),
(1, 'placeholder_1b.jpg', 0, 1),
(2, 'placeholder_2a.jpg', 1, 0),
(2, 'placeholder_2b.jpg', 0, 1),
(3, 'placeholder_3a.jpg', 1, 0),
(3, 'placeholder_3b.jpg', 0, 1),
(4, 'placeholder_4a.jpg', 1, 0),
(4, 'placeholder_4b.jpg', 0, 1),
(5, 'placeholder_5a.jpg', 1, 0),
(5, 'placeholder_5b.jpg', 0, 1),
(6, 'placeholder_6a.jpg', 1, 0),
(6, 'placeholder_6b.jpg', 0, 1);

-- Pengaturan toko
INSERT INTO `settings` (`key`, `value`) VALUES
('site_name',        'Tokonesia'),
('site_logo',        ''),
('hero_title',       'Belanja Lebih Mudah, Lebih Hemat!'),
('hero_subtitle',    'Temukan ribuan produk pilihan dengan harga terbaik dan pengiriman cepat ke seluruh Indonesia.'),
('whatsapp_number',  '6281234567890'),
('primary_color',    '#6C63FF'),
('currency_symbol',  'Rp');

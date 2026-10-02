<?php
// ============================================================
// Migration: 001_initial_schema
// Deskripsi : Skema awal Tokonesia — semua tabel inti
// Dibuat    : 2026-10-02
// ============================================================

return new class {

    // ── UP ──────────────────────────────────────────────────
    // Dijalankan saat migrasi diterapkan (migrate)
    public function up(PDO $pdo): void
    {
        // 1. users
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`       VARCHAR(100) NOT NULL,
                `email`      VARCHAR(150) NOT NULL UNIQUE,
                `password`   VARCHAR(255) NOT NULL,
                `role`       ENUM('admin','customer') NOT NULL DEFAULT 'customer',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. categories
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `categories` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`       VARCHAR(100) NOT NULL,
                `slug`       VARCHAR(120) NOT NULL UNIQUE,
                `icon`       VARCHAR(50)  DEFAULT 'bi-tag',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. products
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `products` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 4. product_images
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `product_images` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `product_id` INT UNSIGNED NOT NULL,
                `image_path` VARCHAR(255) NOT NULL,
                `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
                `sort_order` TINYINT NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 5. orders
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `orders` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 6. order_items
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `order_items` (
                `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `order_id`          INT UNSIGNED NOT NULL,
                `product_id`        INT UNSIGNED NOT NULL,
                `quantity`          INT NOT NULL DEFAULT 1,
                `price_at_purchase` DECIMAL(15,2) NOT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
                FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 7. cart
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `cart` (
                `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id`     INT UNSIGNED DEFAULT NULL,
                `session_id`  VARCHAR(100) DEFAULT NULL,
                `product_id`  INT UNSIGNED NOT NULL,
                `quantity`    INT NOT NULL DEFAULT 1,
                `is_selected` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
                FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 8. settings
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `settings` (
                `id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `key`   VARCHAR(100) NOT NULL UNIQUE,
                `value` TEXT,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        echo "  OK Semua tabel berhasil dibuat.\n";
    }

    // ── DOWN ─────────────────────────────────────────────────
    // Dijalankan saat migrasi dibatalkan (rollback)
    public function down(PDO $pdo): void
    {
        // Hapus dalam urutan terbalik (hindari FK constraint error)
        $tables = [
            'cart',
            'order_items',
            'orders',
            'product_images',
            'products',
            'categories',
            'users',
            'settings',
        ];

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`;");
            echo "  OK Tabel `{$table}` dihapus.\n";
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
};

<?php
// ============================================================
// Migration: 005_add_coupons_and_wishlists
// Deskripsi: Menambahkan sistem Kupon Promo/Voucher Diskon
//            dan Tabel Wishlist Produk untuk Pelanggan.
// Dibuat   : 2026-10-02
// ============================================================

return new class {

    public function up(PDO $pdo): void
    {
        // 1. Tabel Kupon Diskon (coupons)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `coupons` (
                `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `code`           VARCHAR(50) NOT NULL UNIQUE,
                `title`          VARCHAR(100) NOT NULL,
                `discount_type`  ENUM('fixed', 'percent') NOT NULL DEFAULT 'percent',
                `discount_value` DECIMAL(15,2) NOT NULL,
                `min_spend`      DECIMAL(15,2) NOT NULL DEFAULT 0,
                `max_discount`   DECIMAL(15,2) DEFAULT NULL,
                `usage_limit`    INT UNSIGNED DEFAULT 100,
                `used_count`     INT UNSIGNED NOT NULL DEFAULT 0,
                `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
                `expires_at`     DATETIME NULL,
                `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "  OK Tabel 'coupons' (Kupon Promo) berhasil dibuat.\n";

        // 2. Data Awal Kupon Diskon
        $pdo->exec("
            INSERT IGNORE INTO `coupons` 
            (`code`, `title`, `discount_type`, `discount_value`, `min_spend`, `max_discount`, `expires_at`) 
            VALUES 
            ('HEMAT50', 'Diskon Spesial 50%', 'percent', 50.00, 50000.00, 25000.00, DATE_ADD(NOW(), INTERVAL 30 DAY)),
            ('GRATISONGKIR', 'Potongan Ongkir Rp 15.000', 'fixed', 15000.00, 30000.00, 15000.00, DATE_ADD(NOW(), INTERVAL 60 DAY)),
            ('TOKONESIAJUARA', 'Voucher Belanja Rp 100.000', 'fixed', 100000.00, 200000.00, 100000.00, DATE_ADD(NOW(), INTERVAL 90 DAY));
        ");
        echo "  OK Data 3 kupon promo aktif berhasil ditambahkan.\n";

        // 3. Tabel Wishlists (Daftar Keinginan Produk)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `wishlists` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id`    INT UNSIGNED NOT NULL,
                `product_id` INT UNSIGNED NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `user_product_unique` (`user_id`, `product_id`),
                FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
                FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "  OK Tabel 'wishlists' berhasil dibuat.\n";
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `wishlists`;");
        echo "  OK Tabel 'wishlists' dihapus.\n";

        $pdo->exec("DROP TABLE IF EXISTS `coupons`;");
        echo "  OK Tabel 'coupons' dihapus.\n";
    }
};

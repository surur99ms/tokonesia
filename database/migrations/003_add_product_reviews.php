<?php
// ============================================================
// Migration: 003_add_product_reviews
// Deskripsi: Menambahkan tabel ulasan produk (reviews) dan 
//            kolom diskon (discount_percent) di tabel products
// Dibuat   : 2026-10-02
// ============================================================

return new class {

    public function up(PDO $pdo): void
    {
        // 1. Tambah kolom diskon di tabel products jika belum ada
        // (Menggunakan try-catch karena MySQL tidak punya "ADD COLUMN IF NOT EXISTS")
        try {
            $pdo->exec("ALTER TABLE `products` ADD COLUMN `discount_percent` INT NOT NULL DEFAULT 0 AFTER `price`");
            echo "  OK Kolom 'discount_percent' ditambahkan ke tabel products.\n";
        } catch (PDOException $e) {
            // Abaikan error jika kolom ternyata sudah ada
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }

        // 2. Buat tabel ulasan (product_reviews)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `product_reviews` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `product_id` INT UNSIGNED NOT NULL,
                `user_id`    INT UNSIGNED NOT NULL,
                `rating`     TINYINT NOT NULL DEFAULT 5,
                `comment`    TEXT,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "  OK Tabel 'product_reviews' berhasil dibuat.\n";

        // 3. Masukkan data dummy ulasan untuk produk pertama
        // Kita butuh satu akun customer dummy (id=99)
        $pdo->exec("
            INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`, `role`) 
            VALUES (99, 'Budi Pelanggan', 'budi@tokonesia.com', '\$2y\$10\$ZoavjL0lurSb0iI5rDPwUe/RvHQsSVAXc4Vlwncx/8BtZYb2b9712', 'customer')
        ");
        
        $pdo->exec("
            INSERT IGNORE INTO `product_reviews` (`product_id`, `user_id`, `rating`, `comment`) 
            VALUES 
            (1, 99, 5, 'Kualitas suaranya luar biasa! Bassnya kerasa banget.'),
            (1, 99, 4, 'Bagus, tapi pengirimannya lumayan lama dari kurir.')
        ");
        
        // 4. Update produk agar memiliki diskon!
        $pdo->exec("UPDATE `products` SET `discount_percent` = 15 WHERE `id` = 1"); // Diskon 15% untuk Earbuds
        $pdo->exec("UPDATE `products` SET `discount_percent` = 20 WHERE `id` = 2"); // Diskon 20% untuk Smartwatch
        
        echo "  OK Data diskon dan contoh ulasan produk ditambahkan.\n";
    }

    public function down(PDO $pdo): void
    {
        // Hapus tabel reviews
        $pdo->exec("DROP TABLE IF EXISTS `product_reviews`;");
        echo "  OK Tabel 'product_reviews' dihapus.\n";

        // Hapus kolom diskon
        try {
            $pdo->exec("ALTER TABLE `products` DROP COLUMN `discount_percent`");
            echo "  OK Kolom 'discount_percent' dihapus dari tabel products.\n";
        } catch (PDOException $e) {
             if (strpos($e->getMessage(), 'check that column/key exists') === false) {
                 throw $e;
             }
        }
        
        // Hapus user dummy
        $pdo->exec("DELETE FROM `users` WHERE `id` = 99");
    }
};

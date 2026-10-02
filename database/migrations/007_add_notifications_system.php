<?php
// ============================================================
// Migration: 007_add_notifications_system
// Deskripsi: Sistem notifikasi pengguna + tabel poin reward
//            pelanggan setia (loyalty points).
// Dibuat   : 2026-10-03
// ============================================================

return new class {

    public function up(PDO $pdo): void
    {
        // 1. Tabel Notifikasi Pengguna
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `notifications` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id`    INT UNSIGNED NOT NULL,
                `type`       ENUM('order','promo','system','review') NOT NULL DEFAULT 'system',
                `title`      VARCHAR(150) NOT NULL,
                `message`    TEXT NOT NULL,
                `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
                `url`        VARCHAR(255) DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_user_unread` (`user_id`, `is_read`),
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "  OK Tabel 'notifications' berhasil dibuat.\n";

        // 2. Tabel Poin Reward Pelanggan Setia
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `loyalty_points` (
                `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id`     INT UNSIGNED NOT NULL,
                `order_id`    INT UNSIGNED DEFAULT NULL,
                `points`      INT NOT NULL DEFAULT 0 COMMENT 'Positif = masuk, negatif = dipakai',
                `description` VARCHAR(200) NOT NULL,
                `expired_at`  DATE DEFAULT NULL,
                `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "  OK Tabel 'loyalty_points' (Poin Reward) berhasil dibuat.\n";

        // 3. Tambah kolom total_points di tabel users
        try {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `loyalty_points_total` INT NOT NULL DEFAULT 0 AFTER `role`");
            echo "  OK Kolom 'loyalty_points_total' ditambahkan ke tabel users.\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) throw $e;
        }

        // 4. Buat notifikasi selamat datang untuk semua user yang sudah ada
        $pdo->exec("
            INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `url`)
            SELECT id, 'system',
                   'Selamat Datang di Tokonesia! 🎉',
                   'Terima kasih telah bergabung. Nikmati belanja dengan harga terbaik & diskon spesial setiap hari!',
                   '/products.php'
            FROM `users`
            WHERE id != 99
        ");
        echo "  OK Notifikasi selamat datang dikirim ke semua pengguna.\n";
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DELETE FROM `notifications`");
        $pdo->exec("DROP TABLE IF EXISTS `loyalty_points`;");
        $pdo->exec("DROP TABLE IF EXISTS `notifications`;");
        try {
            $pdo->exec("ALTER TABLE `users` DROP COLUMN `loyalty_points_total`");
        } catch (PDOException $e) {}
        echo "  OK Migration 007 di-rollback.\n";
    }
};

<?php
// ============================================================
// Migration: 004_auto_deploy_test
// Deskripsi: Menambahkan tabel `logs_deployment` sebagai bukti
//            bahwa fitur auto-migrate cPanel berjalan sukses.
// Dibuat   : 2026-10-02
// ============================================================

return new class {

    public function up(PDO $pdo): void
    {
        // Buat tabel log deployment
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `logs_deployment` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `pesan`      VARCHAR(255) NOT NULL,
                `waktu`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Catat momen kesuksesan ini!
        $pdo->exec("
            INSERT INTO `logs_deployment` (`pesan`) 
            VALUES ('Auto Deploy dan Auto Migration berhasil berjalan tanpa disentuh manusia!')
        ");
        
        echo "  OK Tabel 'logs_deployment' dibuat dan sukses dicatat.\n";
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `logs_deployment`;");
        echo "  OK Tabel 'logs_deployment' dihapus.\n";
    }
};

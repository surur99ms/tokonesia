<?php
// ============================================================
// Migration: 006_auto_migrate_verification
// Deskripsi: Pembuktian bahwa sistem Auto-Migrate berhasil 100%
//            berjalan sendiri secara otomatis saat web dibuka!
// Dibuat   : 2026-10-03
// ============================================================

return new class {

    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `auto_migrate_proof` (
                `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `status`      VARCHAR(100) NOT NULL,
                `keterangan`  TEXT,
                `waktu_aktif` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            INSERT INTO `auto_migrate_proof` (`status`, `keterangan`)
            VALUES ('SUKSES_100_PERSEN', 'Fitur Auto-Migrate terbukti berjalan otomatis sendiri tanpa menekan tombol deploy ataupun mengetik command apapun!')
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `auto_migrate_proof`;");
    }
};

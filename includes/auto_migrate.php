<?php
// ============================================================
// Tokonesia — Auto Database Migration Runner
// Berjalan otomatis setiap ada file migrasi baru di folder
// database/migrations/ tanpa perlu perintah manual / CLI.
// ============================================================

function checkAndRunAutoMigrations(): void {
    static $alreadyChecked = false;
    if ($alreadyChecked) {
        return;
    }
    $alreadyChecked = true;

    try {
        $migrationsDir = __DIR__ . '/../database/migrations/';
        if (!is_dir($migrationsDir)) {
            return;
        }

        // Ambil daftar file .php di folder migrations
        $files = [];
        $scanned = @scandir($migrationsDir);
        if (is_array($scanned)) {
            foreach ($scanned as $item) {
                if ($item !== '.' && $item !== '..' && pathinfo($item, PATHINFO_EXTENSION) === 'php') {
                    $files[] = $migrationsDir . $item;
                }
            }
        }
        if (empty($files)) {
            return;
        }
        sort($files);

        $pdo = getDB();

        // Pastikan tabel _migrations ada
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `_migrations` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `migration`  VARCHAR(255) NOT NULL UNIQUE,
                `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Cek migrasi yang sudah diterapkan
        $stmt = $pdo->query("SELECT `migration` FROM `_migrations`");
        $applied = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        // Cari migrasi yang belum dijalankan
        $pending = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (!in_array($name, $applied, true)) {
                $pending[$name] = $file;
            }
        }

        // Jika tidak ada migrasi baru, langsung selesai (0 overhead)
        if (empty($pending)) {
            return;
        }

        // Jika ada migrasi baru, eksekusi semuanya secara otomatis!
        $logFile = __DIR__ . '/../migration.log';
        $logHeader = "\n=== [AUTO-MIGRATE ON-LOAD: " . date('Y-m-d H:i:s') . "] ===\n";
        @file_put_contents($logFile, $logHeader, FILE_APPEND);

        foreach ($pending as $name => $file) {
            try {
                $migration = require $file;
                if (is_object($migration) && method_exists($migration, 'up')) {
                    $migration->up($pdo);
                }
                $recordStmt = $pdo->prepare("INSERT IGNORE INTO `_migrations` (`migration`) VALUES (?)");
                $recordStmt->execute([$name]);

                $logMsg = "[OK] Berhasil menerapkan migrasi: {$name}\n";
                @file_put_contents($logFile, $logMsg, FILE_APPEND);
            } catch (Throwable $e) {
                $errMsg = "[ERROR] Gagal pada migrasi {$name}: " . $e->getMessage() . "\n";
                @file_put_contents($logFile, $errMsg, FILE_APPEND);
                error_log($errMsg);
                break; // Hentikan migrasi berikutnya jika yang ini gagal
            }
        }

        @file_put_contents($logFile, "=== [SELESAI AUTO-MIGRATE] ===\n", FILE_APPEND);

    } catch (Throwable $e) {
        error_log("AutoMigrate general error: " . $e->getMessage());
    }
}

// Jalankan otomatis saat file ini dimuat
checkAndRunAutoMigrations();

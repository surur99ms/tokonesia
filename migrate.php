<?php
// ============================================================
//  Tokonesia — Migration Runner
//  Penggunaan:
//    php migrate.php migrate    → terapkan semua migrasi baru
//    php migrate.php rollback   → batalkan migrasi terakhir
//    php migrate.php status     → lihat status semua migrasi
//    php migrate.php fresh      → DROP semua tabel lalu migrate ulang
// ============================================================

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

define('MIGRATIONS_DIR', __DIR__ . '/database/migrations/');

// Mengambil kredensial dari file konfigurasi utama
// (Karena includes/config.php di-ignore Git, maka kredensial aman berbeda tiap server)
require_once __DIR__ . '/includes/config.php';

// ── 0. Helper Output ────────────────────────────────────────
function out(string $msg, string $color = 'white'): void {
    $colors = [
        'green'  => "\033[32m",
        'red'    => "\033[31m",
        'yellow' => "\033[33m",
        'cyan'   => "\033[36m",
        'white'  => "\033[0m",
        'bold'   => "\033[1m",
    ];
    $reset = "\033[0m";
    $c = $colors[$color] ?? $reset;
    // Jika dijalankan di browser tanpa terminal ANSI
    if (php_sapi_name() !== 'cli' && empty($_SERVER['TERM'])) {
        echo htmlspecialchars($msg) . "\n";
    } else {
        echo $c . $msg . $reset . "\n";
    }
}

// ── 1. Koneksi PDO ──────────────────────────────────────────
function getMigrationDB(): PDO {
    if (function_exists('getDB')) {
        return getDB();
    }
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

// ── 2. Tabel migrations (pencatat status) ───────────────────
function ensureMigrationsTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `_migrations` (
            `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `migration`  VARCHAR(255) NOT NULL UNIQUE,
            `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

// ── 3. Baca file migrasi yang tersedia ──────────────────────
function getMigrationFiles(): array {
    if (!is_dir(MIGRATIONS_DIR)) {
        out('Folder migrations/ tidak ditemukan: ' . MIGRATIONS_DIR, 'red');
        exit(1);
    }
    $files = [];
    $scanned = @scandir(MIGRATIONS_DIR);
    if (is_array($scanned)) {
        foreach ($scanned as $item) {
            if (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
                $files[] = MIGRATIONS_DIR . $item;
            }
        }
    } else {
        $glob = @glob(MIGRATIONS_DIR . '*.php');
        if (is_array($glob)) {
            $files = $glob;
        }
    }
    sort($files);
    return $files;
}

// ── 4. Baca migrasi yang sudah diterapkan ───────────────────
function getApplied(PDO $pdo): array {
    $rows = $pdo->query("SELECT `migration` FROM `_migrations` ORDER BY `id`")->fetchAll();
    return array_column($rows, 'migration');
}

// ── 5. Terapkan satu migrasi ────────────────────────────────
// Catatan: DDL (CREATE/DROP TABLE) di MySQL menyebabkan auto-commit
// sehingga transaksi eksplisit tidak bisa membungkus DDL.
// Runner ini mencatat migrasi ke _migrations SETELAH up() selesai.
function applyMigration(PDO $pdo, string $file): void {
    $name = basename($file, '.php');
    out("\n→ Menerapkan: {$name}", 'cyan');

    $migration = require $file;          // anonymous class
    try {
        $migration->up($pdo);
        // Catat migrasi ke tabel pencatat
        $stmt = $pdo->prepare("INSERT IGNORE INTO `_migrations` (`migration`) VALUES (?)");
        $stmt->execute([$name]);
        out("  OK {$name} berhasil.", 'green');
    } catch (Throwable $e) {
        out("  GAGAL: " . $e->getMessage(), 'red');
        exit(1);
    }
}

// ── 6. Batalkan satu migrasi ────────────────────────────────
function rollbackMigration(PDO $pdo, string $name): void {
    $file = MIGRATIONS_DIR . $name . '.php';
    if (!file_exists($file)) {
        out("File tidak ditemukan: {$file}", 'red');
        exit(1);
    }

    out("\n← Membatalkan: {$name}", 'yellow');
    $migration = require $file;
    try {
        $migration->down($pdo);
        $pdo->prepare("DELETE FROM `_migrations` WHERE `migration` = ?")->execute([$name]);
        out("  OK {$name} di-rollback.", 'green');
    } catch (Throwable $e) {
        out("  GAGAL rollback: " . $e->getMessage(), 'red');
        exit(1);
    }
}

// ════════════════════════════════════════════════════════════
//  MAIN
// ════════════════════════════════════════════════════════════
$command = $argv[1] ?? $_GET['action'] ?? 'migrate';

out("\n╔══════════════════════════════════╗", 'bold');
out("║   Tokonesia — Migration Runner   ║", 'bold');
out("╚══════════════════════════════════╝\n", 'bold');

try {
    $pdo = getMigrationDB();
    ensureMigrationsTable($pdo);

    switch ($command) {

        // ── migrate ─────────────────────────────────────────────
        case 'migrate':
            $files   = getMigrationFiles();
            $applied = getApplied($pdo);
            $pending = array_filter($files, fn($f) => !in_array(basename($f, '.php'), $applied));

            if (empty($pending)) {
                out("Tidak ada migrasi baru yang perlu dijalankan. Semua up-to-date.", 'green');
                break;
            }

            out("Menjalankan " . count($pending) . " migrasi baru...", 'cyan');
            foreach ($pending as $file) {
                applyMigration($pdo, $file);
            }
            out("\nSelesai! Semua migrasi berhasil diterapkan.\n", 'green');
            break;

        // ── rollback ─────────────────────────────────────────────
        case 'rollback':
            $applied = getApplied($pdo);
            if (empty($applied)) {
                out("Tidak ada migrasi yang bisa di-rollback.", 'yellow');
                break;
            }
            $last = end($applied);
            rollbackMigration($pdo, $last);
            out("\nRollback selesai.\n", 'green');
            break;

        // ── status ──────────────────────────────────────────────
        case 'status':
            $files   = getMigrationFiles();
            $applied = getApplied($pdo);

            out(sprintf("  %-40s  %s", 'Migration', 'Status'), 'bold');
            out("  " . str_repeat('-', 55));

            foreach ($files as $file) {
                $name   = basename($file, '.php');
                $done   = in_array($name, $applied);
                $status = $done ? '[✔ Diterapkan]' : '[✘ Belum     ]';
                $color  = $done ? 'green' : 'yellow';
                out(sprintf("  %-40s  %s", $name, $status), $color);
            }
            echo "\n";
            break;

        // ── fresh ───────────────────────────────────────────────
        case 'fresh':
            out("PERINGATAN: Semua tabel akan di-DROP!", 'red');
            out("Ketik 'yes' untuk melanjutkan: ", 'yellow');
            $confirm = trim(fgets(STDIN));
            if ($confirm !== 'yes') {
                out("Dibatalkan.", 'yellow');
                break;
            }

            // Rollback semua dari yang terbaru
            $applied = array_reverse(getApplied($pdo));
            foreach ($applied as $name) {
                rollbackMigration($pdo, $name);
            }

            // Lalu migrate ulang
            out("\nMenjalankan ulang semua migrasi...", 'cyan');
            $files = getMigrationFiles();
            foreach ($files as $file) {
                applyMigration($pdo, $file);
            }
            out("\nFresh migration selesai.\n", 'green');
            break;

        default:
            out("Command tidak dikenal: {$command}", 'red');
            out("Gunakan: migrate | rollback | status | fresh", 'yellow');
            exit(1);
    }
} catch (Throwable $e) {
    out("FATAL ERROR: " . $e->getMessage() . " (" . basename($e->getFile()) . ":" . $e->getLine() . ")", 'red');
    exit(1);
}

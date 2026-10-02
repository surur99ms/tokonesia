<?php
// ============================================================
// Migration: 002_seed_data
// Deskripsi : Data awal (seed) — admin, kategori, produk, settings
// Dibuat    : 2026-10-02
// ============================================================

return new class {

    public function up(PDO $pdo): void
    {
        // -- Admin default (password: admin123) --
        $stmt = $pdo->query("SELECT COUNT(*) FROM `users` WHERE `email` = 'admin@tokonesia.com'");
        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec("
                INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
                ('Administrator', 'admin@tokonesia.com',
                 '\$2y\$10\$ZoavjL0lurSb0iI5rDPwUe/RvHQsSVAXc4Vlwncx/8BtZYb2b9712', 'admin');
            ");
            echo "  OK User admin@tokonesia.com ditambahkan.\n";
        } else {
            echo "  -- User admin sudah ada, dilewati.\n";
        }

        // -- Kategori --
        $pdo->exec("
            INSERT IGNORE INTO `categories` (`name`, `slug`, `icon`) VALUES
            ('Elektronik',   'elektronik',   'bi-cpu'),
            ('Fashion',      'fashion',      'bi-bag'),
            ('Rumah Tangga', 'rumah-tangga', 'bi-house'),
            ('Olahraga',     'olahraga',     'bi-trophy'),
            ('Kecantikan',   'kecantikan',   'bi-stars');
        ");
        echo "  OK Kategori awal ditambahkan.\n";

        // -- Produk contoh --
        $pdo->exec("
            INSERT IGNORE INTO `products`
                (`category_id`, `name`, `slug`, `description`, `price`, `stock`, `sold_count`, `is_featured`)
            VALUES
            (1, 'Earbuds Wireless Pro X1', 'earbuds-wireless-pro-x1',
             'Earbuds wireless dengan noise cancelling aktif, baterai 30 jam, dan suara jernih.',
             299000, 45, 312, 1),
            (1, 'Smartwatch Sporty Z7', 'smartwatch-sporty-z7',
             'Jam tangan pintar dengan monitor detak jantung, GPS, dan tahan air IP68.',
             750000, 20, 187, 1),
            (2, 'Kaos Oversize Premium', 'kaos-oversize-premium',
             'Kaos bahan cotton combed 30s, oversize fit, tersedia dalam banyak warna.',
             89000, 200, 543, 1),
            (2, 'Celana Cargo Tactical', 'celana-cargo-tactical',
             'Celana cargo dengan banyak kantong, bahan ripstop anti-air, cocok outdoor.',
             175000, 80, 224, 1),
            (3, 'Set Peralatan Masak Granite', 'set-peralatan-masak-granite',
             'Set 5 pcs wajan & panci lapisan granite anti lengket, aman untuk induksi.',
             450000, 35, 98, 1),
            (4, 'Matras Yoga Anti-Slip 8mm', 'matras-yoga-anti-slip-8mm',
             'Matras yoga tebal 8mm dengan permukaan anti-slip, bahan TPE ramah lingkungan.',
             185000, 60, 275, 1);
        ");
        echo "  OK Produk contoh ditambahkan.\n";

        // -- Gambar produk (placeholder) --
        $pdo->exec("
            INSERT IGNORE INTO `product_images`
                (`product_id`, `image_path`, `is_primary`, `sort_order`)
            VALUES
            (1,'placeholder_1a.jpg',1,0),(1,'placeholder_1b.jpg',0,1),
            (2,'placeholder_2a.jpg',1,0),(2,'placeholder_2b.jpg',0,1),
            (3,'placeholder_3a.jpg',1,0),(3,'placeholder_3b.jpg',0,1),
            (4,'placeholder_4a.jpg',1,0),(4,'placeholder_4b.jpg',0,1),
            (5,'placeholder_5a.jpg',1,0),(5,'placeholder_5b.jpg',0,1),
            (6,'placeholder_6a.jpg',1,0),(6,'placeholder_6b.jpg',0,1);
        ");
        echo "  OK Gambar produk placeholder ditambahkan.\n";

        // -- Pengaturan toko --
        $pdo->exec("
            INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
            ('site_name',       'Tokonesia'),
            ('site_logo',       ''),
            ('hero_title',      'Belanja Lebih Mudah, Lebih Hemat!'),
            ('hero_subtitle',   'Temukan ribuan produk pilihan dengan harga terbaik dan pengiriman cepat ke seluruh Indonesia.'),
            ('whatsapp_number', '6281234567890'),
            ('primary_color',   '#6C63FF'),
            ('currency_symbol', 'Rp');
        ");
        echo "  OK Settings toko ditambahkan.\n";
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DELETE FROM `product_images`;");
        $pdo->exec("DELETE FROM `products`;");
        $pdo->exec("DELETE FROM `categories`;");
        $pdo->exec("DELETE FROM `settings`;");
        $pdo->exec("DELETE FROM `users` WHERE `role` = 'admin';");
        echo "  OK Data seed dihapus.\n";
    }
};

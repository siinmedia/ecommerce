-- Seed demo data. Passwords: admin / developer / user (see README, change after import).
USE ecommerce_mvp;

INSERT INTO users (name, email, phone, password_hash, role, status) VALUES
('Super Admin', 'admin@toko.local', '081200000001', '$2y$12$8fndgrTpdejnCdb4wEtQyuj0g2BFGGDc90VL.rY1DdBcQVG1q3a9K', 'admin', 'active'),
('Admin Demo', 'admin.demo@toko.local', '081200000002', '$2y$12$8fndgrTpdejnCdb4wEtQyuj0g2BFGGDc90VL.rY1DdBcQVG1q3a9K', 'admin', 'active'),
('Developer', 'dev@toko.local', '081200000003', '$2y$12$so3QjcYt852nUqjceuRba.dZdb/xIRXn2CQ1oA/ziHWJFL75I3mPe', 'developer', 'active'),
('User Demo', 'user@toko.local', '081200000004', '$2y$12$SYNk8jfMNf0ez3asWfqXoeyz2jxOd4R4J.DBWEQzGhE3lwzESDG4e', 'user', 'active');

INSERT INTO bank_accounts (bank_name, account_number, account_name, is_active, sort_order) VALUES
('BCA', '8210101001', 'PT Toko MVP', 1, 1),
('Mandiri', '8900010101001', 'PT Toko MVP', 1, 2);

INSERT INTO categories (name, slug, parent_id) VALUES
('Elektronik', 'elektronik', NULL),
('Fashion', 'fashion', NULL),
('Rumah Tangga', 'rumah-tangga', NULL),
('Aksesoris', 'aksesoris', NULL);

INSERT INTO products (category_id, name, slug, sku, description, price, compare_price, weight_gram, status) VALUES
(1, 'Kabel Data USB-C 60W', 'kabel-data-usb-c-60w', 'ELC-001', 'Kabel data USB-C fast charging 60W, panjang 1m, nylon braided.', 49000, 79000, 100, 'published'),
(1, 'Charger GaN 65W Dual Port', 'charger-gan-65w-dual-port', 'ELC-002', 'Charger GaN 65W dengan port USB-C + USB-A, proteksi overheat.', 249000, 329000, 300, 'published'),
(1, 'Mouse Wireless Silent', 'mouse-wireless-silent', 'ELC-003', 'Mouse wireless 2.4GHz silent click, baterai tahan 12 bulan.', 129000, NULL, 200, 'published'),
(2, 'Kaos Polos Cotton Combed 30s', 'kaos-polos-cotton-combed-30s', 'FSH-001', 'Kaos polos cotton combed 30s, adem dan tidak menerawang.', 59000, 89000, 200, 'published'),
(2, 'Hoodie Fleece Premium', 'hoodie-fleece-premium', 'FSH-002', 'Hoodie fleece cotton premium 330gsm dengan saku kanguru.', 189000, 259000, 500, 'published'),
(2, 'Topi Baseball Snapback', 'topi-baseball-snapback', 'FSH-003', 'Topi snapback adjustable, bahan drill premium.', 79000, NULL, 150, 'published'),
(3, 'Botol Minum Stainless 750ml', 'botol-minum-stainless-750ml', 'RT-001', 'Botol stainless 304 double wall, tahan panas 12 jam.', 99000, 149000, 400, 'published'),
(3, 'Lampu Meja LED Lipat', 'lampu-meja-led-lipat', 'RT-002', 'Lampu meja LED 3 mode cahaya, USB rechargeable.', 119000, NULL, 600, 'published'),
(4, 'Tas Selempang Anti Air', 'tas-selempang-anti-air', 'AKS-001', 'Tas selempang waterproof dengan port USB.', 149000, 199000, 400, 'published'),
(4, 'Dompet Kulit Pria RFID', 'dompet-kulit-pria-rfid', 'AKS-002', 'Dompet kulit sintetis premium dengan proteksi RFID.', 89000, 129000, 150, 'published');

INSERT INTO product_variants (product_id, name, sku, price, stock, weight_gram, status) VALUES
(1, 'Hitam 1m', 'ELC-001-HITAM', 49000, 120, 100, 'active'),
(1, 'Putih 1m', 'ELC-001-PUTIH', 49000, 80, 100, 'active'),
(2, 'Default', 'ELC-002-STD', 249000, 45, 300, 'active'),
(3, 'Hitam', 'ELC-003-HITAM', 129000, 60, 200, 'active'),
(3, 'Putih', 'ELC-003-PUTIH', 129000, 40, 200, 'active'),
(4, 'Hitam S', 'FSH-001-HITAM-S', 59000, 100, 200, 'active'),
(4, 'Hitam M', 'FSH-001-HITAM-M', 59000, 100, 200, 'active'),
(4, 'Hitam L', 'FSH-001-HITAM-L', 59000, 70, 200, 'active'),
(4, 'Putih M', 'FSH-001-PUTIH-M', 59000, 90, 200, 'active'),
(5, 'Abu M', 'FSH-002-ABU-M', 189000, 30, 500, 'active'),
(5, 'Hitam L', 'FSH-002-HITAM-L', 189000, 25, 500, 'active'),
(6, 'Hitam', 'FSH-003-HITAM', 79000, 50, 150, 'active'),
(7, 'Silver', 'RT-001-SILVER', 99000, 70, 400, 'active'),
(7, 'Hitam', 'RT-001-HITAM', 99000, 65, 400, 'active'),
(8, 'Putih', 'RT-002-PUTIH', 119000, 35, 600, 'active'),
(9, 'Hitam', 'AKS-001-HITAM', 149000, 40, 400, 'active'),
(10, 'Coklat', 'AKS-002-COKLAT', 89000, 55, 150, 'active'),
(10, 'Hitam', 'AKS-002-HITAM', 89000, 55, 150, 'active');

INSERT INTO promotions (code, type, value, max_discount, min_spend, quota, used_count, per_user_limit, starts_at, ends_at, is_active) VALUES
('HEMAT10', 'percent', 10, 25000, 100000, 500, 0, 1, '2025-01-01 00:00:00', '2030-12-31 23:59:59', 1),
('POTONG15RB', 'nominal', 15000, NULL, 150000, 200, 0, 1, '2025-01-01 00:00:00', '2030-12-31 23:59:59', 1);

-- Sample address for demo user (id 4)
INSERT INTO addresses (user_id, label, recipient, phone, address_line, city_label, destination_id, postal_code, is_default) VALUES
(4, 'Rumah', 'User Demo', '081200000004', 'Jl. Merdeka No. 10 RT 01 RW 02', 'Bandung, Jawa Barat', '23', '40111', 1);

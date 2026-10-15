-- Seed data kemitraan Mahaniagaartha (DATA CONTOH — harga placeholder, admin wajib ganti harga asli).
-- Passwords: admin / developer / user (see README, change after import).
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
('Mie', 'mie', NULL),
('Kopi', 'kopi', NULL),
('Teh', 'teh', NULL),
('Camilan', 'camilan', NULL),
('Ayam', 'ayam', NULL),
('Roti & Dessert', 'roti-dessert', NULL),
('Minuman', 'minuman', NULL);

-- 14 paket kemitraan (harga CONTOH, bukan harga resmi).
INSERT INTO products (category_id, name, slug, sku, description, price, compare_price, weight_gram, status) VALUES
(1, 'Paket Kemitraan You Need Mie', 'paket-kemitraan-you-need-mie', 'YNM-PKT', 'Paket kemitraan berisi booth, peralatan masak dan saji mie, bahan awal, serta training operasional standar gerai.', 9500000, NULL, 25000, 'published'),
(3, 'Paket Kemitraan Mybestea', 'paket-kemitraan-mybestea', 'MTB-PKT', 'Paket kemitraan berisi booth, peralatan seduh dan saji teh, bahan awal, serta training operasional standar gerai.', 9800000, NULL, 25000, 'published'),
(6, 'Paket Kemitraan Mentoast', 'paket-kemitraan-mentoast', 'MTT-PKT', 'Paket kemitraan berisi booth, peralatan panggang dan saji roti bakar, bahan awal, serta training operasional standar gerai.', 10500000, NULL, 25000, 'published'),
(3, 'Paket Kemitraan Esteh Ibukota', 'paket-kemitraan-esteh-ibukota', 'EIB-PKT', 'Paket kemitraan berisi booth, peralatan seduh dan saji es teh, bahan awal, serta training operasional standar gerai.', 9900000, NULL, 25000, 'published'),
(4, 'Paket Kemitraan Kentang Gantenk', 'paket-kemitraan-kentang-gantenk', 'KGT-PKT', 'Paket kemitraan berisi booth, peralatan goreng dan saji kentang, bahan awal, serta training operasional standar gerai.', 10800000, NULL, 25000, 'published'),
(1, 'Paket Kemitraan K-Mie', 'paket-kemitraan-k-mie', 'KMI-PKT', 'Paket kemitraan berisi booth, peralatan masak dan saji mie korea, bahan awal, serta training operasional standar gerai.', 11200000, NULL, 25000, 'published'),
(2, 'Paket Kemitraan Benice Coffee', 'paket-kemitraan-benice-coffee', 'BNC-PKT', 'Paket kemitraan berisi booth, peralatan seduh kopi dan saji, bahan awal, serta training operasional standar gerai.', 12500000, NULL, 25000, 'published'),
(6, 'Paket Kemitraan Breadcoin', 'paket-kemitraan-breadcoin', 'BRC-PKT', 'Paket kemitraan berisi booth, peralatan panggang dan saji roti koin, bahan awal, serta training operasional standar gerai.', 11500000, NULL, 25000, 'published'),
(5, 'Paket Kemitraan Chickuruyuk', 'paket-kemitraan-chickuruyuk', 'CKU-PKT', 'Paket kemitraan berisi booth, peralatan masak dan saji ayam goreng, bahan awal, serta training operasional standar gerai.', 13000000, NULL, 25000, 'published'),
(7, 'Paket Kemitraan El Choco', 'paket-kemitraan-el-choco', 'ELC-PKT', 'Paket kemitraan berisi booth, peralatan racik dan saji minuman coklat, bahan awal, serta training operasional standar gerai.', 10200000, NULL, 25000, 'published'),
(6, 'Paket Kemitraan Merlumer', 'paket-kemitraan-merlumer', 'MLM-PKT', 'Paket kemitraan berisi booth, peralatan saji dessert lumer, bahan awal, serta training operasional standar gerai.', 11800000, NULL, 25000, 'published'),
(2, 'Paket Kemitraan Nicepresso', 'paket-kemitraan-nicepresso', 'NCP-PKT', 'Paket kemitraan berisi booth, peralatan seduh espresso dan saji, bahan awal, serta training operasional standar gerai.', 14000000, NULL, 25000, 'published'),
(4, 'Paket Kemitraan Nyemilin', 'paket-kemitraan-nyemilin', 'NYM-PKT', 'Paket kemitraan berisi booth, peralatan saji camilan, bahan awal, serta training operasional standar gerai.', 9600000, NULL, 25000, 'published'),
(3, 'Paket Kemitraan Xuancha', 'paket-kemitraan-xuancha', 'XNC-PKT', 'Paket kemitraan berisi booth, peralatan seduh dan saji teh, bahan awal, serta training operasional standar gerai.', 12000000, NULL, 25000, 'published');

-- 28 varian: Paket Reguler (stok 20) + Paket Lengkap (stok 10). Harga CONTOH.
INSERT INTO product_variants (product_id, name, sku, price, stock, weight_gram, status) VALUES
(1, 'Paket Reguler', 'YNM-REG', 9500000, 20, 25000, 'active'),
(1, 'Paket Lengkap', 'YNM-LKP', 14500000, 10, 40000, 'active'),
(2, 'Paket Reguler', 'MTB-REG', 9800000, 20, 25000, 'active'),
(2, 'Paket Lengkap', 'MTB-LKP', 15800000, 10, 40000, 'active'),
(3, 'Paket Reguler', 'MTT-REG', 10500000, 20, 25000, 'active'),
(3, 'Paket Lengkap', 'MTT-LKP', 17900000, 10, 40000, 'active'),
(4, 'Paket Reguler', 'EIB-REG', 9900000, 20, 25000, 'active'),
(4, 'Paket Lengkap', 'EIB-LKP', 16900000, 10, 40000, 'active'),
(5, 'Paket Reguler', 'KGT-REG', 10800000, 20, 25000, 'active'),
(5, 'Paket Lengkap', 'KGT-LKP', 18500000, 10, 40000, 'active'),
(6, 'Paket Reguler', 'KMI-REG', 11200000, 20, 25000, 'active'),
(6, 'Paket Lengkap', 'KMI-LKP', 19800000, 10, 40000, 'active'),
(7, 'Paket Reguler', 'BNC-REG', 12500000, 20, 25000, 'active'),
(7, 'Paket Lengkap', 'BNC-LKP', 22500000, 10, 40000, 'active'),
(8, 'Paket Reguler', 'BRC-REG', 11500000, 20, 25000, 'active'),
(8, 'Paket Lengkap', 'BRC-LKP', 18900000, 10, 40000, 'active'),
(9, 'Paket Reguler', 'CKU-REG', 13000000, 20, 25000, 'active'),
(9, 'Paket Lengkap', 'CKU-LKP', 24000000, 10, 40000, 'active'),
(10, 'Paket Reguler', 'ELC-REG', 10200000, 20, 25000, 'active'),
(10, 'Paket Lengkap', 'ELC-LKP', 16500000, 10, 40000, 'active'),
(11, 'Paket Reguler', 'MLM-REG', 11800000, 20, 25000, 'active'),
(11, 'Paket Lengkap', 'MLM-LKP', 19900000, 10, 40000, 'active'),
(12, 'Paket Reguler', 'NCP-REG', 14000000, 20, 25000, 'active'),
(12, 'Paket Lengkap', 'NCP-LKP', 25000000, 10, 40000, 'active'),
(13, 'Paket Reguler', 'NYM-REG', 9600000, 20, 25000, 'active'),
(13, 'Paket Lengkap', 'NYM-LKP', 15200000, 10, 40000, 'active'),
(14, 'Paket Reguler', 'XNC-REG', 12000000, 20, 25000, 'active'),
(14, 'Paket Lengkap', 'XNC-LKP', 21000000, 10, 40000, 'active');

-- Promo generik (DATA CONTOH, kuota 100).
INSERT INTO promotions (code, type, value, max_discount, min_spend, quota, used_count, per_user_limit, starts_at, ends_at, is_active) VALUES
('WELCOME10', 'percent', 10, 50000, 100000, 100, 0, 1, '2025-01-01 00:00:00', '2030-12-31 23:59:59', 1),
('GRATISONGKIR', 'nominal', 20000, NULL, 500000, 100, 0, 1, '2025-01-01 00:00:00', '2030-12-31 23:59:59', 1);

-- Sample address for demo user (id 4)
INSERT INTO addresses (user_id, label, recipient, phone, address_line, city_label, destination_id, postal_code, is_default) VALUES
(4, 'Rumah', 'User Demo', '081200000004', 'Jl. Merdeka No. 10 RT 01 RW 02', 'Bandung, Jawa Barat', '23', '40111', 1);

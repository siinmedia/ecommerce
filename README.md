# E-Commerce Single-Vendor MVP

PHP 8 Native + MySQL 8 + Tailwind CDN + Vanilla JS. Transfer manual + upload bukti + verifikasi Admin. RajaOngkir mock by default.

## Struktur

```
ecommerce/
  public/          <- docroot lokal; isi-nya = isi public_html di cPanel
    index.php      <- front controller + router
    .htaccess
    assets/css/app.css, assets/js/app.js
  app/
    Config/config.example.php  <- copy ke config.php (tidak di-commit)
    Core/ Controllers/ Models/ Services/ Views/ Cron/
  database/schema.sql, seed.sql
  storage/payment-proofs/      <- di luar public (privat)
  docs/DEPLOY-CPANEL.md
```

## Jalan lokal

```bash
cd ecommerce
cp app/Config/config.example.php app/Config/config.php
# edit config.php: db.* sesuai MySQL lokal

mysql -u <user> -p -e "SOURCE database/schema.sql; SOURCE database/seed.sql;"

php -S localhost:8099 -t public
# buka http://localhost:8099
```

## Akun demo (ganti setelah import!)

| Role | Email | Password |
|---|---|---|
| Admin | admin@toko.local | Admin123! |
| Admin demo | admin.demo@toko.local | Admin123! |
| Developer | dev@toko.local | Dev12345! |
| User | user@toko.local | User12345! |

## Flow test

1. Guest: buka `/products`, tambah ke cart, klik Checkout > redirect ke `/login`.
2. Login sebagai user@toko.local > cart tetap ada (merge) > `/checkout`.
3. Pilih alamat (demo user sudah punya 1 alamat Bandung) atau tambah baru via destination search (mock).
4. Hitung ongkir (mock jne/pos/tiki) > pilih layanan > pilih rekening > Buat Pesanan.
5. `/orders/{code}/payment`: salin nominal/norek, upload bukti (jpg/png/webp/pdf <= 3MB).
6. Login admin: `/admin/payments` > buka order > Setujui/Tolak (lock anti double-approve).
7. Admin: PAID > PROCESSING > input resi > SHIPPED > DELIVERED > COMPLETED.
8. Expire: `php app/Cron/expire_orders.php`.
9. Dev: `/dev/health`, `/dev/logs`, `/dev/jobs` (login dev@toko.local).

## Status order

PENDING_PAYMENT > PAYMENT_REVIEW > PAID > PROCESSING > SHIPPED > DELIVERED > COMPLETED.
Cabang: PAYMENT_REJECTED (upload ulang), EXPIRED (auto cron), CANCELLED, RETURN_*/REFUND_* (manual admin).

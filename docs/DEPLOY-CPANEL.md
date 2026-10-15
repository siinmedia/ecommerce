# Deploy ke Shared Hosting cPanel (Apache + public_html)

Arsitektur: `public/` = isi `public_html`. Semua file lain (`app/`, `storage/`) di luar `public_html`.

## 1. Siapkan database

1. cPanel > MySQL Databases > buat database, misal `user_ecom`.
2. Buat user MySQL + password kuat, attach ke database dengan ALL PRIVILEGES.
3. cPanel > phpMyAdmin > pilih database > Import > `database/schema.sql` > Go.
4. Import `database/seed.sql` (data demo), lalu **hapus/ganti password akun demo** via SQL:
   ```sql
   UPDATE users SET status = 'suspended' WHERE email LIKE '%@toko.local';
   -- lalu buat admin asli via register + UPDATE users SET role = 'admin' WHERE email = '...';
   ```

## 2. Upload file

Struktur di hosting:

```
/home/<user>/
  ecommerce/            <- upload SELURUH repo ke sini (atau nama bebas)
    app/
    database/
    docs/
    storage/            <- chmod 755, pastikan writable oleh PHP
    README.md
  public_html/
    index.php           <- isi dari public/index.php
    .htaccess           <- isi dari public/.htaccess
    assets/             <- isi dari public/assets/
```

Catatan: `public/index.php` menghitung `$appRoot = dirname(__DIR__) . '/app'` — artinya ia mengasumsikan
struktur `ecommerce/public/index.php` + `ecommerce/app/`. Karena di cPanel `index.php` pindah ke `public_html`
(sibling dari `ecommerce/`), edit 1 baris di `public_html/index.php`:

```php
$appRoot = '/home/<user>/ecommerce/app';
```

Ganti `<user>` dengan username cPanel. Alternatif: symlink bila diperbolehkan
(`ln -s /home/<user>/ecommerce/public/index.php /home/<user>/public_html/index.php` — jarang diizinkan di shared hosting).

## 3. Konfigurasi

1. Copy `app/Config/config.example.php` menjadi `app/Config/config.php` (jangan lewat git).
2. Isi:
   - `app.site_url` = `https://domainanda.com`
   - `db.*` = kredensial dari langkah 1
   - `storage.payment_proofs` = `/home/<user>/ecommerce/storage/payment-proofs`
   - `rajaongkir.mode` = `mock` dulu; isi `api_key` + `mode=real` setelah punya key.
3. Pastikan `config.php` tidak ikut ter-upload ke repo publik (ada di `.gitignore`).

## 4. Cron (auto-expire order)

cPanel > Cron Jobs > tambah (tiap 15 menit):

```
php /home/<user>/ecommerce/app/Cron/expire_orders.php >> /home/<user>/ecommerce/storage/cron.log 2>&1
```

## 5. Permission checklist

- Folder: `755` (`storage/` harus writable oleh user PHP — biasanya 755 cukup karena owner sama).
- File: `644`. Jangan pernah `777`.
- Test: buka `https://domainanda.com/dev/health` setelah login developer untuk verifikasi DB + storage.

## 6. RajaOngkir real

1. Daftar paket di rajaongkir.com, dapatkan API key.
2. Isi `rajaongkir.api_key` di `config.php` (hanya di server, tidak di-commit).
3. Ubah `rajaongkir.mode` = `real`, sesuaikan `base_url` dengan paket (starter/pro/basic).
4. Test checkout dengan alamat asli; pantau `/dev/logs` untuk request/response + correlation ID.

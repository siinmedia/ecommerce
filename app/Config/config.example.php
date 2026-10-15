<?php
// config.example.php - COPY to config.php and fill real values. Never commit config.php.
return [
    'app' => [
        'name' => 'TokoMVP',
        'site_url' => 'http://localhost:8099',
        'timezone' => 'Asia/Jakarta',
        'env' => 'local', // local | production
        'debug' => false, // true = tampilkan error detail (hanya lokal); false = display_errors Off + 500 generik
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'ecommerce_mvp',
        'user' => 'ecom_user',
        'pass' => 'CHANGE_ME',
        'charset' => 'utf8mb4',
    ],
    // storage/payment-proofs MUST live outside public_html on shared hosting.
    // Local: <project>/storage ; cPanel: /home/<user>/storage-ecommerce
    'storage' => [
        'payment_proofs' => dirname(__DIR__, 2) . '/storage/payment-proofs',
    ],
    'rajaongkir' => [
        'mode' => 'mock', // mock | real
        'api_key' => '',  // fill only in config.php (never commit). Real mode reads this.
        'base_url' => 'https://api.rajaongkir.com/starter',
        'origin_city_id' => '152', // Jakarta Pusat (mock default)
        'timeout' => 10,
    ],
    'payment' => [
        'expiry_hours' => 24,
        'max_proof_mb' => 3,
        'allowed_proof_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
    ],
    'security' => [
        'session_name' => 'ECOMSESSID',
    ],
];

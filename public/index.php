<?php
declare(strict_types=1);

// Front controller. All requests route through here via .htaccess.

$appRoot = dirname(__DIR__) . '/app';
$configFile = $appRoot . '/Config/config.php';
if (!file_exists($configFile)) {
    $configFile = $appRoot . '/Config/config.example.php';
}
$config = require $configFile;

date_default_timezone_set($config['app']['timezone'] ?? 'Asia/Jakarta');

// Simple PSR-4 autoloader for App\
spl_autoload_register(function (string $class) use ($appRoot): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $rel = str_replace('\\', '/', substr($class, 4)) . '.php';
    $file = $appRoot . '/' . $rel;
    if (is_file($file)) {
        require $file;
    }
});

use App\Controllers\AccountController;
use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\DevController;
use App\Controllers\StoreController;
use App\Core\Database;
use App\Core\View;

session_name($config['security']['session_name'] ?? 'ECOMSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();

View::init(dirname(__DIR__) . '/app/Views');

$router = new App\Core\Router();

// Storefront
$router->get('/', [StoreController::class, 'home']);
$router->get('/products', [StoreController::class, 'catalog']);
$router->get('/search', [StoreController::class, 'search']);
$router->get('/categories/{slug}', [StoreController::class, 'category']);
$router->get('/products/{slug}', [StoreController::class, 'product']);
$router->get('/sitemap.xml', [StoreController::class, 'sitemap']);
$router->get('/robots.txt', [StoreController::class, 'robots']);

// Cart
$router->get('/cart', [CartController::class, 'show']);
$router->post('/cart/add', [CartController::class, 'add']);
$router->post('/cart/update', [CartController::class, 'update']);
$router->post('/cart/clear', [CartController::class, 'clear']);

// Auth
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

// Checkout + payment
$router->get('/checkout', [CheckoutController::class, 'show']);
$router->post('/checkout/address', [CheckoutController::class, 'saveAddress']);
$router->post('/checkout/place', [CheckoutController::class, 'place']);
$router->get('/api/destinations', [CheckoutController::class, 'destSearch']);
$router->get('/api/shipping-cost', [CheckoutController::class, 'shipCost']);
$router->post('/api/promo-check', [CheckoutController::class, 'promoCheck']);
$router->get('/orders/{code}/payment', [CheckoutController::class, 'paymentPage']);
$router->post('/orders/{code}/payment/proof', [CheckoutController::class, 'uploadProof']);
$router->get('/proof/{id}', [CheckoutController::class, 'serveProof']);

// Account
$router->get('/account/orders', [AccountController::class, 'orders']);
$router->get('/account/orders/{code}', [AccountController::class, 'orderDetail']);
$router->post('/account/orders/{code}/reorder', [AccountController::class, 'reorder']);
$router->post('/account/orders/{code}/cancel', [AccountController::class, 'cancel']);
$router->get('/account/orders/{code}/track', [AccountController::class, 'track']);
$router->get('/account/addresses', [AccountController::class, 'addresses']);
$router->get('/account/profile', [AccountController::class, 'profile']);
$router->post('/account/profile', [AccountController::class, 'updateProfile']);

// Admin
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/products', [AdminController::class, 'products']);
$router->get('/admin/products/new', [AdminController::class, 'productForm']);
$router->get('/admin/products/{id}/edit', [AdminController::class, 'productForm']);
$router->post('/admin/products/save', [AdminController::class, 'productSave']);
$router->post('/admin/stock/adjust', [AdminController::class, 'adjustStock']);
$router->get('/admin/orders', [AdminController::class, 'orders']);
$router->get('/admin/orders/{code}', [AdminController::class, 'orderDetail']);
$router->post('/admin/orders/{code}/transition', [AdminController::class, 'transition']);
$router->get('/admin/payments', [AdminController::class, 'payments']);
$router->post('/admin/orders/{code}/verify', [AdminController::class, 'verifyPayment']);
$router->get('/admin/banks', [AdminController::class, 'banks']);
$router->post('/admin/banks/save', [AdminController::class, 'bankSave']);
$router->get('/admin/audit-logs', [AdminController::class, 'auditLogs']);

// Developer console
$router->get('/dev/health', [DevController::class, 'health']);
$router->get('/dev/logs', [DevController::class, 'logs']);
$router->get('/dev/jobs', [DevController::class, 'jobs']);
$router->post('/dev/jobs/{id}/retry', [DevController::class, 'retryJob']);
$router->get('/dev/audit-logs', [DevController::class, 'auditLogs']);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (Throwable $e) {
    error_log('[ECOM] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (($config['app']['env'] ?? 'local') === 'local') {
        http_response_code(500);
        echo '<h1>Error</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    } else {
        http_response_code(500);
        echo View::render('errors/500', ['title' => 'Terjadi kesalahan']);
    }
}

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

$debug = !empty($config['app']['debug']);
// Production default: never leak errors to the browser.
if (!$debug) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}
error_reporting(E_ALL);

// Global error/exception handling: log to storage/logs, show generic 500.
$logDir = dirname(__DIR__) . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$errorLog = $logDir . '/php-errors.log';
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use ($errorLog, $debug): bool {
    if (!(error_reporting() & $errno)) {
        return false; // dihormati operator @
    }
    @file_put_contents(
        $errorLog,
        date('Y-m-d H:i:s') . " [{$errno}] {$errstr} @ {$errfile}:{$errline}\n",
        FILE_APPEND | LOCK_EX
    );
    if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    return true;
});
set_exception_handler(function (Throwable $e) use ($errorLog, $debug, $config): void {
    @file_put_contents(
        $errorLog,
        date('Y-m-d H:i:s') . ' [EX] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n",
        FILE_APPEND | LOCK_EX
    );
    if (!headers_sent()) {
        http_response_code(500);
    }
    if ($debug) {
        echo '<h1>Error</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    } else {
        try {
            echo \App\Core\View::render('errors/500', ['title' => 'Terjadi kesalahan']);
        } catch (Throwable) {
            echo '<h1>Terjadi kesalahan</h1>';
        }
    }
    exit;
});

// Security headers (dikirim dari PHP agar tetap aktif tanpa mod_headers / php -S).
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; frame-ancestors 'self'; form-action 'self'");

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
use App\Core\Auth;
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

// Penegakan idle timeout (30 mnt), absolute lifetime (12 jam), rotasi ID, session_version.
Auth::tick();

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
$router->get('/login/2fa', [AuthController::class, 'showTwoFa']);
$router->post('/login/2fa', [AuthController::class, 'verifyTwoFa']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'showForgot']);
$router->post('/forgot-password', [AuthController::class, 'forgot']);
$router->get('/reset-password', [AuthController::class, 'showReset']);
$router->post('/reset-password', [AuthController::class, 'reset']);

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
$router->get('/account/2fa', [AccountController::class, 'twoFa']);
$router->post('/account/2fa/enable', [AccountController::class, 'enableTwoFa']);
$router->post('/account/2fa/disable', [AccountController::class, 'disableTwoFa']);

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
    @file_put_contents(
        $errorLog,
        date('Y-m-d H:i:s') . ' [DISPATCH] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n",
        FILE_APPEND | LOCK_EX
    );
    if (!headers_sent()) {
        http_response_code(500);
    }
    if ($debug) {
        echo '<h1>Error</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    } else {
        echo View::render('errors/500', ['title' => 'Terjadi kesalahan']);
    }
}

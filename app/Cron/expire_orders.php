<?php
declare(strict_types=1);

// Cron: expire unpaid orders past expires_at. Run every 10-15 min:
//   php /path/to/app/Cron/expire_orders.php
// cPanel cron example:
//   php /home/<user>/ecommerce/app/Cron/expire_orders.php

$appRoot = dirname(__DIR__);
$configFile = $appRoot . '/Config/config.php';
if (!file_exists($configFile)) {
    $configFile = $appRoot . '/Config/config.example.php';
}
$config = require $configFile;

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

use App\Core\Database;
use App\Models\Order;
use App\Models\Product;

Database::reset();
$cfg = require Database::configPath();
$pdo = Database::pdo($cfg);

$st = $pdo->prepare(
    "SELECT id, order_code, status FROM orders
     WHERE status IN ('PENDING_PAYMENT','PAYMENT_REVIEW','PAYMENT_REJECTED') AND expires_at < NOW() LIMIT 100"
);
$st->execute();
$rows = $st->fetchAll();
$expired = 0;
foreach ($rows as $row) {
    $pdo->beginTransaction();
    try {
        // Re-check inside lock
        $lock = $pdo->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
        $lock->execute([$row['id']]);
        $cur = $lock->fetchColumn();
        if (!in_array($cur, ['PENDING_PAYMENT', 'PAYMENT_REVIEW', 'PAYMENT_REJECTED'], true)) {
            $pdo->rollBack();
            continue;
        }
        $up = $pdo->prepare('UPDATE orders SET status = "EXPIRED", updated_at = NOW() WHERE id = ? AND status = ?');
        $up->execute([$row['id'], $cur]);
        Order::recordHistory($pdo, (int)$row['id'], $cur, 'EXPIRED', null, 'Auto-expire by cron');
        foreach (Order::items((int)$row['id']) as $it) {
            if ($it['variant_id']) {
                Product::incrementStock($pdo, (int)$it['variant_id'], (int)$it['qty']);
            }
        }
        $pdo->commit();
        $expired++;
        echo "Expired {$row['order_code']}\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        fwrite(STDERR, "Fail {$row['order_code']}: {$e->getMessage()}\n");
    }
}
echo "Done. Expired: {$expired}\n";

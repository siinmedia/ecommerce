<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Shipment
{
    public static function forOrder(int $orderId): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM shipments WHERE order_id = ? LIMIT 1');
        $st->execute([$orderId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function trackingEvents(int $shipmentId): array
    {
        $st = Database::pdo()->prepare('SELECT * FROM tracking_events WHERE shipment_id = ? ORDER BY event_time DESC');
        $st->execute([$shipmentId]);
        return $st->fetchAll();
    }
}

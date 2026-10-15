<?php
declare(strict_types=1);

namespace App\Services;

/**
 * MockRajaOngkir: deterministic dummy data for MVP development.
 * Swap to RealRajaOngkir by setting rajaongkir.mode=real + api key in config.php.
 */
class MockRajaOngkir implements RajaOngkirInterface
{
    private const CITIES = [
        ['id' => '152', 'label' => 'Jakarta Pusat, DKI Jakarta', 'province' => 'DKI Jakarta', 'postal' => '10110'],
        ['id' => '151', 'label' => 'Jakarta Timur, DKI Jakarta', 'province' => 'DKI Jakarta', 'postal' => '13330'],
        ['id' => '153', 'label' => 'Jakarta Barat, DKI Jakarta', 'province' => 'DKI Jakarta', 'postal' => '11220'],
        ['id' => '154', 'label' => 'Jakarta Selatan, DKI Jakarta', 'province' => 'DKI Jakarta', 'postal' => '12110'],
        ['id' => '155', 'label' => 'Jakarta Utara, DKI Jakarta', 'province' => 'DKI Jakarta', 'postal' => '14140'],
        ['id' => '23', 'label' => 'Bandung, Jawa Barat', 'province' => 'Jawa Barat', 'postal' => '40111'],
        ['id' => '256', 'label' => 'Surabaya, Jawa Timur', 'province' => 'Jawa Timur', 'postal' => '60111'],
        ['id' => '190', 'label' => 'Medan, Sumatera Utara', 'province' => 'Sumatera Utara', 'postal' => '20111'],
        ['id' => '114', 'label' => 'Denpasar, Bali', 'province' => 'Bali', 'postal' => '80111'],
        ['id' => '327', 'label' => 'Makassar, Sulawesi Selatan', 'province' => 'Sulawesi Selatan', 'postal' => '90111'],
        ['id' => '446', 'label' => 'Yogyakarta, DI Yogyakarta', 'province' => 'DI Yogyakarta', 'postal' => '55111'],
        ['id' => '377', 'label' => 'Semarang, Jawa Tengah', 'province' => 'Jawa Tengah', 'postal' => '50111'],
    ];

    private const RATES = [
        'jne' => [
            ['service' => 'REG', 'description' => 'Layanan Reguler', 'base' => 9000, 'per_kg' => 8000, 'etd' => '2-3 hari'],
            ['service' => 'YES', 'description' => 'Yakin Esok Sampai', 'base' => 18000, 'per_kg' => 16000, 'etd' => '1 hari'],
        ],
        'pos' => [
            ['service' => 'Paket Kilat Khusus', 'description' => 'Paket Kilat Khusus', 'base' => 8000, 'per_kg' => 7000, 'etd' => '2-4 hari'],
        ],
        'tiki' => [
            ['service' => 'REG', 'description' => 'Reguler', 'base' => 8500, 'per_kg' => 7500, 'etd' => '2-3 hari'],
            ['service' => 'ONS', 'description' => 'Over Night Service', 'base' => 17000, 'per_kg' => 15000, 'etd' => '1 hari'],
        ],
    ];

    public function searchDestination(string $keyword): array
    {
        $kw = strtolower(trim($keyword));
        if ($kw === '') {
            return array_slice(self::CITIES, 0, 5);
        }
        $out = [];
        foreach (self::CITIES as $c) {
            if (str_contains(strtolower($c['label']), $kw)) {
                $out[] = $c;
            }
        }
        return $out;
    }

    public function cost(string $originId, string $destinationId, int $weightGram, string $courier = ''): array
    {
        if ($destinationId === '' || $weightGram <= 0) {
            throw new \RuntimeException('Destinasi dan berat wajib diisi.');
        }
        $kg = max(1, (int)ceil($weightGram / 1000));
        // Deterministic distance factor from id pair so demos look realistic
        $factor = 1 + ((abs(crc32($originId . ':' . $destinationId)) % 40) / 100);
        $couriers = $courier !== '' ? [$courier] : array_keys(self::RATES);
        $out = [];
        foreach ($couriers as $c) {
            foreach (self::RATES[strtolower($c)] ?? [] as $r) {
                $out[] = [
                    'courier' => strtolower($c),
                    'service' => $r['service'],
                    'description' => $r['description'],
                    'cost' => (int)round(($r['base'] + $r['per_kg'] * ($kg - 1)) * $factor),
                    'etd' => $r['etd'],
                    'note' => 'mock',
                ];
            }
        }
        if ($out === []) {
            throw new \RuntimeException("Kurir '{$courier}' tidak tersedia (mock: jne, pos, tiki).");
        }
        return $out;
    }

    public function track(string $waybill, string $courier): array
    {
        return [
            'waybill' => $waybill,
            'courier' => $courier,
            'status' => 'ON PROCESS (mock)',
            'events' => [
                ['time' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'desc' => 'Paket diterima di agen (mock)'],
                ['time' => date('Y-m-d H:i:s', strtotime('-1 day')), 'desc' => 'Paket diproses di sorting center (mock)'],
            ],
            'note' => 'mock',
        ];
    }
}

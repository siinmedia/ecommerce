<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * RealRajaOngkir: stub for RajaOngkir Starter API (cost + city search + waybill).
 * Activate: set rajaongkir.mode=real and fill rajaongkir.api_key in config.php (never commit).
 * Every request/response is logged to integration_requests (key redacted).
 */
class RealRajaOngkir implements RajaOngkirInterface
{
    public function __construct(private array $cfg) {}

    private function log(string $endpoint, array $request, int $status, string $responseSnippet, string $correlation): void
    {
        // Redact key: never persist it
        unset($request['key']);
        $st = Database::pdo()->prepare(
            'INSERT INTO integration_requests (provider, endpoint, correlation_id, request_json, response_code, response_json, created_at)
             VALUES ("rajaongkir", ?, ?, ?, ?, ?, NOW())'
        );
        $st->execute([$endpoint, $correlation, json_encode($request), $status, mb_substr($responseSnippet, 0, 2000)]);
    }

    private function call(string $endpoint, array $payload, string $method = 'POST'): array
    {
        $key = $this->cfg['api_key'] ?? '';
        if ($key === '') {
            throw new \RuntimeException('RajaOngkir API key belum diisi (config.php > rajaongkir.api_key).');
        }
        $correlation = bin2hex(random_bytes(8));
        $url = rtrim($this->cfg['base_url'], '/') . $endpoint;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int)($this->cfg['timeout'] ?? 10),
            CURLOPT_HTTPHEADER => ['key: ' . $key, 'content-type: application/x-www-form-urlencoded'],
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        } else {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($payload));
        }
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false || $err !== '') {
            $this->log($endpoint, $payload, 0, 'CURL_ERROR: ' . $err, $correlation);
            throw new \RuntimeException('RajaOngkir tidak dapat dihubungi. Coba lagi. (ref ' . $correlation . ')');
        }
        $this->log($endpoint, $payload, $code, (string)$body, $correlation);
        $json = json_decode((string)$body, true);
        if (!is_array($json) || ($json['rajaongkir']['status']['code'] ?? 500) !== 200) {
            $desc = $json['rajaongkir']['status']['description'] ?? 'unknown error';
            throw new \RuntimeException('RajaOngkir error: ' . $desc . ' (ref ' . $correlation . ')');
        }
        return $json['rajaongkir'];
    }

    public function searchDestination(string $keyword): array
    {
        $res = $this->call('/city', [], 'GET');
        $out = [];
        foreach ($res['results'] ?? [] as $c) {
            $label = $c['city_name'] . ', ' . $c['province'];
            if ($keyword === '' || str_contains(strtolower($label), strtolower($keyword))) {
                $out[] = ['id' => $c['city_id'], 'label' => $label, 'province' => $c['province'], 'postal' => $c['postal_code']];
            }
        }
        return array_slice($out, 0, 20);
    }

    public function cost(string $originId, string $destinationId, int $weightGram, string $courier = ''): array
    {
        $res = $this->call('/cost', [
            'origin' => $originId,
            'destination' => $destinationId,
            'weight' => $weightGram,
            'courier' => $courier !== '' ? strtolower($courier) : 'jne:pos:tiki',
        ]);
        $out = [];
        foreach ($res['results'] ?? [] as $r) {
            foreach ($r['costs'] ?? [] as $c) {
                $cost = $c['cost'][0] ?? null;
                if (!$cost) {
                    continue;
                }
                $out[] = [
                    'courier' => strtolower($r['code']),
                    'service' => $c['service'],
                    'description' => $c['description'],
                    'cost' => (int)$cost['value'],
                    'etd' => $cost['etd'] ?? '',
                    'note' => $cost['note'] ?? '',
                ];
            }
        }
        return $out;
    }

    public function track(string $waybill, string $courier): array
    {
        $res = $this->call('/waybill', ['waybill' => $waybill, 'courier' => strtolower($courier)]);
        $r = $res['result'] ?? [];
        $events = [];
        foreach ($r['manifest'] ?? [] as $m) {
            $events[] = ['time' => ($m['manifest_date'] ?? '') . ' ' . ($m['manifest_time'] ?? ''), 'desc' => $m['manifest_description'] ?? ''];
        }
        return [
            'waybill' => $waybill,
            'courier' => $courier,
            'status' => $r['delivery_status']['status'] ?? 'unknown',
            'events' => $events,
            'note' => 'real',
        ];
    }

    public static function make(array $rajaCfg): RajaOngkirInterface
    {
        if (($rajaCfg['mode'] ?? 'mock') === 'real') {
            return new self($rajaCfg);
        }
        return new MockRajaOngkir();
    }
}

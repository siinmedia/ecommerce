<?php
declare(strict_types=1);

namespace App\Services;

interface RajaOngkirInterface
{
    /** Search destination cities/areas by keyword. Returns normalized list. */
    public function searchDestination(string $keyword): array;

    /**
     * Calculate shipping cost.
     * @return array[] each: ['courier'=>, 'service'=>, 'description'=>, 'cost'=>int, 'etd'=>, 'note'=>]
     */
    public function cost(string $originId, string $destinationId, int $weightGram, string $courier = ''): array;

    /** Track waybill. Returns normalized timeline or error. */
    public function track(string $waybill, string $courier): array;
}

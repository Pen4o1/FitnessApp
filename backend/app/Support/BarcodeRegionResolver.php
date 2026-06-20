<?php

namespace App\Support;

class BarcodeRegionResolver
{
    /**
     * @return list<string>
     */
    public function regionsForBarcode(string $barcode): array
    {
        $chain = $this->configuredBarcodeRegions();
        $hint = $this->hintRegionFromGtin($this->normalizeToGtin13($barcode));

        if ($hint === null) {
            return $chain;
        }

        return $this->prioritizeRegion($hint, $chain);
    }

    /**
     * @return list<string>
     */
    private function configuredBarcodeRegions(): array
    {
        $configured = config('services.fatsecret.barcode_regions');

        if (is_array($configured) && $configured !== []) {
            return array_values(array_map(
                fn (mixed $region): string => strtoupper(trim((string) $region)),
                $configured,
            ));
        }

        $fallback = config('services.fatsecret.region');

        return is_string($fallback) && $fallback !== ''
            ? [strtoupper($fallback)]
            : ['US'];
    }

    private function normalizeToGtin13(string $barcode): string
    {
        $digits = preg_replace('/\D/', '', $barcode) ?? '';

        return str_pad($digits, 13, '0', STR_PAD_LEFT);
    }

    private function hintRegionFromGtin(string $gtin13): ?string
    {
        if (strlen($gtin13) < 3) {
            return null;
        }

        if (str_starts_with($gtin13, '380')) {
            return 'BG';
        }

        $prefix3 = (int) substr($gtin13, 0, 3);

        if ($prefix3 >= 400 && $prefix3 <= 440) {
            return 'DE';
        }

        if ($prefix3 >= 800 && $prefix3 <= 839) {
            return 'IT';
        }

        if (str_starts_with($gtin13, '590')) {
            return 'PL';
        }

        if (str_starts_with($gtin13, '868') || str_starts_with($gtin13, '869')) {
            return 'TR';
        }

        return null;
    }

    /**
     * @param  list<string>  $chain
     * @return list<string>
     */
    private function prioritizeRegion(string $hint, array $chain): array
    {
        $hint = strtoupper($hint);
        $ordered = [$hint];

        foreach ($chain as $region) {
            if ($region !== $hint) {
                $ordered[] = $region;
            }
        }

        return $ordered;
    }
}

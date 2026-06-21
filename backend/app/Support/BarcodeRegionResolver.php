<?php

namespace App\Support;

class BarcodeRegionResolver
{
    public function __construct(
        private readonly Gs1BarcodePrefixMap $prefixMap = new Gs1BarcodePrefixMap,
    ) {}

    /**
     * Build a smart, deduplicated region chain for a barcode lookup.
     *
     * Order:
     * 1. GS1 prefix hint(s) for the GTIN
     * 2. User-configured preferred regions (FATSECRET_BARCODE_REGIONS / FATSECRET_REGION)
     * 3. Regional cluster neighbours for hinted markets
     * 4. Remaining FatSecret barcode markets (global import fallback)
     *
     * @return list<string>
     */
    public function regionsForBarcode(string $barcode): array
    {
        $supported = $this->supportedRegions();
        $gtin13 = $this->normalizeToGtin13($barcode);
        $gs1Hints = $this->prefixMap->filterToSupported(
            $this->prefixMap->regionsForGtin($gtin13),
            $supported,
        );
        $preferred = $this->configuredPreferredRegions($supported);
        $clusterNeighbours = $this->clusterNeighboursFor($gs1Hints, $supported);

        return $this->uniqueOrdered(
            $gs1Hints,
            $preferred,
            $clusterNeighbours,
            $supported,
        );
    }

    /**
     * @return list<string>
     */
    private function supportedRegions(): array
    {
        $configured = config('fatsecret_barcode.supported_regions');

        if (! is_array($configured) || $configured === []) {
            return ['US'];
        }

        return array_values(array_map(
            fn (mixed $region): string => strtoupper(trim((string) $region)),
            $configured,
        ));
    }

    /**
     * User preference boost — not the full search list.
     *
     * @param  list<string>  $supported
     * @return list<string>
     */
    private function configuredPreferredRegions(array $supported): array
    {
        $supportedLookup = array_flip($supported);
        $configured = config('services.fatsecret.barcode_regions');
        $preferred = [];

        if (is_array($configured) && $configured !== []) {
            foreach ($configured as $region) {
                $code = strtoupper(trim((string) $region));

                if ($code !== '' && isset($supportedLookup[$code])) {
                    $preferred[] = $code;
                }
            }
        }

        $primary = config('services.fatsecret.region');

        if (is_string($primary) && $primary !== '') {
            $code = strtoupper($primary);

            if (isset($supportedLookup[$code])) {
                $preferred[] = $code;
            }
        }

        return array_values(array_unique($preferred));
    }

    /**
     * @param  list<string>  $hintedRegions
     * @param  list<string>  $supported
     * @return list<string>
     */
    private function clusterNeighboursFor(array $hintedRegions, array $supported): array
    {
        $clusters = config('fatsecret_barcode.regional_clusters');

        if (! is_array($clusters) || $hintedRegions === []) {
            return [];
        }

        $supportedLookup = array_flip($supported);
        $neighbours = [];

        foreach ($hintedRegions as $hint) {
            $members = $clusters[$hint] ?? [];

            if (! is_array($members)) {
                continue;
            }

            foreach ($members as $member) {
                $code = strtoupper(trim((string) $member));

                if ($code !== '' && isset($supportedLookup[$code])) {
                    $neighbours[] = $code;
                }
            }
        }

        return $neighbours;
    }

    /**
     * @param  list<string>  ...$groups
     * @return list<string>
     */
    private function uniqueOrdered(array ...$groups): array
    {
        $ordered = [];
        $seen = [];

        foreach ($groups as $group) {
            foreach ($group as $region) {
                $code = strtoupper(trim($region));

                if ($code === '' || isset($seen[$code])) {
                    continue;
                }

                $seen[$code] = true;
                $ordered[] = $code;
            }
        }

        return $ordered;
    }

    private function normalizeToGtin13(string $barcode): string
    {
        $digits = preg_replace('/\D/', '', $barcode) ?? '';

        return str_pad($digits, 13, '0', STR_PAD_LEFT);
    }
}

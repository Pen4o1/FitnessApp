<?php

namespace Tests\Unit\Support;

use App\Support\BarcodeRegionResolver;
use App\Support\Gs1BarcodePrefixMap;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BarcodeRegionResolverTest extends TestCase
{
    private BarcodeRegionResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fatsecret.region' => 'BG',
            'services.fatsecret.barcode_regions' => ['BG', 'DE', 'RO', 'GR', 'TR', 'AT', 'IT'],
            'fatsecret_barcode.lookup_batch_size' => 8,
        ]);

        $this->resolver = new BarcodeRegionResolver;
    }

    public function test_prioritizes_bulgarian_prefix_before_user_preferences(): void
    {
        $regions = $this->resolver->regionsForBarcode('3800012345678');

        $this->assertSame('BG', $regions[0]);
        $this->assertContains('RO', $regions);
        $this->assertContains('DE', $regions);
        $this->assertSame($regions, array_values(array_unique($regions)));
    }

    public function test_prioritizes_german_prefix_before_bulgaria_for_imported_products(): void
    {
        $regions = $this->resolver->regionsForBarcode('4014400901191');

        $this->assertSame('DE', $regions[0]);
        $this->assertContains('BG', $regions);
        $this->assertContains('AT', $regions);
    }

    #[DataProvider('prefixHintCases')]
    public function test_prioritizes_region_from_gs1_prefix(string $barcode, string $expectedFirstRegion): void
    {
        $regions = $this->resolver->regionsForBarcode($barcode);

        $this->assertSame($expectedFirstRegion, $regions[0]);
        $this->assertSame($regions, array_values(array_unique($regions)));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function prefixHintCases(): array
    {
        return [
            'italy' => ['8001234567890', 'IT'],
            'poland' => ['5901234567890', 'PL'],
            'turkey' => ['8691234567890', 'TR'],
            'united_states_upc' => ['0041570054161', 'US'],
            'united_kingdom' => ['5000436338826', 'GB'],
            'france' => ['3245413852113', 'FR'],
            'brazil' => ['7897517209223', 'BR'],
            'singapore' => ['8888351200070', 'SG'],
        ];
    }

    public function test_includes_all_supported_regions_as_global_fallback(): void
    {
        // Prefix 200 is unassigned in our GS1 map — no geographic hint.
        $regions = $this->resolver->regionsForBarcode('2001234567890');
        $supported = config('fatsecret_barcode.supported_regions');

        $this->assertCount(count($supported), $regions);
        $this->assertSame($regions, array_values(array_unique($regions)));

        foreach ($supported as $region) {
            $this->assertContains($region, $regions);
        }

        $this->assertSame('BG', $regions[0]);
    }

    public function test_falls_back_to_primary_region_when_barcode_regions_are_not_configured(): void
    {
        config([
            'services.fatsecret.region' => 'DE',
            'services.fatsecret.barcode_regions' => [],
        ]);

        $regions = (new BarcodeRegionResolver)->regionsForBarcode('4014400901191');

        $this->assertSame('DE', $regions[0]);
        $this->assertContains('AT', $regions);
        $this->assertContains('US', $regions);
    }

    public function test_adds_cluster_neighbours_after_gs1_hint(): void
    {
        $regions = $this->resolver->regionsForBarcode('3800012345678');

        $roIndex = array_search('RO', $regions, true);
        $usIndex = array_search('US', $regions, true);

        $this->assertNotFalse($roIndex);
        $this->assertNotFalse($usIndex);
        $this->assertLessThan($usIndex, $roIndex);
    }
}

class Gs1BarcodePrefixMapTest extends TestCase
{
    private Gs1BarcodePrefixMap $map;

    protected function setUp(): void
    {
        parent::setUp();

        $this->map = new Gs1BarcodePrefixMap;
    }

    public function test_maps_common_prefixes(): void
    {
        $this->assertSame(['BG'], $this->map->regionsForGtin('3800012345678'));
        $this->assertSame(['DE'], $this->map->regionsForGtin('4014400901191'));
        $this->assertSame(['US'], $this->map->regionsForGtin('0041570054161'));
    }

    public function test_filters_to_supported_regions(): void
    {
        $supported = ['BG', 'DE', 'US'];

        $this->assertSame(
            ['DE'],
            $this->map->filterToSupported(['DE', 'SI'], $supported),
        );
    }
}

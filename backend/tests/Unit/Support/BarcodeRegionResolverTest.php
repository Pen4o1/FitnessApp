<?php

namespace Tests\Unit\Support;

use App\Support\BarcodeRegionResolver;
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
        ]);

        $this->resolver = new BarcodeRegionResolver();
    }

    public function test_prioritizes_bulgarian_prefix_before_configured_chain(): void
    {
        $this->assertSame(
            ['BG', 'DE', 'RO', 'GR', 'TR', 'AT', 'IT'],
            $this->resolver->regionsForBarcode('3800012345678'),
        );
    }

    public function test_prioritizes_german_prefix_before_bulgaria_for_imported_products(): void
    {
        $this->assertSame(
            ['DE', 'BG', 'RO', 'GR', 'TR', 'AT', 'IT'],
            $this->resolver->regionsForBarcode('4014400901191'),
        );
    }

    #[DataProvider('prefixHintCases')]
    public function test_prioritizes_region_from_gs1_prefix(string $barcode, string $expectedFirstRegion): void
    {
        $regions = $this->resolver->regionsForBarcode($barcode);

        $this->assertSame($expectedFirstRegion, $regions[0]);
        $this->assertSame($regions, array_values(array_unique($regions)));
        $this->assertContains('BG', $regions);
        $this->assertContains('DE', $regions);
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
        ];
    }

    public function test_keeps_configured_chain_when_prefix_is_unknown(): void
    {
        $this->assertSame(
            ['BG', 'DE', 'RO', 'GR', 'TR', 'AT', 'IT'],
            $this->resolver->regionsForBarcode('1234567890123'),
        );
    }

    public function test_falls_back_to_primary_region_when_barcode_regions_are_not_configured(): void
    {
        config([
            'services.fatsecret.region' => 'DE',
            'services.fatsecret.barcode_regions' => [],
        ]);

        $this->assertSame(['DE'], (new BarcodeRegionResolver())->regionsForBarcode('4014400901191'));
    }
}

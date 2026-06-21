<?php

namespace App\Support;

/**
 * Maps GS1 company-prefix ranges on GTIN-13 barcodes to likely FatSecret region codes.
 *
 * @see https://www.gs1.org/standards/id-keys/company-prefix
 */
class Gs1BarcodePrefixMap
{
    /**
     * @var list<array{0: int, 1: int, 2: string}>
     */
    private const PREFIX_RANGES = [
        [0, 19, 'US'],
        [30, 39, 'US'],
        [60, 139, 'US'],
        [300, 379, 'FR'],
        [380, 380, 'BG'],
        [383, 383, 'SI'],
        [385, 385, 'HR'],
        [387, 387, 'BA'],
        [400, 440, 'DE'],
        [450, 459, 'JP'],
        [460, 469, 'RU'],
        [470, 470, 'KG'],
        [471, 471, 'TW'],
        [474, 474, 'EE'],
        [475, 475, 'LV'],
        [476, 476, 'AZ'],
        [477, 477, 'LT'],
        [478, 478, 'UZ'],
        [479, 479, 'LK'],
        [480, 480, 'PH'],
        [481, 481, 'BY'],
        [482, 482, 'UA'],
        [484, 484, 'MD'],
        [485, 485, 'AM'],
        [486, 487, 'KZ'],
        [489, 489, 'HK'],
        [490, 499, 'JP'],
        [500, 509, 'GB'],
        [520, 521, 'GR'],
        [528, 528, 'LB'],
        [529, 529, 'CY'],
        [530, 530, 'AL'],
        [531, 531, 'MK'],
        [535, 535, 'MT'],
        [539, 539, 'IE'],
        [540, 549, 'BE'],
        [560, 560, 'PT'],
        [569, 569, 'IS'],
        [570, 579, 'DK'],
        [590, 590, 'PL'],
        [594, 594, 'RO'],
        [599, 599, 'HU'],
        [600, 601, 'ZA'],
        [608, 608, 'BH'],
        [609, 609, 'MU'],
        [611, 611, 'MA'],
        [613, 613, 'DZ'],
        [615, 615, 'NG'],
        [616, 616, 'KE'],
        [618, 618, 'CI'],
        [619, 619, 'TN'],
        [621, 621, 'SY'],
        [622, 622, 'EG'],
        [624, 624, 'LY'],
        [625, 625, 'JO'],
        [628, 628, 'SA'],
        [629, 629, 'AE'],
        [640, 649, 'FI'],
        [690, 699, 'CN'],
        [700, 709, 'NO'],
        [729, 729, 'IL'],
        [730, 739, 'SE'],
        [740, 740, 'GT'],
        [741, 741, 'SV'],
        [742, 742, 'HN'],
        [743, 743, 'NI'],
        [744, 744, 'CR'],
        [745, 745, 'PA'],
        [746, 746, 'DO'],
        [750, 750, 'MX'],
        [754, 755, 'CA'],
        [759, 759, 'VE'],
        [760, 769, 'CH'],
        [770, 770, 'CO'],
        [773, 773, 'UY'],
        [775, 775, 'PE'],
        [777, 777, 'BO'],
        [779, 779, 'AR'],
        [780, 780, 'CL'],
        [784, 784, 'PY'],
        [786, 786, 'EC'],
        [789, 790, 'BR'],
        [800, 839, 'IT'],
        [840, 849, 'ES'],
        [850, 850, 'CU'],
        [858, 858, 'SK'],
        [859, 859, 'CZ'],
        [860, 860, 'RS'],
        [868, 869, 'TR'],
        [870, 879, 'NL'],
        [880, 880, 'KR'],
        [884, 884, 'KH'],
        [885, 885, 'TH'],
        [888, 888, 'SG'],
        [890, 890, 'IN'],
        [893, 893, 'VN'],
        [899, 899, 'ID'],
        [900, 919, 'AT'],
        [930, 939, 'AU'],
        [940, 949, 'NZ'],
        [955, 955, 'MY'],
        [958, 958, 'MO'],
    ];

    /**
     * @return list<string>
     */
    public function regionsForGtin(string $gtin13): array
    {
        if (! preg_match('/^\d{13}$/', $gtin13)) {
            return [];
        }

        $prefix = (int) substr($gtin13, 0, 3);
        $regions = [];

        foreach (self::PREFIX_RANGES as [$from, $to, $region]) {
            if ($prefix >= $from && $prefix <= $to) {
                $regions[] = $region;
            }
        }

        // UPC-A values padded to GTIN-13 often start with 0 but represent US products.
        if ($regions === [] && str_starts_with($gtin13, '0')) {
            $regions[] = 'US';
        }

        return array_values(array_unique($regions));
    }

    /**
     * @param  list<string>  $supportedRegions
     * @return list<string>
     */
    public function filterToSupported(array $regions, array $supportedRegions): array
    {
        $supported = array_flip(array_map('strtoupper', $supportedRegions));

        return array_values(array_filter(
            $regions,
            fn (string $region): bool => isset($supported[strtoupper($region)]),
        ));
    }
}

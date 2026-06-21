<?php

/**
 * FatSecret regions that support barcode lookup (API demo / premier barcode markets).
 *
 * @see https://platform.fatsecret.com/api-demo
 *
 * @var list<string>
 */
$supportedRegions = [
    'AR', 'AU', 'AT', 'BY', 'BE', 'BR', 'BG', 'CA', 'CL', 'CN', 'CO', 'CR', 'CZ', 'DK', 'EC', 'EG', 'EE',
    'FI', 'FR', 'DE', 'HK', 'IN', 'ID', 'IE', 'IL', 'IT', 'JP', 'KZ', 'KR', 'LV', 'LT', 'MY', 'MX', 'MD',
    'NL', 'NZ', 'NO', 'PE', 'PH', 'PL', 'PT', 'RO', 'RU', 'SA', 'SG', 'ZA', 'ES', 'SE', 'CH', 'TW', 'TR',
    'UA', 'AE', 'GB', 'US', 'VE',
];

return [

    /*
    |--------------------------------------------------------------------------
    | Supported barcode regions
    |--------------------------------------------------------------------------
    |
    | Complete list of FatSecret market codes that can be queried for barcodes.
    | The resolver orders these intelligently per scan; you do not need to list
    | them manually in FATSECRET_BARCODE_REGIONS unless you want to boost certain
    | markets after the GS1 prefix hint.
    |
    */
    'supported_regions' => $supportedRegions,

    /*
    |--------------------------------------------------------------------------
    | Lookup batch size
    |--------------------------------------------------------------------------
    |
    | Barcode lookups run in parallel waves of this many regions. Likely regions
    | are tried first; later waves only run when earlier ones return "not found".
    |
    */
    'lookup_batch_size' => (int) env('FATSECRET_BARCODE_BATCH_SIZE', 8),

    /*
    |--------------------------------------------------------------------------
    | Regional clusters
    |--------------------------------------------------------------------------
    |
    | When a GS1 prefix hints at one market, nearby / commonly cross-importing
    | markets are tried in the next wave before scanning the rest of the world.
    |
    */
    'regional_clusters' => [
        'BG' => ['RO', 'GR', 'MK', 'RS', 'TR', 'DE', 'IT'],
        'RO' => ['BG', 'GR', 'HU', 'MD', 'DE', 'IT'],
        'GR' => ['BG', 'RO', 'CY', 'IT', 'DE', 'TR'],
        'DE' => ['AT', 'CH', 'NL', 'BE', 'FR', 'PL', 'CZ', 'IT'],
        'AT' => ['DE', 'CH', 'IT', 'CZ', 'HU'],
        'IT' => ['FR', 'DE', 'AT', 'CH', 'ES', 'GR'],
        'FR' => ['BE', 'DE', 'ES', 'IT', 'CH', 'GB'],
        'ES' => ['PT', 'FR', 'IT'],
        'PT' => ['ES', 'FR'],
        'PL' => ['DE', 'CZ', 'LT', 'UA', 'RO'],
        'CZ' => ['SK', 'DE', 'AT', 'PL', 'HU'],
        'HU' => ['RO', 'AT', 'DE', 'PL'],
        'TR' => ['BG', 'GR', 'DE', 'RO'],
        'GB' => ['IE', 'FR', 'DE', 'NL', 'US'],
        'IE' => ['GB', 'FR', 'DE'],
        'US' => ['CA', 'MX', 'GB'],
        'CA' => ['US', 'MX'],
        'MX' => ['US', 'CA'],
        'NL' => ['BE', 'DE', 'FR', 'GB'],
        'BE' => ['NL', 'FR', 'DE', 'LU'],
        'CH' => ['DE', 'AT', 'FR', 'IT'],
        'SE' => ['NO', 'DK', 'FI', 'DE'],
        'NO' => ['SE', 'DK', 'FI'],
        'DK' => ['SE', 'NO', 'DE'],
        'FI' => ['SE', 'EE', 'DE'],
        'RU' => ['BY', 'KZ', 'UA', 'PL', 'FI'],
        'UA' => ['PL', 'RO', 'MD', 'RU', 'DE'],
        'BY' => ['RU', 'PL', 'UA', 'LT', 'LV'],
        'KZ' => ['RU', 'TR'],
        'CN' => ['HK', 'TW', 'JP', 'KR', 'SG'],
        'HK' => ['CN', 'TW', 'SG'],
        'TW' => ['CN', 'HK', 'JP'],
        'JP' => ['KR', 'CN', 'TW', 'US'],
        'KR' => ['JP', 'CN', 'US'],
        'IN' => ['AE', 'SG', 'GB', 'US'],
        'AU' => ['NZ', 'SG', 'US', 'GB'],
        'NZ' => ['AU', 'US', 'GB'],
        'SG' => ['MY', 'ID', 'IN', 'AU', 'CN'],
        'MY' => ['SG', 'ID', 'TH'],
        'ID' => ['SG', 'MY', 'AU'],
        'PH' => ['SG', 'US', 'AU'],
        'TH' => ['SG', 'MY', 'ID'],
        'AE' => ['SA', 'IN', 'GB'],
        'SA' => ['AE', 'EG'],
        'EG' => ['SA', 'AE', 'DE', 'IT'],
        'IL' => ['AE', 'TR', 'DE', 'US'],
        'ZA' => ['GB', 'AU', 'US'],
        'BR' => ['AR', 'US', 'PT'],
        'AR' => ['BR', 'CL', 'UY'],
        'CL' => ['AR', 'BR', 'PE'],
        'CO' => ['MX', 'US', 'VE', 'PE'],
        'PE' => ['CO', 'CL', 'MX'],
        'VE' => ['CO', 'MX', 'US'],
        'EC' => ['CO', 'PE', 'MX'],
        'CR' => ['MX', 'US', 'CO'],
    ],

];

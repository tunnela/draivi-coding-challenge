<?php

$root = dirname(__DIR__);

require $root . '/bootstrap/app.php';

use Tunnela\DraiviCodingChallenge\Scraper;

$script = $root . '/scripts/download-alko-dataset.js';

$scraper = new Scraper($script, [
    'cachePath' => $root . '/storage/cache',
    'cacheDuration' => 60,
    'database' => [
        'driver' => 'sqlite',
        'file' => $root . '/resources/database.sqlite'
    ]
]);

$scraper->onCreateQuery(function() {
    return '
        CREATE TABLE IF NOT EXISTS "products" (
            "id" INTEGER,
            "number" INTEGER NOT NULL UNIQUE,
            "name" TEXT NOT NULL,
            "bottlesize" TEXT,
            "price" NUMERIC NOT NULL,
            "priceGBP" NUMERIC NOT NULL,
            "timestamp" INTEGER NOT NULL,
            "orderamount" INTEGER NOT NULL DEFAULT 0,
            PRIMARY KEY("id" AUTOINCREMENT)
        )
    ';
});

$scraper->onInsertQuery(function($fields, $bindings) {
    return '
        INSERT INTO "products" (' . $fields . ') VALUES (' . $bindings . ') 
        ON CONFLICT(number) DO UPDATE SET price = excluded.price, 
        priceGBP = excluded.priceGBP, timestamp = strftime(\'%s\', \'now\')
        WHERE price != excluded.price OR priceGBP != excluded.priceGBP;
    ';
});

$scraper->onLoad(function($dataset) {
    $currencyParams = [
        'access_key' => getenv('CURRENCYLAYER_ACCESS_KEY'),
        'source' => 'EUR',
        'currencies' => 'GBP'
    ];

    $currencyUrl = 'https://api.currencylayer.com/live?' . http_build_query($currencyParams);

    $currencyConversion = json_decode(file_get_contents($currencyUrl), true);

    if (!$currencyConversion || empty($currencyConversion['quotes']['EURGBP'])) {
        throw new \Exception('Could not load currancy conversion information');
    }
    $multiplier = floatval($currencyConversion['quotes']['EURGBP']);

    $products = [];
    $header = null;

    // Let's find all the products
    foreach ($dataset as $worksheet) {
        foreach ($worksheet as $row) {
            $item = [];

            foreach ($row as $col => $cell) {
                if ($header !== null) {
                    $item[$header[$col]] = $cell;

                    continue;
                }
                if ($cell == 'Numero') {
                    $header = $row;

                    continue 2;
                }
            }
            if ($header === null) {
                continue;
            }
            $products[] = [
                'number' => $item['Numero'],
                'name' => $item['Nimi'],
                'bottlesize' => $item['Pullokoko'],
                'price' => number_format(floatval($item['Hinta']), 2, '.', ''),
                'priceGBP' => number_format(floatval($item['Hinta']) * $multiplier, 2, '.', ''),
                'timestamp' => time(),
                'orderamount' => 0
            ];
        }
    }
    return $products;
});

$scraper->run();
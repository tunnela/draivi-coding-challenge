<?php

require dirname(__DIR__) . '/bootstrap/app.php';

use Tunnela\DraiviCodingChallenge\Scraper;

set_time_limit(0);

echo "\nScraper started! Please wait...\n";

$script = root_path('scripts/scrape.js');

$scraper = new Scraper($script, [
    'cachePath' => root_path('storage/cache'),
     // add seconds here to cache result for X seconds
    'cacheDuration' => null,
    'database' => [
        'driver' => 'sqlite',
        'file' => root_path('resources/databases/database.sqlite')
    ],
    'loader' => [
        'nodePath' => getenv('NODE_BIN_PATH') ?: 'node'
    ],
    'remotePrimaryKey' => 'number'
]);

$scraper->onCreateQuery(function() {
    return '
        CREATE TABLE IF NOT EXISTS "products" (
            "id" INTEGER,
             /* Is padded with zeros, so can not be INTEGER */
            "number" TEXT NOT NULL UNIQUE,
            "name" TEXT NOT NULL,
            "bottlesize" TEXT,
             /* SQLite does not support DECIMAL */
            "price" TEXT NOT NULL,
             /* SQLite does not support DECIMAL */
            "priceGBP" TEXT NOT NULL,
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

$scraper->onDeleteQuery(function($remotePrimary, $bindings) {
    return '
        DELETE FROM "products" WHERE ' . $remotePrimary . ' NOT IN (' . $bindings . ');
    ';
});

$scraper->onLoad(function($dataset) {
    $currencyParams = [
        'access_key' => getenv('CURRENCYLAYER_ACCESS_KEY'),
        'source' => 'EUR',
        'currencies' => 'GBP'
    ];

    $currencyUrl = 'https://api.currencylayer.com/live?' . http_build_query($currencyParams);

    $currencyResult = file_get_contents($currencyUrl);
    $currencyConversion = json_decode($currencyResult, true);

    if (!$currencyConversion || empty($currencyConversion['quotes']['EURGBP'])) {
        throw new \Exception('Could not load currency conversion information: ' . $currencyResult);
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
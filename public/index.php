<?php

$root = dirname(__DIR__);

require $root . '/bootstrap/app.php';

use Tunnela\DraiviCodingChallenge\App;

$app = new App([
    'viewPath' => $root . '/resources/views',
    'database' => [
        'driver' => 'sqlite',
        'file' => $root . '/resources/database.sqlite'
    ]
]);

$app->get('/', function() {
    return $this->view('app');
});

$app->get('/api/products', function($database) {
    return $this->json(
        $database
        ->query('SELECT * FROM products')
        ->fetchAll(\PDO::FETCH_ASSOC)
    );
});

$app->get('/api/products/{id}', function($id, $database) {
    $statement = $database->prepare('SELECT * FROM products WHERE id = ?');

    $statement->execute([$id]);

    return $this->json(
        $statement->fetch(\PDO::FETCH_ASSOC)
    );
});

$app->put('/api/products/{id}', function($id, $data, $database) {
    if (!isset($data->orderamount)) {
        return $this->json([], 422);
    }
    $statement = $database->prepare('UPDATE products SET orderamount = ? WHERE id = ?');

    $statement->execute([$data->orderamount, $id]);

    $statement = $database->prepare('SELECT * FROM products WHERE id = ?');

    $statement->execute([$id]);

    return $this->json(
        $statement->fetch(\PDO::FETCH_ASSOC)
    );
});

$app->run();
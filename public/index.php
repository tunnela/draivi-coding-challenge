<?php

require dirname(__DIR__) . '/bootstrap/app.php';

use Tunnela\DraiviCodingChallenge\App;

$app = new App([
    'viewPath' => root_path('resources/views'),
    'database' => [
        'driver' => 'sqlite',
        'file' => root_path('resources/databases/database.sqlite')
    ]
]);

$app->get('/', function() {
    return $this->view('app');
});

$app->get('/api/products', function($database) {
    return $this->json(
        $database
        ->query('SELECT * FROM products ORDER BY number ASC')
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
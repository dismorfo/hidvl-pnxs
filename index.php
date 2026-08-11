<?php

/**
 * @file
 * index.php
 */

define('REQUESTS_SILENCE_PSR0_DEPRECATIONS', true);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/include/common.php';
require_once __DIR__ . '/include/ISO639.php';

use Bramus\Router\Router;

$router = new Router();

// Custom 404 Handler
$router->set404(function () {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    echo '404, route not found!';
});

// Home
$router->get('/', function () {
    require_once './routes/home.php';
    html(home());
});

// Item Withdrawn
$router->get('/item-withdrawn', function () {
    require_once './routes/itemwithdrawn.php';
    html(itemwithdrawn());
});

// Metadata Player
$router->get('/(\w+)', function (...$params) {
    require_once './routes/hidvl-metadata-player.php';
    html(player($params));
});

$router->run();
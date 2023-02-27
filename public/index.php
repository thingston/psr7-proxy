<?php

use Laminas\Diactoros\ServerRequestFactory;
use Thingston\Psr7Proxy\Proxy;

require __DIR__ . '/../vendor/autoload.php';

define('BASE_URL', 'https://httpbin.org');
define('EXTRA_HEADERS', [
    'Authozition' => 'Bearer MY_BEARER_TOKEN',
]);

Proxy::create(BASE_URL, EXTRA_HEADERS)
    ->run(ServerRequestFactory::fromGlobals());

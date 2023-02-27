<?php

use Laminas\Diactoros\ServerRequestFactory;
use Thingston\Psr7Proxy\Proxy;

define('BASE_URL', 'https://httpbin.org');
define('EXTRA_HEADERS', []);

require __DIR__ . '/../vendor/autoload.php';

Proxy::create(BASE_URL, EXTRA_HEADERS)
    ->run(ServerRequestFactory::fromGlobals());
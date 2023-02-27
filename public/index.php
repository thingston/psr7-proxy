<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

require __DIR__ . '/../vendor/autoload.php';

define('BASE_URL', 'https://httpbin.org');
define('LOGGER_NAME', 'logger');
define('LOGGER_FILE', __DIR__ . '/../var/log/error');

$request = ServerRequestFactory::fromGlobals();

$uri = (new Uri(BASE_URL))
        ->withPath($request->getUri()->getPath())
        ->withQuery($request->getUri()->getQuery());

$options = [
    RequestOptions::HEADERS => $request->getHeaders(),
    RequestOptions::HTTP_ERRORS => false,
];

if (0 < $request->getBody()->getSize()) {
    $options[RequestOptions::BODY] = $request->getBody();
}

try {
    $response = (new Client())->request($request->getMethod(), $uri, $options);
} catch (Throwable $e) {
    (new Logger(LOGGER_NAME, [new RotatingFileHandler(LOGGER_FILE)]))->error($e->getMessage(), []);

    $response = $e instanceof ClientException && $e->hasResponse()
        ? $e->getResponse()
        : new Response($e->getMessage(), 500);
}

(new SapiEmitter())->emit($response);

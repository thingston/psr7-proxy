<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

require __DIR__ . '/../vendor/autoload.php';

define('REMOTE_HOST', 'https://httpbin.org');

$request = ServerRequestFactory::fromGlobals();

$uri = (new Uri(REMOTE_HOST))
        ->withPath($request->getUri()->getPath())
        ->withQuery($request->getUri()->getQuery());

$options = [
    RequestOptions::HEADERS => [
        'Accept' => 'application/json',
    ],
    RequestOptions::HTTP_ERRORS => false,
];

if (0 < $request->getBody()->getSize()) {
    $options[RequestOptions::BODY] = $request->getBody();
}

try {
    $response = (new Client())->request($request->getMethod(), $uri, $options);
} catch (ClientException $e) {
    $response = $e->hasResponse() ? $e->getResponse() : new \Laminas\Diactoros\Response\Response($e->getMessage(), 500);
}

(new SapiEmitter())->emit($response);

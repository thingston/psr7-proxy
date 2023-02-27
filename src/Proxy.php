<?php

declare(strict_types=1);

namespace Thingston\Psr7Proxy;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Laminas\Diactoros\ResponseFactory;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Proxy implements ProxyInterface
{
    private UriInterface $baseUri;
    private array $extraHeaders = [];
    private ClientInterface $client;
    private ?LoggerInterface $logger = null;
    private EmitterInterface $emitter;
    private ResponseFactoryInterface $responseFactory;

    /**
     * Proxy xonstructor.
     *
     * @param UriInterface $baseUri
     * @param array<string, string|string[]> $extraHeaders
     * @param ClientInterface|null $client
     * @param LoggerInterface|null $logger
     * @param EmitterInterface|null $emitter
     * @param ResponseFactoryInterface|null $responseFactory
     */
    public function __construct(
        UriInterface $baseUri,
        array $extraHeaders = [],
        ?ClientInterface $client = null,
        ?LoggerInterface $logger = null,
        ?EmitterInterface $emitter = null,
        ?ResponseFactoryInterface $responseFactory = null
    ) {
        $this->baseUri = $baseUri;
        $this->extraHeaders = $extraHeaders;
        $this->client = $client ?? new Client();
        $this->logger = $logger;
        $this->emitter = $emitter ?? new SapiEmitter();
        $this->responseFactory = $responseFactory ?? new ResponseFactory();
    }

    /**
     * Static constructor.
     *
     * @param string $baseUri
     * @param array $extraHeaders
     * @param array<string, string|string[]> $extraHeaders
     * @return self
     */
    public static function create(string $baseUri, array $extraHeaders = [], ?LoggerInterface $logger = null): self
    {
        return new self(new Uri($baseUri), $extraHeaders);
    }

    /**
     * Run the request and emit a response.
     *
     * @param ServerRequestInterface $request
     * @return void
     */
    public function run(ServerRequestInterface $request): void
    {
        try {
            $response = $this->client->sendRequest($this->prepareRequest($request));
        } catch (Throwable $e) {
            $response = $this->handleException($e);
        }

        $this->logResponse($response);

        $this->emitter->emit($response);
    }

    /**
     * Prepare request.
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    private function prepareRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        foreach ($this->extraHeaders as $name => $value) {
            $request = $request->withAddedHeader($name, $value);
        }

        $uri = $this->baseUri
            ->withPath($request->getUri()->getPath())
            ->withQuery($request->getUri()->getQuery())
            ->withFragment($request->getUri()->getFragment());

        return $request->withUri($uri);
    }

    /**
     * Log response.
     *
     * @param ResponseInterface $response
     * @param array $context
     * @return void
     */
    private function logResponse(ResponseInterface $response, array $context = []): void
    {
        if (null === $this->logger) {
            return;
        }

        if (400 > $response->getStatusCode()) {
            $this->logger->info($response->getReasonPhrase(), $context);
            return;
        }

        if (500 > $response->getStatusCode()) {
            $this->logger->warning($response->getReasonPhrase(), $context);
            return;
        }

        $this->logger->error($response->getReasonPhrase(), $context);
    }

    /**
     * Handle exception.
     *
     * @param Throwable $e
     * @return ResponseInterface
     */
    private function handleException(Throwable $e): ResponseInterface
    {
        if ($e instanceof ClientExceptionInterface && method_exists($e, 'getResponse')) {
            $response = $e->getResponse();

            if ($response instanceof ResponseInterface) {
                return $response;
            }
        }

        $body = \json_encode([
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
        ]);

        $response = $this->responseFactory->createResponse(500)
            ->withHeader('Content-Type', 'application/json');

        $response->getBody()->write($body);

        return  $response;
    }
}

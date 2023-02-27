<?php

declare(strict_types=1);


namespace Thingston\Psr7Proxy;

use Psr\Http\Message\ServerRequestInterface;

interface ProxyInterface
{
    /**
     * Run the request and emit a response.
     *
     * @param ServerRequestInterface $request
     * @return void
     */
    public function run(ServerRequestInterface $request): void;
}

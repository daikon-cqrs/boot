<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface ActionInterface
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface;

    public function handleError(ServerRequestInterface $request): ResponseInterface;

    public function registerValidator(ServerRequestInterface $request): ServerRequestInterface;

    public function isSecure(): bool;
}

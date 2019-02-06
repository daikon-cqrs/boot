<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware\Action;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ActionInterface extends StatusCodeInterface
{
    public function __invoke(ServerRequestInterface $request): ServerRequestInterface;

    public function handleError(ServerRequestInterface $request): ServerRequestInterface;

    public function registerValidator(ServerRequestInterface $request): ServerRequestInterface;

    public function isSecure(): bool;
}

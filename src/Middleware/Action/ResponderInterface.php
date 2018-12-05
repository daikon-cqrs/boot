<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ResponderInterface
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface;
}

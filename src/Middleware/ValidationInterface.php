<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Psr\Http\Message\ServerRequestInterface;

interface ValidationInterface
{
    public function __invoke(ServerRequestInterface $request): ServerRequestInterface;
}

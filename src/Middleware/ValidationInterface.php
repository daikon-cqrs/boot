<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Psr\Http\Message\ServerRequestInterface;

interface ValidationInterface
{
    const ATTR_ERRORS = 'error';

    public function __invoke(ServerRequestInterface $request): ServerRequestInterface;
}

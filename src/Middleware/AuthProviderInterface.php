<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

interface AuthProviderInterface
{
    public function authenticate(ServerRequestInterface $request);
}

<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware\Action;

use Psr\Http\Message\ServerRequestInterface;

interface ValidatorInterface
{
    public function __invoke(ServerRequestInterface $request): ServerRequestInterface;
}

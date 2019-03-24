<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware\Action;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ValidatorInterface extends StatusCodeInterface
{
    const SEVERITY_CRITICAL = 32;

    const SEVERITY_ERROR = 16;

    const SEVERITY_SUCCESS = 8;

    const SEVERITY_INFO = 4;

    public function __invoke(ServerRequestInterface $request): ServerRequestInterface;
}

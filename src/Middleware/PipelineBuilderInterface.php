<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Psr\Http\Server\RequestHandlerInterface;

interface PipelineBuilderInterface
{
    public function __invoke(): RequestHandlerInterface;
}

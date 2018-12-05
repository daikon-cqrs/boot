<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware\Action;

use Assert\Assertion;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

trait ResponderTrait
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return call_user_func($this->getOutputTypeHandler($request), $request);
    }

    private function getOutputTypeHandler(ServerRequestInterface $request): callable
    {
        $outputType = $request->getHeaderLine('Accept');
        $parts = explode('/', $outputType, 2);
        Assertion::count($parts, 2);
        $methodName = 'respondTo'.ucfirst($parts[1]);
        $outputTypeHandler = [$this, $methodName];
        if (!is_callable($outputTypeHandler)) {
            throw new RuntimeException(sprintf(
                'Output-type "%s" not supported. Method "%s" missing from: %s',
                $outputType,
                $methodName,
                self::class
            ));
        }
        return $outputTypeHandler;
    }
}
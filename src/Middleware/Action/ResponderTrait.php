<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Middleware\Action;

use Daikon\Interop\Assertion;
use Daikon\Interop\RuntimeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

trait ResponderTrait
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->getOutputTypeHandler($request)($request);
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
                'Output type "%s" not supported. Method "%s" missing from: %s',
                $outputType,
                $methodName,
                self::class
            ));
        }

        return $outputTypeHandler;
    }
}

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

abstract class Responder implements ResponderInterface
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Accept');
        $parts = explode('/', $contentType, 2);
        Assertion::count($parts, 2, "Invalid content type '$contentType'.");
        $methodName = 'respondTo'.ucfirst($parts[1]);
        $responseHandler = [$this, $methodName];
        if (!is_callable($responseHandler)) {
            throw new RuntimeException(sprintf(
                "Method '%s' for content type '%s' missing from '%s'.",
                $methodName,
                $contentType,
                static::class
            ));
        }

        return $responseHandler($request);
    }
}

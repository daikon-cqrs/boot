<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Middleware\Action;

use Daikon\Boot\Middleware\Action\ActionInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class Action implements ActionInterface, StatusCodeInterface
{
    public function registerValidator(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request;
    }

    public function handleError(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request;
    }
}

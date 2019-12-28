<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Middleware\Action;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ActionInterface extends StatusCodeInterface
{
    public function __invoke(ServerRequestInterface $request): ServerRequestInterface;

    public function handleError(ServerRequestInterface $request): ServerRequestInterface;

    public function registerValidator(ServerRequestInterface $request): ServerRequestInterface;
}

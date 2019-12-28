<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Middleware;

use Psr\Http\Server\RequestHandlerInterface;

interface PipelineBuilderInterface
{
    public function __invoke(): RequestHandlerInterface;
}

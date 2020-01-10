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

interface ValidatorInterface extends StatusCodeInterface
{
    public const SEVERITY_CRITICAL = 32;
    public const SEVERITY_ERROR = 16;
    public const SEVERITY_SUCCESS = 8;
    public const SEVERITY_INFO = 4;
    public const SEVERITY_SILENT = 2;

    public function __invoke(ServerRequestInterface $request): ServerRequestInterface;
}

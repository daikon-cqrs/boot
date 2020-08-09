<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Middleware\Action;

use Daikon\Boot\Middleware\Action\ActionInterface;
use Daikon\Validize\Validator\ValidatorInterface;
use Fig\Http\Message\StatusCodeInterface;

abstract class Action implements ActionInterface, StatusCodeInterface
{
    public function getValidator(DaikonRequest $request): ?ValidatorInterface
    {
        return null;
    }

    public function handleError(DaikonRequest $request): DaikonRequest
    {
        return $request;
    }
}

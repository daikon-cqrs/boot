<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Middleware\Action;

use Daikon\Validize\Validator\ValidatorInterface;

interface ActionInterface
{
    public function __invoke(DaikonRequest $request): DaikonRequest;

    public function getValidator(DaikonRequest $request): ?ValidatorInterface;

    public function handleError(DaikonRequest $request): DaikonRequest;
}

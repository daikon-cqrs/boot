<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Middleware;

use Auryn\Injector;
use Daikon\Interop\Assertion;
use Daikon\Interop\RuntimeException;
use Psr\Container\ContainerInterface;

trait ResolvesDependency
{
    /** @param mixed $dependency */
    private function resolve(ContainerInterface $container, $dependency, string $stereoType): callable
    {
        if (is_string($dependency)) {
            Assertion::classExists($dependency, "Given dependency '$dependency' not found.");
            $dependency = $container->get($dependency);
        } elseif (is_array($dependency) && count($dependency) === 2) {
            $fqcn = $dependency[0];
            $params = $dependency[1];
            Assertion::classExists($fqcn, "Given dependency '$fqcn' not found.");
            Assertion::isArray($params, 'Dependency parameters must be an array.');
            $dependency = $container->get(Injector::class)->make($fqcn, $params);
        }

        if (is_object($dependency)) {
            Assertion::isInstanceOf(
                $dependency,
                $stereoType,
                sprintf("Given dependency '%s' is not a '$stereoType'.", get_class($dependency))
            );
        }

        if (is_callable($dependency)) {
            return $dependency;
        }

        throw new RuntimeException(
            sprintf("Given dependency '%s' is not a '$stereoType'.", gettype($dependency))
        );
    }
}

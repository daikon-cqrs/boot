<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Assert\Assertion;
use Psr\Container\ContainerInterface;

trait ResolvesDependency
{
    /** @var ContainerInterface */
    private $container;

    /** @param mixed $dependency */
    private function resolve($dependency, string $stereoType): callable
    {
        if (is_string($dependency)) {
            Assertion::classExists($dependency, "Given dependency '$dependency' not found.");
            $dependency = $this->container->get($dependency);
        } elseif (is_array($dependency) && count($dependency) === 2) {
            $fqcn = $dependency[0];
            $params = $dependency[1];
            Assertion::classExists($fqcn, "Given dependency '$fqcn' not found.");
            Assertion::isArray($params);
            $dependency = $this->container->make($fqcn, $params);
        }
        if (is_object($dependency)) {
            Assertion::isInstanceOf($dependency, $stereoType);
        }
        if (is_callable($dependency)) {
            return $dependency;
        }
        throw new \RuntimeException(
            sprintf("Given type '%s' is not a $stereoType.", gettype($dependency))
        );
    }
}

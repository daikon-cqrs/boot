<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Service;

use Auryn\Injector;
use Psr\Container\ContainerInterface;

final class Container implements ContainerInterface
{
    private Injector $injector;

    public function __construct(Injector $injector)
    {
        $this->injector = $injector;
    }

    /**
     * @param string $fqcn
     * @return object
     */
    public function get($fqcn)
    {
        return $this->injector->make($fqcn);
    }

    /**
     * @param string $fqcn
     * @return boolean
     */
    public function has($fqcn)
    {
        return class_exists($fqcn);
    }
}

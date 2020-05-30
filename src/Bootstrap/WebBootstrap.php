<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Bootstrap;

use Auryn\Injector;
use Daikon\Boot\Service\ServiceProvisioner;
use Daikon\Config\ConfigProvider;
use Daikon\Config\ConfigProviderInterface;
use Psr\Container\ContainerInterface;

final class WebBootstrap implements BootstrapInterface
{
    use BootstrapTrait;

    public function __invoke(Injector $injector, array $bootParams): ContainerInterface
    {
        $configProvider = $this->loadConfiguration($bootParams);

        $injector
            ->share($injector)
            ->share($configProvider)
            ->alias(ConfigProviderInterface::class, ConfigProvider::class);

        $container = (new ServiceProvisioner)->provision($injector, $configProvider);

        $injector
            ->share($container)
            ->alias(ContainerInterface::class, get_class($container));

        return $container;
    }
}

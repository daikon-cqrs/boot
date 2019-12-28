<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Bootstrap;

use Auryn\Injector;
use Daikon\Config\ConfigProvider;
use Daikon\Config\ConfigProviderInterface;
use Oroshi\Core\Service\ServiceProvisioner;
use Psr\Container\ContainerInterface;

final class WebBootstrap implements BootstrapInterface
{
    use BootstrapTrait;

    public function __invoke(Injector $injector, array $bootParams): ContainerInterface
    {
        $configProvider = $this->loadConfiguration($bootParams);
        $injector
            ->share($configProvider)
            ->alias(ConfigProviderInterface::class, ConfigProvider::class);
        $container = (new ServiceProvisioner)->provision($injector, $configProvider);
        $injector
            ->share($container)
            ->alias(ContainerInterface::class, get_class($container));
        return $container;
    }
}

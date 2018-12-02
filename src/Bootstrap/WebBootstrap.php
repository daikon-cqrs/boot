<?php

declare(strict_types=1);

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

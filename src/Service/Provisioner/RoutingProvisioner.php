<?php

declare(strict_types=1);

namespace Oroshi\Core\Service\Provisioner;

use Aura\Router\RouterContainer;
use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Oroshi\Core\Config\RoutingConfigLoader;
use Oroshi\Core\Service\ServiceDefinitionInterface;

final class RoutingProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $appContext = $configProvider->get('app.context');
        $appEnv = $configProvider->get('app.env');
        $appConfigDir = $configProvider->get('app.config_dir');

        (new RoutingConfigLoader($router = new RouterContainer, $configProvider))->load(
            array_merge(
                [ $appConfigDir ],
                $configProvider->get('crates.*.config_dir')
            ),
            [
                'routing.php',
                "routing.$appContext.php",
                "routing.$appEnv.php",
                "routing.$appContext.$appEnv.php"
            ]
        );

        $serviceClass = $serviceDefinition->getServiceClass();
        $injector
            ->define($serviceClass, [ ':router' => $router ])
            ->share($serviceClass);
    }
}

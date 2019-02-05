<?php

declare(strict_types=1);

namespace Oroshi\Core\Service\Provisioner;

use Auryn\Injector;
use Daikon\Dbal\Connector\ConnectorMap;
use Daikon\Config\ConfigProviderInterface;
use Oroshi\Core\Fixture\FixtureLoaderMap;
use Oroshi\Core\Fixture\FixtureTarget;
use Oroshi\Core\Fixture\FixtureTargetMap;
use Oroshi\Core\Service\ServiceDefinitionInterface;

final class FixtureTargetMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $loaderConfigs = $configProvider->get('fixtures.fixture_loaders', []);
        $targetConfigs = $configProvider->get('fixtures.fixture_targets', []);

        $this->delegateLoaderMap($injector, $loaderConfigs);
        $this->delegateTargetMap($injector, $targetConfigs);
    }

    private function delegateLoaderMap(Injector $injector, array $loaderConfigs): void
    {
        $factory = function (ConnectorMap $connectorMap) use (
            $injector,
            $loaderConfigs
        ): FixtureLoaderMap {
            $fixtureLoaders = [];
            foreach ($loaderConfigs as $loaderName => $loaderConfig) {
                $fixtureLoader = $injector->make(
                    $loaderConfig['class'],
                    [
                        ':connector' => $connectorMap->get($loaderConfig['connector']),
                        ':settings' => $loaderConfig['settings'] ?? []
                    ]
                );
                $fixtureLoaders[$loaderName] = $fixtureLoader;
            }
            return new FixtureLoaderMap($fixtureLoaders);
        };

        $injector->delegate(FixtureLoaderMap::class, $factory)->share(FixtureLoaderMap::class);
    }

    private function delegateTargetMap(Injector $injector, array $targetConfigs): void
    {
        $factory = function (FixtureLoaderMap $loaderMap) use (
            $injector,
            $targetConfigs
        ): FixtureTargetMap {
            $fixtureTargets = [];
            foreach ($targetConfigs as $targetName => $targetConfig) {
                $fixtureTarget = $injector->make(
                    FixtureTarget::class,
                    [
                        ':name' => $targetName,
                        ':enabled' => $targetConfig['enabled'],
                        ':fixtureLoader' => $loaderMap->get($targetConfig['fixture_loader'])
                    ]
                );
                $fixtureTargets[$targetName] = $fixtureTarget;
            }
            return new FixtureTargetMap($fixtureTargets);
        };

        $injector->delegate(FixtureTargetMap::class, $factory)->share(FixtureTargetMap::class);
    }
}

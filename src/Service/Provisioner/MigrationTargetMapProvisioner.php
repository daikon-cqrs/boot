<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Service\Provisioner;

use Auryn\Injector;
use Daikon\Boot\Service\ServiceDefinitionInterface;
use Daikon\Config\ConfigProviderInterface;
use Daikon\Dbal\Connector\ConnectorMap;
use Daikon\Dbal\Migration\MigrationAdapterMap;
use Daikon\Dbal\Migration\MigrationLoaderMap;
use Daikon\Dbal\Migration\MigrationTarget;
use Daikon\Dbal\Migration\MigrationTargetMap;
use Daikon\Interop\Assertion;

final class MigrationTargetMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $loaderConfigs = (array)$configProvider->get('migrations.migration_loaders', []);
        $adapterConfigs = (array)$configProvider->get('migrations.migration_adapters', []);
        $targetConfigs = (array)$configProvider->get('migrations.migration_targets', []);

        $this->delegateLoaderMap($injector, $loaderConfigs);
        $this->delegateAdapterMap($injector, $adapterConfigs);
        $this->delegateTargetMap($injector, $targetConfigs);
    }

    private function delegateLoaderMap(Injector $injector, array $loaderConfigs): void
    {
        $factory = function (ConnectorMap $connectorMap) use (
            $injector,
            $loaderConfigs
        ): MigrationLoaderMap {
            $loaders = [];
            foreach ($loaderConfigs as $loaderKey => $loaderConfig) {
                Assertion::keyNotExists($loaders, $loaderKey, "Migration loader '$loaderKey' is already defined.");
                $loaders[$loaderKey] = $injector->make(
                    $loaderConfig['class'],
                    [
                        ':connector' => $connectorMap->get($loaderConfig['connector']),
                        ':settings' => $loaderConfig['settings'] ?? []
                    ]
                );
            }
            return new MigrationLoaderMap($loaders);
        };

        $injector->delegate(MigrationLoaderMap::class, $factory)->share(MigrationLoaderMap::class);
    }

    private function delegateAdapterMap(Injector $injector, array $adapterConfigs): void
    {
        $factory = function (ConnectorMap $connectorMap) use ($injector, $adapterConfigs): MigrationAdapterMap {
            $adapters = [];
            foreach ($adapterConfigs as $adapterKey => $adapterConfig) {
                Assertion::keyNotExists($adapters, $adapterKey, "Migration adapter '$adapterKey' is already defined.");
                $adapters[$adapterKey] = $injector->make(
                    $adapterConfig['class'],
                    [
                        ':connector' => $connectorMap->get($adapterConfig['connector']),
                        ':settings' => $adapterConfig['settings'] ?? []
                    ]
                );
            }
            return new MigrationAdapterMap($adapters);
        };

        $injector->delegate(MigrationAdapterMap::class, $factory)->share(MigrationAdapterMap::class);
    }

    private function delegateTargetMap(Injector $injector, array $targetConfigs): void
    {
        $factory = function (
            MigrationAdapterMap $adapterMap,
            MigrationLoaderMap $loaderMap
        ) use (
            $injector,
            $targetConfigs
        ): MigrationTargetMap {
            $targets = [];
            foreach ($targetConfigs as $targetKey => $targetConfig) {
                Assertion::keyNotExists($targets, $targetKey, "Migration target '$targetKey' is already defined.");
                $targets[$targetKey] = $injector->make(
                    MigrationTarget::class,
                    [
                        ':key' => $targetKey,
                        ':enabled' => $targetConfig['enabled'],
                        ':migrationAdapter' => $adapterMap->get($targetConfig['migration_adapter']),
                        ':migrationLoader' => $loaderMap->get($targetConfig['migration_loader'])
                    ]
                );
            }
            return new MigrationTargetMap($targets);
        };

        $injector->delegate(MigrationTargetMap::class, $factory)->share(MigrationTargetMap::class);
    }
}

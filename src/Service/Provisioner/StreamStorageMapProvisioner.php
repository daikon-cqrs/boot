<?php

declare(strict_types=1);

namespace Oroshi\Core\Service\Provisioner;

use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Daikon\Dbal\Storage\StorageAdapterMap;
use Oroshi\Core\Common\StreamStorageMap;
use Oroshi\Core\Service\ServiceDefinitionInterface;

final class StreamStorageMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $adapterConfigs = $configProvider->get('databases.stream_stores', []);
        $factory = function (
            StorageAdapterMap $storageAdapterMap
        ) use (
            $injector,
            $adapterConfigs
        ): StreamStorageMap {
            $adapters = [];
            foreach ($adapterConfigs as $adapterName => $adapterConfigs) {
                $adapterClass = $adapterConfigs['class'];
                $adapters[$adapterName] = $injector->make(
                    $adapterClass,
                    [':storageAdapter' => $storageAdapterMap->get($adapterConfigs['storage_adapter'])]
                );
            }
            return new StreamStorageMap($adapters);
        };

        $injector
            ->share(StreamStorageMap::class)
            ->delegate(StreamStorageMap::class, $factory);
    }
}

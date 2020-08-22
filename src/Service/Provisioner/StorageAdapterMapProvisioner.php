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
use Daikon\Dbal\Storage\StorageAdapterMap;
use Daikon\Interop\Assertion;

final class StorageAdapterMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $adapterConfigs = (array)$configProvider->get('databases.storage_adapters', []);
        $factory = function (ConnectorMap $connectorMap) use ($injector, $adapterConfigs): StorageAdapterMap {
            $adapters = [];
            foreach ($adapterConfigs as $adapterKey => $adapterConfigs) {
                Assertion::keyNotExists($adapters, $adapterKey, "Storage adapter '$adapterKey' is already defined.");
                $adapters[$adapterKey] = $injector->make(
                    $adapterConfigs['class'],
                    [
                        ':connector' => $connectorMap->get($adapterConfigs['connector']),
                        ':settings' => $adapterConfigs['settings'] ?? []
                    ]
                );
            }
            return new StorageAdapterMap($adapters);
        };

        $injector
            ->share(StorageAdapterMap::class)
            ->delegate(StorageAdapterMap::class, $factory);
    }
}

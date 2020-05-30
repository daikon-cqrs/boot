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
use Daikon\Dbal\Storage\StorageAdapterMap;

final class StreamStorageMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $mapClass = $serviceDefinition->getServiceClass();
        $adapterConfigs = (array)$configProvider->get('databases.stream_stores', []);
        $factory = function (
            StorageAdapterMap $storageAdapterMap
        ) use (
            $injector,
            $mapClass,
            $adapterConfigs
        ): object {
            $adapters = [];
            foreach ($adapterConfigs as $adapterName => $adapterConfigs) {
                $adapterClass = $adapterConfigs['class'];
                $adapters[$adapterName] = $injector->make(
                    $adapterClass,
                    [':storageAdapter' => $storageAdapterMap->get($adapterConfigs['storage_adapter'])]
                );
            }
            return new $mapClass($adapters);
        };

        $injector
            ->share($mapClass)
            ->delegate($mapClass, $factory);
    }
}

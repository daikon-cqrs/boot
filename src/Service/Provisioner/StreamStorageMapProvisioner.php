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
use Daikon\Interop\Assertion;

final class StreamStorageMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $serviceClass = $serviceDefinition->getServiceClass();
        $adapterConfigs = (array)$configProvider->get('databases.stream_stores', []);
        $factory = function (
            StorageAdapterMap $storageAdapterMap
        ) use (
            $injector,
            $serviceClass,
            $adapterConfigs
        ): object {
            $adapters = [];
            foreach ($adapterConfigs as $adapterKey => $adapterConfigs) {
                Assertion::keyNotExists($adapters, $adapterKey, "Stream adapter '$adapterKey' is already defined.");
                $adapters[$adapterKey] = $injector->make(
                    $adapterConfigs['class'],
                    [':storageAdapter' => $storageAdapterMap->get($adapterConfigs['storage_adapter'])]
                );
            }
            return new $serviceClass($adapters);
        };

        $injector
            ->share($serviceClass)
            ->delegate($serviceClass, $factory);
    }
}

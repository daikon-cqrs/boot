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

final class RepositoryMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $serviceClass = $serviceDefinition->getServiceClass();
        $repositoryConfigs = (array)$configProvider->get('databases.repositories', []);

        $factory = function (StorageAdapterMap $storageAdapterMap) use (
            $injector,
            $repositoryConfigs,
            $serviceClass
        ): object {
            $repositories = [];
            foreach ($repositoryConfigs as $repositoryKey => $repositoryConfig) {
                Assertion::keyNotExists(
                    $repositories,
                    $repositoryKey,
                    "Repository '$repositoryKey' is already defined."
                );
                $dependencies = [':storageAdapter' => $storageAdapterMap->get($repositoryConfig['storage_adapter'])];
                if (isset($repositoryConfig['search_adapter'])) {
                    $dependencies[':searchAdapter'] = $storageAdapterMap->get($repositoryConfig['search_adapter']);
                }
                $repositories[$repositoryKey] = $injector->make($repositoryConfig['class'], $dependencies);
            }
            return new $serviceClass($repositories);
        };

        $injector
            ->share($serviceClass)
            ->delegate($serviceClass, $factory);
    }
}

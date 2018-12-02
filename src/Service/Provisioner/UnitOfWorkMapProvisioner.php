<?php

declare(strict_types=1);

namespace Oroshi\Core\Service\Provisioner;

use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Daikon\EventSourcing\EventStore\UnitOfWork;
use Oroshi\Core\Common\StreamStorageMap;
use Oroshi\Core\Common\UnitOfWorkMap;
use Oroshi\Core\Service\ServiceDefinitionInterface;

final class UnitOfWorkMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $uowConfigs = $configProvider->get('databases.units_of_work', []);
        $factory = function (StreamStorageMap $streamStorageMap) use ($uowConfigs): UnitOfWorkMap {
            $unitsOfWork = [];
            foreach ($uowConfigs as $uowName => $uowConfig) {
                $unitsOfWork[$uowName] = new UnitOfWork(
                    $uowConfig['aggregate_root'],
                    $streamStorageMap->get($uowConfig['stream_store'])
                );
            }
            return new UnitOfWorkMap($unitsOfWork);
        };

        $injector
            ->share(UnitOfWorkMap::class)
            ->delegate(UnitOfWorkMap::class, $factory);
    }
}

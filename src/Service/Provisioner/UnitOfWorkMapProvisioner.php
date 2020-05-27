<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Service\Provisioner;

use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Daikon\EventSourcing\EventStore\Storage\StreamStorageInterface;
use Daikon\EventSourcing\EventStore\Storage\StreamStorageMap;
use Daikon\EventSourcing\EventStore\UnitOfWork;
use Oroshi\Core\Service\ServiceDefinitionInterface;

final class UnitOfWorkMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $mapClass = $serviceDefinition->getServiceClass();
        $uowConfigs = (array)$configProvider->get('databases.units_of_work', []);
        $factory = function (StreamStorageMap $streamStorageMap) use ($uowConfigs, $mapClass): object {
            $unitsOfWork = [];
            foreach ($uowConfigs as $uowName => $uowConfig) {
                /** @var StreamStorageInterface $streamStorage */
                $streamStorage = $streamStorageMap->get((string)$uowConfig['stream_store']);
                $unitsOfWork[$uowName] = new UnitOfWork($uowConfig['aggregate_root'], $streamStorage);
            }
            return new $mapClass($unitsOfWork);
        };

        $injector
            ->share($mapClass)
            ->delegate($mapClass, $factory);
    }
}

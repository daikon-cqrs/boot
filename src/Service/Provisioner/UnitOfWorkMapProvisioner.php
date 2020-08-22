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
use Daikon\EventSourcing\EventStore\Storage\StreamStorageInterface;
use Daikon\EventSourcing\EventStore\Storage\StreamStorageMap;
use Daikon\EventSourcing\EventStore\UnitOfWork;
use Daikon\Interop\Assertion;

final class UnitOfWorkMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $serviceClass = $serviceDefinition->getServiceClass();
        $uowConfigs = (array)$configProvider->get('databases.units_of_work', []);
        $factory = function (StreamStorageMap $streamStorageMap) use ($uowConfigs, $serviceClass): object {
            $unitsOfWork = [];
            foreach ($uowConfigs as $uowKey => $uowConfig) {
                Assertion::keyNotExists($unitsOfWork, $uowKey, "Unit of work '$uowKey' is already defined.");
                /** @var StreamStorageInterface $streamStorage */
                $streamStorage = $streamStorageMap->get((string)$uowConfig['stream_store']);
                $unitsOfWork[$uowKey] = new UnitOfWork($uowConfig['aggregate_root'], $streamStorage);
            }
            return new $serviceClass($unitsOfWork);
        };

        $injector
            ->share($serviceClass)
            ->delegate($serviceClass, $factory);
    }
}

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

final class ConnectorMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $connectorConfigs = (array)$configProvider->get('connectors', []);
        $injector
            ->share(ConnectorMap::class)
            ->delegate(
                ConnectorMap::class,
                $this->factory($injector, $connectorConfigs)
            );
    }

    private function factory(Injector $injector, array $connectorConfigs): callable
    {
        return function () use ($injector, $connectorConfigs): ConnectorMap {
            $connectors = [];
            foreach ($connectorConfigs as $connectorName => $connectorConfig) {
                if (isset($connectorConfig['connector'])) {
                    $connectorConfig = array_replace_recursive(
                        $connectorConfigs[$connectorConfig['connector']],
                        $connectorConfig
                    );
                }
                $connectorClass = $connectorConfig['class'];
                $connectors[$connectorName] = $injector->define(
                    $connectorClass,
                    [':settings' => $connectorConfig['settings'] ?? []]
                )->make($connectorClass);
            }
            return new ConnectorMap($connectors);
        };
    }
}

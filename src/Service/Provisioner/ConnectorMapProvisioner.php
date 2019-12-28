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
use Daikon\Dbal\Connector\ConnectorMap;
use Oroshi\Core\Service\ServiceDefinitionInterface;

final class ConnectorMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $injector
            ->share(ConnectorMap::class)
            ->delegate(
                ConnectorMap::class,
                $this->factory(
                    $injector,
                    $configProvider->get('connectors', [])
                )
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

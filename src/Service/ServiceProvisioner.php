<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Service;

use Auryn\ConfigException;
use Auryn\Injector;
use Daikon\Boot\Service\Provisioner\ProvisionerInterface;
use Daikon\Config\ConfigProviderInterface;
use Psr\Container\ContainerInterface;

final class ServiceProvisioner implements ServiceProvisionerInterface
{
    public function provision(Injector $injector, ConfigProviderInterface $configProvider): ContainerInterface
    {
        $serviceDefinitionMap = $this->buildServiceDefinitionMap($configProvider);
        $injector->share($serviceDefinitionMap);
        return new Container($this->prepareServices(
            $injector,
            $configProvider,
            $serviceDefinitionMap
        ));
    }

    private function prepareServices(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionMap $serviceDefinitionMap
    ): Injector {
        foreach ($serviceDefinitionMap as $serviceDefinition) {
            $provisionerClass = $serviceDefinition->getProvisionerClass();
            $provisioner = $injector->make($provisionerClass);
            if ($provisioner instanceof ProvisionerInterface) {
                $provisioner->provision($injector, $configProvider, $serviceDefinition);
            } else {
                throw new ConfigException(
                    sprintf('Provisioner %s must implement %s', $provisionerClass, ProvisionerInterface::class)
                );
            }
        }
        return $injector;
    }

    private function buildServiceDefinitionMap(ConfigProviderInterface $configProvider): ServiceDefinitionMap
    {
        $serviceDefinitions = [];
        $serviceConfigs = $configProvider->get('services');
        foreach ($serviceConfigs as $namespace => $namespaceDefinitions) {
            foreach ($namespaceDefinitions as $serviceName => $serviceDefinition) {
                $serviceKey = sprintf('%s.%s', $namespace, $serviceName);
                $serviceDefinitions[$serviceKey] = new ServiceDefinition(
                    $serviceDefinition['class'],
                    $serviceDefinition['provisioner'] ?? null,
                    $serviceDefinition['settings'] ?? [],
                    $serviceDefinition['subscriptions'] ?? []
                );
            }
        }
        return new ServiceDefinitionMap($serviceDefinitions);
    }
}

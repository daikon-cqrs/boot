<?php

declare(strict_types=1);

namespace Oroshi\Core\Service;

use Auryn\ConfigException;
use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Psr\Container\ContainerInterface;
use Oroshi\Core\Service\Provisioner\ProvisionerInterface;

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
        foreach ($serviceDefinitionMap->getIterator() as $serviceDefinition) {
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

<?php

declare(strict_types=1);

namespace Oroshi\Core\Service\Provisioner;

use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Oroshi\Core\Service\ServiceDefinitionInterface;

interface ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void;
}

<?php

declare(strict_types=1);

namespace Oroshi\Core\Service;

use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Psr\Container\ContainerInterface;

interface ServiceProvisionerInterface
{
    public function provision(Injector $injector, ConfigProviderInterface $configProvider): ContainerInterface;
}

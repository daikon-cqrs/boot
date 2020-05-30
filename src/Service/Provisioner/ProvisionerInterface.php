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

interface ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void;
}

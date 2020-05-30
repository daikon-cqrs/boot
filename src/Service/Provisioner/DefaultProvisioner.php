<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Service\Provisioner;

use Auryn\Injector;
use Daikon\Boot\Exception\ConfigException;
use Daikon\Boot\Service\ServiceDefinitionInterface;
use Daikon\Config\ConfigProviderInterface;

final class DefaultProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $serviceClass = $serviceDefinition->getServiceClass();
        $settings = $serviceDefinition->getSettings();

        $injector->define($serviceClass, [':settings' => $settings]);

        // there will only be one instance of the service when the "share" setting is true (default)
        if (!isset($settings['_share']) || true === $settings['_share']) {
            $injector->share($serviceClass);
        }

        if (isset($settings['_alias'])) {
            $alias = $settings['_alias'];
            if (!is_string($alias) && !class_exists($alias)) {
                throw new ConfigException('Alias must be an existing fully qualified class or interface name.');
            }
            $injector->alias($alias, $serviceClass);
        }
    }
}

<?php

declare(strict_types=1);

namespace Oroshi\Core\Service\Provisioner;

use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Oroshi\Core\Exception\ConfigException;
use Oroshi\Core\Service\ServiceDefinitionInterface;
use Psr\Log\LoggerInterface;

final class MonologProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $className = $serviceDefinition->getServiceClass();
        $settings = $serviceDefinition->getSettings();
        if (!isset($settings['location'])) {
            throw new ConfigException('Please provide a logging service output location.');
        }
        $settings['level'] = $settings['level'] ?? Logger::INFO;
        $settings['name'] = $settings['name'] ?? 'default-logger';

        $injector
            ->alias(LoggerInterface::class, $className)
            ->share($className)
            ->delegate(
                $className,
                function () use ($className, $settings): Logger {
                    /** @var Logger $logger */
                    $logger = new $className($settings['name']);
                    $logger->pushHandler(
                        new StreamHandler($settings['location'], $settings['level'])
                    );
                    return $logger;
                }
            );
    }
}

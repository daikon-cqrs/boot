<?php

declare(strict_types=1);

namespace Oroshi\Core\Service\Provisioner;

use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Oroshi\Core\Exception\ConfigException;
use Oroshi\Core\Logging\LoggingService;
use Oroshi\Core\Service\ServiceDefinitionInterface;

final class MonologProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $settings = $serviceDefinition->getSettings();
        if (!isset($settings['location'])) {
            throw new ConfigException('Please provide a logging service output location.');
        }
        $settings['level'] = $settings['level'] ?? Logger::INFO;
        $settings['name'] = $settings['name'] ?? 'default-logger';

        $injector
            ->alias(LoggerInterface::class, LoggingService::class)
            ->share(LoggingService::class)
            ->delegate(
                LoggingService::class,
                function () use ($settings): LoggingService {
                    $logger = new Logger($settings['name']);
                    $logger->pushHandler(
                        new StreamHandler($settings['location'], $settings['level'])
                    );
                    return new LoggingService($logger);
                }
            );
    }
}

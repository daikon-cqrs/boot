<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Service\Provisioner;

use Auryn\Injector;
use Daikon\Boot\MessageBus\CommandRouter;
use Daikon\Boot\Service\ServiceDefinitionInterface;
use Daikon\Config\ConfigProviderInterface;
use Daikon\EventSourcing\Aggregate\Command\CommandHandler;
use Daikon\EventSourcing\EventStore\UnitOfWorkMap;

final class CommandRouterProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $commandConfigs = (array)$configProvider->get('services.daikon.command_router.commands', []);
        $injector
            ->share(CommandRouter::class)
            ->delegate(
                CommandRouter::class,
                function (UnitOfWorkMap $uowMap) use ($injector, $commandConfigs): CommandRouter {
                    $handlerMap = [];
                    foreach ($commandConfigs as $uowKey => $registeredHandlers) {
                        foreach ($registeredHandlers as $commandFqcn => $handlerFqcn) {
                            $handlerMap[$commandFqcn] = fn(): CommandHandler => $injector->make(
                                $handlerFqcn,
                                [':unitOfWork' => $uowMap->get($uowKey)]
                            );
                        }
                    }
                    return new CommandRouter($handlerMap);
                }
            );
    }
}

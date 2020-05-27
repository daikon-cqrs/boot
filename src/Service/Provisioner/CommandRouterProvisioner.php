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
use Daikon\EventSourcing\Aggregate\Command\CommandHandler;
use Daikon\EventSourcing\EventStore\UnitOfWorkMap;
use Oroshi\Core\MessageBus\CommandRouter;
use Oroshi\Core\Service\ServiceDefinitionInterface;

final class CommandRouterProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $commandConfigs = (array)$configProvider->get('services.oroshi.command_router.commands', []);
        $injector
            ->share(CommandRouter::class)
            ->delegate(
                CommandRouter::class,
                $this->factory($injector, $commandConfigs)
            );
    }

    private function factory(Injector $injector, array $cmdRoutingConfig): callable
    {
        return function (UnitOfWorkMap $uowMap) use ($injector, $cmdRoutingConfig): CommandRouter {
            $handlerMap = [];
            foreach ($cmdRoutingConfig as $uowKey => $registeredHandlers) {
                foreach ($registeredHandlers as $commandFqcn => $handlerFqcn) {
                    $handlerMap[$commandFqcn] = function () use (
                        $injector,
                        $handlerFqcn,
                        $uowMap,
                        $uowKey
                    ): CommandHandler {
                        return $injector->make(
                            $handlerFqcn,
                            [':unitOfWork' => $uowMap->get($uowKey)]
                        );
                    };
                }
            }
            return new CommandRouter($handlerMap);
        };
    }
}

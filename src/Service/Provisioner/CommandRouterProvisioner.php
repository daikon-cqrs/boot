<?php

declare(strict_types=1);

namespace Oroshi\Core\Service\Provisioner;

use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Daikon\EventSourcing\Aggregate\Command\CommandHandler;
use Oroshi\Core\Common\UnitOfWorkMap;
use Oroshi\Core\MessageBus\CommandRouter;
use Oroshi\Core\Service\ServiceDefinitionInterface;

final class CommandRouterProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $injector
            ->share(CommandRouter::class)
            ->delegate(
                CommandRouter::class,
                $this->factory(
                    $injector,
                    $configProvider->get('services.oroshi.command_router.commands', [])
                )
            );
    }

    private function factory(Injector $injector, array $cmdRoutingConfig): callable
    {
        return function (UnitOfWorkMap $uowMap) use ($injector, $cmdRoutingConfig): CommandRouter {
            $handlerMap = [];
            foreach ($cmdRoutingConfig as $uowKey => $handlerMap) {
                foreach ($handlerMap as $commandFqcn => $handlerFqcn) {
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

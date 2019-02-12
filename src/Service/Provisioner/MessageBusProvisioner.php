<?php

declare(strict_types=1);

namespace Oroshi\Core\Service\Provisioner;

use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Daikon\Dbal\Connector\ConnectorMap;
use Daikon\MessageBus\Channel\Channel;
use Daikon\MessageBus\Channel\ChannelMap;
use Daikon\MessageBus\Channel\Subscription\LazySubscription;
use Daikon\MessageBus\Channel\Subscription\MessageHandler\MessageHandlerList;
use Daikon\MessageBus\Channel\Subscription\SubscriptionMap;
use Daikon\MessageBus\Channel\Subscription\Transport\TransportInterface;
use Daikon\MessageBus\Channel\Subscription\Transport\TransportMap;
use Daikon\MessageBus\MessageBusInterface;
use Daikon\MessageBus\Metadata\MetadataEnricherList;
use Psr\Container\ContainerInterface;
use Oroshi\Core\Exception\ConfigException;
use Oroshi\Core\Service\ServiceDefinition;
use Oroshi\Core\Service\ServiceDefinitionInterface;
use Oroshi\Core\Service\ServiceDefinitionMap;

final class MessageBusProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $settings = $serviceDefinition->getSettings();
        if (!isset($settings['transports'])) {
            throw new ConfigException('Message bus transports configuration is required.');
        }
        $serviceClass = $serviceDefinition->getServiceClass();
        $injector
            ->delegate($serviceClass, $this->factory($injector, $serviceDefinition))
            ->share($serviceClass)
            ->alias(MessageBusInterface::class, $serviceClass);
    }

    private function factory(Injector $injector, ServiceDefinitionInterface $serviceDefinition): callable
    {
        return function (
            ConnectorMap $connectorMap,
            ServiceDefinitionMap $serviceDefinitionMap
        ) use (
            $injector,
            $serviceDefinition
        ): object {
            $settings = $serviceDefinition->getSettings();
            $transportMap = $this->buildTransportMap($injector, $serviceDefinition, $connectorMap);
            $channelSubs = $this->collectChannelSubscriptions($injector, $serviceDefinitionMap, $transportMap);
            $channels = [];
            foreach ($channelSubs as $channelName => $subscriptions) {
                $channels[$channelName] = new Channel($channelName, new SubscriptionMap($subscriptions));
            }
            $serviceClass = $serviceDefinition->getServiceClass();
            return new $serviceClass(new ChannelMap($channels));
        };
    }

    private function buildTransportMap(
        Injector $injector,
        ServiceDefinitionInterface $serviceDefinition,
        ConnectorMap $connectorMap
    ): TransportMap {
        $transports = [];
        $settings = $serviceDefinition->getSettings();
        foreach ($settings['transports'] as $transportName => $transportConfig) {
            $transportClass = $transportConfig['class'];
            $arguments = [':key' => $transportName];
            if (isset($transportConfig['dependencies']['connector'])) {
                $arguments[':connector'] = $connectorMap->get($transportConfig['dependencies']['connector']);
            }
            $transports[$transportName] = $injector->make($transportClass, $arguments);
        }
        return new TransportMap($transports);
    }

    private function collectChannelSubscriptions(
        Injector $injector,
        ServiceDefinitionMap $serviceDefinitionMap,
        TransportMap $transportMap
    ): array {
        $channelSubs = ['commands' => [], 'commits' => [], 'events' => []];
        foreach ($serviceDefinitionMap as $serviceDefinition) {
            $this->registerServiceSubs($injector, $serviceDefinition, $transportMap, $channelSubs);
        }
        return $channelSubs;
    }

    private function registerServiceSubs(
        Injector $injector,
        ServiceDefinitionInterface $serviceDefinition,
        TransportMap $transportMap,
        array &$channelSubs
    ): void {
        foreach ($serviceDefinition->getSubscriptions() as $subscriptionName => $subscriptionConfig) {
            $channelName = $subscriptionConfig['channel'];
            $transportName = $subscriptionConfig['transport'];
            if (!$transportMap->has($transportName)) {
                throw new ConfigException(
                    sprintf('Message bus transport "%s" has not been configured.', $transportName)
                );
            }
            $channelSubs[$channelName][] = $this->buildLazySubscription(
                $injector,
                $serviceDefinition->getServiceClass(),
                $subscriptionName,
                $subscriptionConfig,
                $transportMap
            );
        }
    }

    private function buildLazySubscription(
        Injector $injector,
        string $serviceFqcn,
        string $subscriptionName,
        array $subscriptionConfig,
        TransportMap $transportMap
    ): LazySubscription {
        $transportName = $subscriptionConfig['transport'];
        return new LazySubscription(
            $subscriptionName,
            function () use ($transportMap, $transportName): TransportInterface {
                return $transportMap->get($transportName);
            },
            function () use ($injector, $serviceFqcn): MessageHandlerList {
                return new MessageHandlerList([$injector->make($serviceFqcn)]);
            },
            null,
            function () use ($injector, $subscriptionConfig): MetadataEnricherList {
                $enrichers = [];
                foreach ($subscriptionConfig['enrichers'] ?? [] as $enricherConfig) {
                    $enricherClass = $enricherConfig['class'];
                    $enrichers[] = $injector->make(
                        $enricherClass,
                        [':settings' => $enricherConfig['settings'] ?? []]
                    );
                }
                return new MetadataEnricherList($enrichers);
            }
        );
    }
}

<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Service\Provisioner;

use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Daikon\Boot\Exception\ConfigException;
use Daikon\Boot\Service\ServiceDefinitionInterface;
use Daikon\Boot\Service\ServiceDefinitionMap;
use Daikon\Dbal\Connector\ConnectorMap;
use Daikon\MessageBus\EnvelopeInterface;
use Daikon\MessageBus\Channel\Channel;
use Daikon\MessageBus\Channel\ChannelMap;
use Daikon\MessageBus\Channel\Subscription\LazySubscription;
use Daikon\MessageBus\Channel\Subscription\MessageHandler\MessageHandlerList;
use Daikon\MessageBus\Channel\Subscription\SubscriptionMap;
use Daikon\MessageBus\Channel\Subscription\Transport\TransportInterface;
use Daikon\MessageBus\Channel\Subscription\Transport\TransportMap;
use Daikon\MessageBus\MessageBusInterface;
use Daikon\Metadata\MetadataEnricherList;

final class MessageBusProvisioner implements ProvisionerInterface
{
    public const COMMANDS_CHANNEL = 'commands';

    public const COMMITS_CHANNEL = 'commits';

    public const EVENTS_CHANNEL = 'events';

    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $serviceClass = $serviceDefinition->getServiceClass();
        $settings = $serviceDefinition->getSettings();

        if (!isset($settings['transports'])) {
            throw new ConfigException('Message bus transports configuration is required.');
        }

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
        $channelSubs = [self::COMMANDS_CHANNEL => [], self::COMMITS_CHANNEL => [], self::EVENTS_CHANNEL => []];
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
        $guards = (array)($subscriptionConfig['guards'] ?? []);
        return new LazySubscription(
            $subscriptionName,
            /**
             * @psalm-suppress InvalidNullableReturnType
             * @psalm-suppress NullableReturnStatement
             */
            fn(): TransportInterface => $transportMap->get($transportName),
            fn(): MessageHandlerList => new MessageHandlerList([$injector->make($serviceFqcn)]),
            function (EnvelopeInterface $envelope) use ($guards): bool {
                $message = $envelope->getMessage();
                $interfaces = class_implements($message);
                foreach ($guards as $guard) {
                    if ($message instanceof $guard || isset($interfaces[$guard])) {
                        return true;
                    }
                }
                return false;
            },
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

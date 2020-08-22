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
use Daikon\Boot\Service\ServiceDefinitionInterface;
use Daikon\Boot\Service\ServiceDefinitionMap;
use Daikon\Dbal\Connector\ConnectorMap;
use Daikon\Interop\Assertion;
use Daikon\Interop\RuntimeException;
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
            throw new RuntimeException('Message bus transports configuration is required.');
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
            foreach ($channelSubs as $channelKey => $subscriptions) {
                Assertion::keyNotExists($channels, $channelKey, "Channel '$channelKey' is already defined.");
                $channels[$channelKey] = new Channel($channelKey, new SubscriptionMap($subscriptions));
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
        foreach ($settings['transports'] as $transportKey => $transportConfig) {
            Assertion::keyNotExists($transports, $transportKey, "Transport '$transportKey' is already defined.");
            $arguments = [':key' => $transportKey];
            if (isset($transportConfig['dependencies']['connector'])) {
                $arguments[':connector'] = $connectorMap->get($transportConfig['dependencies']['connector']);
            }
            $transports[$transportKey] = $injector->make($transportConfig['class'], $arguments);
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
        foreach ($serviceDefinition->getSubscriptions() as $subscriptionKey => $subscriptionConfig) {
            $channelKey = $subscriptionConfig['channel'];
            Assertion::keyNotExists(
                $channelSubs[$channelKey],
                $subscriptionKey,
                "Subscription '$subscriptionKey' is already defined on channel '$channelKey'."
            );
            $transportKey = $subscriptionConfig['transport'];
            if (!$transportMap->has($transportKey)) {
                throw new RuntimeException("Message bus transport '$transportKey' has not been configured.");
            }
            $channelSubs[$channelKey][$subscriptionKey] = $this->buildLazySubscription(
                $injector,
                $serviceDefinition->getServiceClass(),
                $subscriptionKey,
                $subscriptionConfig,
                $transportMap
            );
        }
    }

    private function buildLazySubscription(
        Injector $injector,
        string $serviceFqcn,
        string $subscriptionKey,
        array $subscriptionConfig,
        TransportMap $transportMap
    ): LazySubscription {
        $transportKey = $subscriptionConfig['transport'];
        $guards = (array)($subscriptionConfig['guards'] ?? []);
        return new LazySubscription(
            $subscriptionKey,
            /**
             * @psalm-suppress InvalidNullableReturnType
             * @psalm-suppress NullableReturnStatement
             */
            fn(): TransportInterface => $transportMap->get($transportKey),
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
                    $enrichers[] = $injector->make(
                        $enricherConfig['class'],
                        [':settings' => $enricherConfig['settings'] ?? []]
                    );
                }
                return new MetadataEnricherList($enrichers);
            }
        );
    }
}

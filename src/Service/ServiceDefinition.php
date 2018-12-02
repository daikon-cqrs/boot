<?php

declare(strict_types=1);

namespace Oroshi\Core\Service;

use Oroshi\Core\Service\Provisioner\DefaultProvisioner;

final class ServiceDefinition implements ServiceDefinitionInterface
{
    /** @var string */
    private $serviceClass;

    /** @var string */
    private $provisionerClass;

    /** @var array */
    private $settings;

    /** @var array */
    private $subscriptions;

    public function __construct(
        string $serviceClass,
        string $provisionerClass = null,
        array $settings = [],
        array $subscriptions = []
    ) {
        $this->serviceClass = $serviceClass;
        $this->provisionerClass = $provisionerClass ?? DefaultProvisioner::class;
        $this->settings = $settings;
        $this->subscriptions = $subscriptions;
    }

    public function getServiceClass(): string
    {
        return $this->serviceClass;
    }

    public function getProvisionerClass(): string
    {
        return $this->provisionerClass;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getSubscriptions(): array
    {
        return $this->subscriptions;
    }
}

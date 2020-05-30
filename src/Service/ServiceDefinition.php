<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Service;

use Daikon\Boot\Service\Provisioner\DefaultProvisioner;

final class ServiceDefinition implements ServiceDefinitionInterface
{
    private string $serviceClass;

    private string $provisionerClass;

    private array $settings;

    private array $subscriptions;

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

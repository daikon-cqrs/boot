<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Service\Provisioner;

use Auryn\Injector;
use Daikon\AsyncJob\Job\JobDefinitionMap;
use Daikon\AsyncJob\Strategy\JobStrategyMap;
use Daikon\AsyncJob\Worker\WorkerMap;
use Daikon\Config\ConfigProviderInterface;
use Daikon\Dbal\Connector\ConnectorMap;
use Oroshi\Core\Service\ServiceDefinitionInterface;

final class JobDefinitionMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $workerConfigs = $configProvider->get('jobs.job_workers', []);
        $strategyConfigs = $configProvider->get('jobs.job_strategies', []);
        $jobConfigs = $configProvider->get('jobs.jobs', []);

        $this->delegateJobStrategyMap($injector, $strategyConfigs);
        $this->delegateJobDefinitionMap($injector, $jobConfigs);
        $this->delegateWorkerMap($injector, $workerConfigs);
    }

    private function delegateJobDefinitionMap(Injector $injector, array $jobConfigs): void
    {
        $factory = function (JobStrategyMap $strategyMap) use ($injector, $jobConfigs): JobDefinitionMap {
            $jobs = [];
            foreach ($jobConfigs as $jobName => $jobConfig) {
                $jobs[$jobName] = $injector->make(
                    $jobConfig['class'],
                    [
                        ':jobStrategy' => $strategyMap->get($jobConfig['job_strategy']),
                        ':settings' => $jobConfig['settings'] ?? []
                    ]
                );
            }
            return new JobDefinitionMap($jobs);
        };

        $injector->share(JobDefinitionMap::class)->delegate(JobDefinitionMap::class, $factory);
    }

    private function delegateJobStrategyMap(Injector $injector, array $strategyConfigs): void
    {
        $factory = function () use ($injector, $strategyConfigs): JobStrategyMap {
            $strategies = [];
            foreach ($strategyConfigs as $strategyName => $strategyConfig) {
                $strategies[$strategyName] = $injector->make(
                    $strategyConfig['class'],
                    [
                        ':retryStrategy' => $injector->make(
                            $strategyConfig['retry']['class'],
                            [':settings' => $strategyConfig['retry']['settings'] ?? []]
                        ),
                        ':failureStrategy' => $injector->make(
                            $strategyConfig['failure']['class'],
                            [':settings' => $strategyConfig['failure']['settings'] ?? []]
                        )
                    ]
                );
            }
            return new JobStrategyMap($strategies);
        };

        $injector->share(JobStrategyMap::class)->delegate(JobStrategyMap::class, $factory);
    }

    private function delegateWorkerMap(Injector $injector, array $workerConfigs): void
    {
        $factory = function (
            ConnectorMap $connectorMap,
            JobDefinitionMap $jobDefinitionMap
        ) use (
            $injector,
            $workerConfigs
        ): WorkerMap {
            $workers = [];
            foreach ($workerConfigs as $workerName => $workerConfig) {
                $workers[$workerName] = $injector->make(
                    $workerConfig['class'],
                    [
                        ':connector' => $connectorMap->get($workerConfig['dependencies']['connector']),
                        ':jobDefinitionMap' => $jobDefinitionMap,
                        ':settings' => $workerConfig['settings'] ?? []
                    ]
                );
            }
            return new WorkerMap($workers);
        };

        $injector->share(WorkerMap::class)->delegate(WorkerMap::class, $factory);
    }
}

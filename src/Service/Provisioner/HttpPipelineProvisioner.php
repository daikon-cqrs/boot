<?php

declare(strict_types=1);

namespace Oroshi\Core\Service\Provisioner;

use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Middlewares\ContentEncoding;
use Middlewares\ContentLanguage;
use Middlewares\Whoops;
use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalyzerInterface;
use Neomerx\Cors\Strategies\Settings;
use Oroshi\Core\Middleware\PipelineBuilderInterface;
use Oroshi\Core\Service\ServiceDefinitionInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

final class HttpPipelineProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $serviceClass = $serviceDefinition->getServiceClass();
        $settings = $serviceDefinition->getSettings();

        $injector
            ->define($serviceClass, [':settings' => $settings])
            ->share($serviceClass)
            ->alias(PipelineBuilderInterface::class, $serviceClass)
            ->define(Whoops::class, [':whoops' => (new Run)->pushHandler(new PrettyPageHandler)])
            ->define(ContentLanguage::class, [':languages' => ['en', 'gl', 'es']])
            ->define(ContentEncoding::class, [':encodings' => ['gzip', 'deflate']])
            ->share(AnalyzerInterface::class)
            ->alias(AnalyzerInterface::class, Analyzer::class)
            ->delegate(Analyzer::class, function () use ($configProvider): AnalyzerInterface {
                $corsSettings = new Settings;
                $corsSettings->setServerOrigin([
                    'scheme' => 'http',
                    'host' => $configProvider->get('cors.host'),
                    'port' => $configProvider->get('cors.port'),
                ]);
                return Analyzer::instance($corsSettings);
            });
    }
}

<?php

declare(strict_types=1);

namespace Oroshi\Core\Service\Provisioner;

use Aura\Router\RouterContainer;
use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Middlewares\ContentEncoding;
use Middlewares\ContentLanguage;
use Middlewares\Whoops;
use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalyzerInterface;
use Neomerx\Cors\Contracts\Constants\CorsResponseHeaders;
use Neomerx\Cors\Strategies\Settings;
use Oroshi\Core\Config\RoutingConfigLoader;
use Oroshi\Core\Middleware\PipelineBuilderInterface;
use Oroshi\Core\Middleware\RoutingHandler;
use Oroshi\Core\Service\ServiceDefinitionInterface;
use Psr\Container\ContainerInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

final class HttpPipelineProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $config,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $serviceClass = $serviceDefinition->getServiceClass();
        $settings = $serviceDefinition->getSettings();

        $injector
            ->define($serviceClass, [':settings' => $settings])
            ->share($serviceClass)
            ->alias(PipelineBuilderInterface::class, $serviceClass)
            // Exception Handling
            ->define(Whoops::class, [':whoops' => (new Run)->pushHandler(new PrettyPageHandler)])
            // Content Negotiation
            ->define(ContentLanguage::class, [':languages' => $config->get('project.i18n.languages', ['en'])])
            ->define(ContentEncoding::class, [':encodings' => ['gzip', 'deflate']])
            // Cors
            ->share(AnalyzerInterface::class)
            ->alias(AnalyzerInterface::class, Analyzer::class)
            ->delegate(Analyzer::class, function () use ($config): AnalyzerInterface {
                $corsSettings = new Settings;
                $corsSettings->setServerOrigin([
                    'scheme' => $config->get('project.cors.scheme'),
                    'host' => $config->get('project.cors.host'),
                    'port' => $config->get('project.cors.port'),
                ]);
                $corsSettings->setRequestAllowedOrigins(
                    array_fill_keys($config->get('project.cors.request.allowed_origins', []), true)
                );
                $corsSettings->setRequestAllowedHeaders(
                    array_fill_keys($config->get('project.cors.request.allowed_headers', []), true)
                );
                $corsSettings->setRequestAllowedMethods(
                    array_fill_keys($config->get('project.cors.request.allowed_methods', []), true)
                );
                $corsSettings->setRequestCredentialsSupported(
                    $config->get('project.cors.request.allowed_credentials', false)
                );
                $corsSettings->setPreFlightCacheMaxAge(
                    $config->get('project.cors.response.preflight_cache_max_age', 0)
                );
                $corsSettings->setResponseExposedHeaders(
                    array_fill_keys($config->get('project.cors.response.exposed_headers', []), true)
                );
                return Analyzer::instance($corsSettings);
            })
            // Routing
            ->share(RoutingHandler::class)
            ->delegate(
                RoutingHandler::class,
                function (ContainerInterface $container) use ($config): RoutingHandler {
                    return new RoutingHandler(
                        $this->routerFactory($config),
                        $container
                    );
                }
            );
    }

    private function routerFactory(ConfigProviderInterface $config): RouterContainer
    {
        $appContext = $config->get('app.context');
        $appEnv = $config->get('app.env');
        $appConfigDir = $config->get('app.config_dir');
        $router = new RouterContainer;
        (new RoutingConfigLoader($router, $config))->load(
            array_merge([$appConfigDir], $config->get('crates.*.config_dir', [])),
            [
                'routing.php',
                "routing.$appContext.php",
                "routing.$appEnv.php",
                "routing.$appContext.$appEnv.php"
            ]
        );
        return $router;
    }
}

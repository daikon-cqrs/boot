<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Service\Provisioner;

use Aura\Router\RouterContainer;
use Auryn\Injector;
use Daikon\Boot\Config\RoutingConfigLoader;
use Daikon\Boot\Middleware\PipelineBuilderInterface;
use Daikon\Boot\Middleware\RoutingHandler;
use Daikon\Boot\Service\ServiceDefinitionInterface;
use Daikon\Config\ConfigProviderInterface;
use Middlewares\ContentEncoding;
use Middlewares\ContentLanguage;
use Middlewares\ContentType;
use Middlewares\JsonPayload;
use Middlewares\RequestHandler;
use Middlewares\UrlEncodePayload;
use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalyzerInterface;
use Neomerx\Cors\Strategies\Settings;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

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
            // Content Negotiation
            ->define(ContentLanguage::class, [
                ':languages' => $configProvider->get('project.negotiation.languages', ['en'])
            ])
            ->define(ContentEncoding::class, [':encodings' => ['gzip', 'deflate']])
            ->delegate(ContentType::class, function () use ($configProvider): ContentType {
                return (new ContentType($configProvider->get('project.negotiation.content_types')))
                    ->charsets($configProvider->get('project.negotiation.charsets', ['UTF-8']))
                    ->nosniff($configProvider->get('project.negotiation.nosniff', true))
                    ->errorResponse();
            })
            // Cors
            ->share(AnalyzerInterface::class)
            ->alias(AnalyzerInterface::class, Analyzer::class)
            ->delegate(Analyzer::class, function () use ($injector, $configProvider): AnalyzerInterface {
                $corsSettings = (new Settings)
                    // skipping ->init() because it doesn't look good
                    ->disableCheckHost()
                    ->disableAddAllowedMethodsToPreFlightResponse()
                    ->disableAddAllowedHeadersToPreFlightResponse()
                    ->setCredentialsNotSupported()
                    ->setServerOrigin(
                        $configProvider->get('project.cors.scheme'),
                        $configProvider->get('project.cors.host'),
                        $configProvider->get('project.cors.port')
                    )->setAllowedOrigins(
                        $configProvider->get('project.cors.request.allowed_origins', [])
                    )->setAllowedHeaders(
                        $configProvider->get('project.cors.request.allowed_headers', [])
                    )->setAllowedMethods(
                        $configProvider->get('project.cors.request.allowed_methods', [])
                    )->setPreFlightCacheMaxAge(
                        $configProvider->get('project.cors.response.preflight_cache_max_age', 0)
                    )->setExposedHeaders(
                        $configProvider->get('project.cors.response.exposed_headers', [])
                    );
                if ($configProvider->get('project.cors.request.enable_check_host') === true) {
                    $corsSettings = $corsSettings->enableCheckHost();
                }
                if ($configProvider->get('project.cors.request.allowed_all_origins') === true) {
                    $corsSettings = $corsSettings->enableAllOriginsAllowed();
                }
                if ($configProvider->get('project.cors.request.allowed_credentials') === true) {
                    $corsSettings = $corsSettings->setCredentialsSupported();
                }
                $corsSettings->setLogger($injector->make(LoggerInterface::class));
                return Analyzer::instance($corsSettings);
            })
            // Routing and request
            ->share(JsonPayload::class)
            ->delegate(JsonPayload::class, fn(): JsonPayload => (new JsonPayload)->depth(8)->override(true))
            ->share(UrlEncodePayload::class)
            ->delegate(UrlEncodePayload::class, fn(): UrlEncodePayload => (new UrlEncodePayload)->override(true))
            ->share(RoutingHandler::class)
            ->delegate(
                RoutingHandler::class,
                function (ContainerInterface $container) use ($configProvider): RoutingHandler {
                    return new RoutingHandler($this->routerFactory($configProvider), $container);
                }
            )
            ->share(RequestHandler::class)
            ->delegate(
                RequestHandler::class,
                function (ContainerInterface $container): RequestHandler {
                    return (new RequestHandler($container))->handlerAttribute(RoutingHandler::REQUEST_HANDLER);
                }
            );
    }

    private function routerFactory(ConfigProviderInterface $configProvider): RouterContainer
    {
        $appContext = $configProvider->get('app.context');
        $appEnv = $configProvider->get('app.env');
        $appConfigDir = $configProvider->get('app.config_dir');
        $router = new RouterContainer;
        (new RoutingConfigLoader($router, $configProvider))->load(
            array_merge([$appConfigDir], (array)$configProvider->get('crates.*.config_dir', [])),
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

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
            // Content Negotiation
            ->define(ContentLanguage::class, [':languages' => $config->get('project.negotiation.languages', ['en'])])
            ->define(ContentEncoding::class, [':encodings' => ['gzip', 'deflate']])
            ->delegate(ContentType::class, function () use ($config): ContentType {
                return (new ContentType($config->get('project.negotiation.content_types')))
                    ->charsets($config->get('project.negotiation.charsets', ['UTF-8']))
                    ->nosniff($config->get('project.negotiation.nosniff', true))
                    ->errorResponse();
            })
            // Cors
            ->share(AnalyzerInterface::class)
            ->alias(AnalyzerInterface::class, Analyzer::class)
            ->delegate(Analyzer::class, function () use ($config): AnalyzerInterface {
                $corsSettings = (new Settings)
                    ->disableAddAllowedMethodsToPreFlightResponse()
                    ->disableAddAllowedHeadersToPreFlightResponse()
                    ->enableCheckHost()
                    ->setServerOrigin(
                        $config->get('project.cors.scheme'),
                        $config->get('project.cors.host'),
                        $config->get('project.cors.port')
                    )->setAllowedOrigins(
                        $config->get('project.cors.request.allowed_origins', [])
                    )->setAllowedHeaders(
                        $config->get('project.cors.request.allowed_headers', [])
                    )->setAllowedMethods(
                        $config->get('project.cors.request.allowed_methods', [])
                    )->setPreFlightCacheMaxAge(
                        $config->get('project.cors.response.preflight_cache_max_age', 0)
                    )->setExposedHeaders(
                        $config->get('project.cors.response.exposed_headers', [])
                    );
                if ($config->get('project.cors.request.allowed_all_origins') === true) {
                    $corsSettings = $corsSettings->enableAllOriginsAllowed();
                }
                if ($config->get('project.cors.request.allowed_credentials') === true) {
                    $corsSettings = $corsSettings->setCredentialsSupported();
                }
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
                function (ContainerInterface $container) use ($config): RoutingHandler {
                    return new RoutingHandler($this->routerFactory($config), $container);
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

    private function routerFactory(ConfigProviderInterface $config): RouterContainer
    {
        $appContext = $config->get('app.context');
        $appEnv = $config->get('app.env');
        $appConfigDir = $config->get('app.config_dir');
        $router = new RouterContainer;
        (new RoutingConfigLoader($router, $config))->load(
            array_merge([$appConfigDir], (array)$config->get('crates.*.config_dir', [])),
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

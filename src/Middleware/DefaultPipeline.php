<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Daikon\Config\ConfigProviderInterface;
use Middlewares\ContentEncoding;
use Middlewares\ContentLanguage;
use Middlewares\ContentType;
use Middlewares\Cors;
use Middlewares\RequestHandler;
use Middlewares\Whoops;
use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Strategies\Settings;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\Relay;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

final class DefaultPipeline implements PipelineBuilderInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var ConfigProviderInterface */
    private $configProvider;

    public function __construct(ContainerInterface $container, ConfigProviderInterface $configProvider)
    {
        $this->container = $container;
        $this->configProvider = $configProvider;
    }

    public function __invoke(): RequestHandlerInterface
    {
        $middlewares = [];
        $this->addDev(
            $middlewares,
            new Whoops(
                (new Run)->pushHandler(new PrettyPageHandler)
            )
        )->add(
            $middlewares,
            new ContentType,
            new ContentLanguage(['en', 'gl', 'es']),
            new ContentEncoding(['gzip', 'deflate'])
        );

        if ($this->configProvider->get('cors.enabled', false)) {
            $this->add($middlewares, $this->buildCorsMiddleware());
        }

        $this->add(
            $middlewares,
            $this->container->get(JwtHandler::class),
            $this->container->get(AuthenticationHandler::class),
            $this->container->get(AuraRouting::class),
            new RequestHandler($this->container)
        );

        return new Relay($middlewares);
    }

    private function addDev(array &$middlewares, MiddlewareInterface ...$middleware): self
    {
        if ($this->configProvider->get('env') === 'development') {
            return $this->add($middlewares, ...$middleware);
        }
        return $this;
    }

    private function add(array &$middlewares, MiddlewareInterface ...$middleware): self
    {
        array_push($middlewares, ...$middleware);
        return $this;
    }

    private function buildCorsMiddleware(): Cors
    {
        $settings = new Settings();
        $settings->setServerOrigin([
            'scheme' => 'http',
            'host' => $this->configProvider->get('cors.host'),
            'port' => $this->configProvider->get('cors.port'),
        ]);
        return new Cors(Analyzer::instance($settings));
    }
}

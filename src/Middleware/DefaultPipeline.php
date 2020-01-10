<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Middleware;

use Daikon\Config\ConfigProviderInterface;
use Franzl\Middleware\Whoops\WhoopsMiddleware;
use Middlewares\ContentEncoding;
use Middlewares\ContentLanguage;
use Middlewares\ContentType;
use Middlewares\Cors;
use Middlewares\RequestHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\Relay;

final class DefaultPipeline implements PipelineBuilderInterface
{
    private static array $defaultPipeline = [
        ContentType::class,
        ContentLanguage::class,
        ContentEncoding::class,
        RoutingHandler::class,
        ActionHandler::class,
        RequestHandler::class
    ];

    private ContainerInterface $container;

    private ConfigProviderInterface $configProvider;

    private array $settings;

    public function __construct(
        ContainerInterface $container,
        ConfigProviderInterface $configProvider,
        array $settings = []
    ) {
        $this->container = $container;
        $this->configProvider = $configProvider;
        $this->settings = $settings;
    }

    public function __invoke(): RequestHandlerInterface
    {
        $middlewares = [];
        $this->addDebug($middlewares, $this->container->get(WhoopsMiddleware::class));

        if ($this->configProvider->get('project.cors.enabled', false)) {
            $this->add($middlewares, $this->container->get(Cors::class));
        }

        $this->add($middlewares, ...array_map(
            [$this->container, 'get'],
            $this->settings['pipeline'] ?? self::$defaultPipeline
        ));

        return new Relay($middlewares);
    }

    private function addDebug(array &$middlewares, MiddlewareInterface ...$middleware): void
    {
        if ($this->configProvider->get('app.debug') === true) {
            $this->add($middlewares, ...$middleware);
        }
    }

    private function add(array &$middlewares, MiddlewareInterface ...$middleware): void
    {
        array_push($middlewares, ...$middleware);
    }
}

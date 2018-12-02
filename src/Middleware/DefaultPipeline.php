<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Daikon\Config\ConfigProviderInterface;
use Middlewares\RequestHandler;
use Middlewares\Whoops;
use Psr\Container\ContainerInterface;
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
        if ($this->configProvider->get('env') === 'development') {
            $middlewares[] = new Whoops(
                (new Run)->pushHandler(new PrettyPageHandler)
            );
        }

        array_push(
            $middlewares,
            $this->container->get(AuraRouting::class),
            new RequestHandler($this->container)
        );

        return new Relay($middlewares);
    }
}

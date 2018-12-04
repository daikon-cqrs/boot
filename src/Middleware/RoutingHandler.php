<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Aura\Router\Route;
use Aura\Router\RouterContainer;
use Aura\Router\Rule\Accepts;
use Aura\Router\Rule\Allows;
use Aura\Router\Rule\Host;
use Aura\Router\Rule\Path;
use Middlewares\Utils\Traits\HasResponseFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RoutingHandler implements MiddlewareInterface
{
    use HasResponseFactory;

    /** @var string */
    const ATTR_HANDLER = 'request-handler';

    /** @var RouterContainer */
    private $router;

    /** @var ContainerInterface */
    private $container;

    public function __construct(RouterContainer $router, ContainerInterface $container)
    {
        $this->router = $router;
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $matcher = $this->router->getMatcher();
        if (!$route = $matcher->match($request)) {
            return $this->errorResponse($matcher->getFailedRoute());
        }
        foreach ($route->attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }
        return $handler->handle(
            $request->withAttribute(self::ATTR_HANDLER, $this->initHandler($route->handler))
        );
    }

    private function errorResponse(Route $failedRoute = null): ResponseInterface
    {
        if (!$failedRoute) {
            return $this->createResponse(500);
        }
        switch ($failedRoute->failedRule) {
            case Accepts::class:
                return $this->createResponse(406);

            case Allows::class:
                $allowed = implode(', ', $failedRoute->allows);
                return $this->createResponse(405)->withHeader('Allow', $allowed);

            case Host::class:
            case Path::class:
                return $this->createResponse(404);
        }
    }

    private function initHandler($requestHandler)
    {
        if (is_string($requestHandler)) {
            $requestHandler = $this->container->get($requestHandler);
        }
        return $requestHandler;
    }
}

<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Middleware;

use Aura\Router\Route;
use Aura\Router\RouterContainer;
use Aura\Router\Rule\Accepts;
use Aura\Router\Rule\Allows;
use Aura\Router\Rule\Host;
use Aura\Router\Rule\Path;
use Fig\Http\Message\StatusCodeInterface;
use Middlewares\Utils\Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RoutingHandler implements MiddlewareInterface, StatusCodeInterface
{
    public const REQUEST_HANDLER = '_request_handler';

    private RouterContainer $router;

    private ContainerInterface $container;

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

        $requestHandler = is_string($route->handler)
            ? $this->container->get($route->handler)
            : $route->handler;

        return $handler->handle(
            $request->withAttribute(self::REQUEST_HANDLER, $requestHandler)
        );
    }

    private function errorResponse(Route $failedRoute = null): ResponseInterface
    {
        if (!$failedRoute) {
            return Factory::createResponse(self::STATUS_INTERNAL_SERVER_ERROR);
        }

        switch ($failedRoute->failedRule) {
            case Accepts::class:
                return Factory::createResponse(self::STATUS_NOT_ACCEPTABLE);
            case Allows::class:
                $allowed = implode(', ', $failedRoute->allows);
                return Factory::createResponse(self::STATUS_METHOD_NOT_ALLOWED)->withHeader('Allow', $allowed);
            case Host::class:
            case Path::class:
                return Factory::createResponse(self::STATUS_NOT_FOUND);
            default:
                return Factory::createResponse(self::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}

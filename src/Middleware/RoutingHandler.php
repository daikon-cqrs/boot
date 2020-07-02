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
use Daikon\Boot\Middleware\Action\ActionInterface;
use Middlewares\Utils\Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RoutingHandler implements MiddlewareInterface
{
    public const ATTR_HANDLER = 'request-handler';

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

        return $handler->handle(
            $request->withAttribute(self::ATTR_HANDLER, $this->initHandler($route->handler))
        );
    }

    private function errorResponse(Route $failedRoute = null): ResponseInterface
    {
        if (!$failedRoute) {
            return Factory::createResponse(500);
        }

        switch ($failedRoute->failedRule) {
            case Accepts::class:
                return Factory::createResponse(406);
            case Allows::class:
                $allowed = implode(', ', $failedRoute->allows);
                return Factory::createResponse(405)->withHeader('Allow', $allowed);
            case Host::class:
            case Path::class:
                return Factory::createResponse(404);
            default:
                return Factory::createResponse(500);
        }
    }

    /** @param callable|ActionInterface $requestHandler */
    private function initHandler($requestHandler): callable
    {
        if (is_string($requestHandler)) {
            $requestHandler = $this->container->get($requestHandler);
        }
        return $requestHandler;
    }
}

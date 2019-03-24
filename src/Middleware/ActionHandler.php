<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Oroshi\Core\Middleware\Action\ActionInterface;
use Oroshi\Core\Middleware\Action\ResponderInterface;
use Oroshi\Core\Middleware\Action\ValidatorInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ActionHandler implements MiddlewareInterface
{
    use ResolvesDependency;

    const ATTR_ERRORS = '_errors';

    const ATTR_ERROR_CODE = '_error_code';

    const ATTR_ERROR_SEVERITY = '_error_severity';

    const ATTR_RESPONDER = '_responder';

    const ATTR_VALIDATOR = '_validator';

    const ATTR_PAYLOAD = '_payload';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestHandler = $request->getAttribute(RoutingHandler::ATTR_HANDLER);
        return $requestHandler instanceof ActionInterface
            ? $this->executeAction($requestHandler, $request)
            : $handler->handle($request);
    }

    private function executeAction(ActionInterface $action, ServerRequestInterface $request): ResponseInterface
    {
        if ($validator = $this->getValidator($action->registerValidator($request))) {
            $request = $validator($request);
        }

        $errors = $request->getAttribute(self::ATTR_ERRORS);
        $request = empty($errors)
            ? $action($request)
            : $action->handleError($request);

        if (!$responder = $this->getResponder($request)) {
            throw new \RuntimeException('Unable to determine responder for '.get_class($action));
        }

        return $responder($request);
    }

    private function getValidator(ServerRequestInterface $request): ?callable
    {
        return ($validator = $request->getAttribute(self::ATTR_VALIDATOR))
            ? $this->resolve($validator, ValidatorInterface::class)
            : null;
    }

    private function getResponder(ServerRequestInterface $request): ?callable
    {
        return ($responder = $request->getAttribute(self::ATTR_RESPONDER))
            ? $this->resolve($responder, ResponderInterface::class)
            : null;
    }
}

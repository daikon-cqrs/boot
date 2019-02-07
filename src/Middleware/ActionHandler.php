<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Assert\Assertion;
use Oroshi\Core\Middleware\Action\ActionInterface;
use Oroshi\Core\Middleware\Action\ResponderInterface;
use Oroshi\Core\Middleware\Action\ValidatorInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ActionHandler implements MiddlewareInterface
{
    const ATTR_ERRORS = '_errors';

    const ATTR_RESPONDER = '_responder';

    const ATTR_VALIDATOR = '_validator';

    /** @var ContainerInterface */
    private $container;

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

    /** @param mixed $dependency */
    private function resolve($dependency, string $stereoType): ?callable
    {
        if (is_string($dependency)) {
            Assertion::classExists($dependency);
            $responder = $this->container->get($dependency);
        } elseif (is_array($dependency) && count($dependency) === 2) {
            $fqcn = $dependency[0];
            $params = $dependency[1];
            Assertion::classExists($fqcn);
            Assertion::isArray($params);
            $dependency = $this->container->make($fqcn, $params);
        }
        if (is_object($dependency)) {
            Assertion::isInstanceOf($dependency, $stereoType);
        }
        if (is_callable($dependency)) {
            return $dependency;
        }
        throw new \RuntimeException(
            sprintf("Given type '%s' is not a $stereoType.", gettype($dependency))
        );
    }
}

<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Assert\Assertion;
use Oroshi\Core\Middleware\Action\ActionInterface;
use Oroshi\Core\Middleware\Action\ValidatorInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ActionHandler implements MiddlewareInterface
{
    const ATTR_VALIDATOR = '_validator';

    const ATTR_ERRORS = 'errors';

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
        return empty($errors) ? $action($request) : $action->handleError($request);
    }

    private function getValidator(ServerRequestInterface $request): ?callable
    {
        if (!$validator = $request->getAttribute(self::ATTR_VALIDATOR)) {
            return null;
        }
        if (is_string($validator)) {
            Assertion::classExists($validator);
            $validator = $this->container->get($validator);
        } elseif (is_array($validator) && count($validator) === 2) {
            $fqcn = $validator[0];
            $params = $validator[1];
            Assertion::classExists($fqcn);
            Assertion::isArray($params);
            $validator = $this->container->make($fqcn, $params);
        }
        if (is_object($validator)) {
            Assertion::isInstanceOf($validator, ValidatorInterface::class);
        }
        if (is_callable($validator)) {
            return $validator;
        }
        throw new \RuntimeException(
            sprintf('Given validator type: "%s" is not supported.', gettype($validator))
        );
    }
}

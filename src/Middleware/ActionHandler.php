<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Middleware;

use Middlewares\Utils\Factory;
use Oroshi\Core\Exception\RuntimeException;
use Oroshi\Core\Middleware\Action\ActionInterface;
use Oroshi\Core\Middleware\Action\ResponderInterface;
use Oroshi\Core\Middleware\Action\SecureActionInterface;
use Oroshi\Core\Middleware\Action\ValidatorInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ActionHandler implements MiddlewareInterface
{
    use ResolvesDependency;

    public const ATTR_ERRORS = '_errors';
    public const ATTR_ERROR_CODE = '_error_code';
    public const ATTR_ERROR_SEVERITY = '_error_severity';
    public const ATTR_RESPONDER = '_responder';
    public const ATTR_VALIDATOR = '_validator';
    public const ATTR_PAYLOAD = '_payload';

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
        if ($action instanceof SecureActionInterface) {
            // Check action access first before running validation
            if (!$action->isAuthorized($request)) {
                return Factory::createResponse(401);
            }
        }

        if ($validator = $this->getValidator($action->registerValidator($request))) {
            $request = $validator($request);
        }

        $errors = $request->getAttribute(self::ATTR_ERRORS);
        if (empty($errors)) {
            if ($action instanceof SecureActionInterface) {
                // Run secondary resource authorization after validation
                if (!$action->isAuthorized($request)) {
                    return Factory::createResponse(401);
                }
            }
            $request = $action($request);
        } else {
            $request = $action->handleError($request);
        }

        if (!$responder = $this->getResponder($request)) {
            throw new RuntimeException('Unable to determine responder for '.get_class($action));
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

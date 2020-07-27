<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Middleware;

use Daikon\Boot\Exception\ServiceException;
use Daikon\Boot\Middleware\Action\ActionInterface;
use Daikon\Boot\Middleware\Action\ResponderInterface;
use Daikon\Boot\Middleware\Action\ValidatorInterface;
use Daikon\Interop\RuntimeException;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final class ActionHandler implements MiddlewareInterface, StatusCodeInterface
{
    use ResolvesDependency;

    public const ATTR_STATUS_CODE = '_status_code';
    public const ATTR_ERRORS = '_errors';
    public const ATTR_ERROR_SEVERITY = '_error_severity';
    public const ATTR_RESPONDER = '_responder';
    public const ATTR_VALIDATOR = '_validator';
    public const ATTR_PAYLOAD = '_payload';

    private ContainerInterface $container;

    private LoggerInterface $logger;

    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestHandler = $request->getAttribute(RoutingHandler::ATTR_REQUEST_HANDLER);
        return $requestHandler instanceof ActionInterface
            ? $this->executeAction($requestHandler, $request)
            : $handler->handle($request);
    }

    private function executeAction(ActionInterface $action, ServerRequestInterface $request): ResponseInterface
    {
        $request = $action->registerValidator($request);
        if ($validator = $this->getValidator($request)) {
            $request = $validator($request);
        }

        if (!empty($request->getAttribute(self::ATTR_ERRORS))) {
            $request = $action->handleError(
                $request->withAttribute(
                    self::ATTR_STATUS_CODE,
                    $request->getAttribute(self::ATTR_STATUS_CODE, self::STATUS_UNPROCESSABLE_ENTITY)
                )
            );
        } else {
            try {
                $request = $action($request);
            } catch (ServiceException $error) {
                $request = $action->handleError(
                    $request->withAttribute(self::ATTR_STATUS_CODE, self::STATUS_UNPROCESSABLE_ENTITY)
                        ->withAttribute(self::ATTR_ERRORS, $error)
                );
            } catch (Exception $error) {
                $this->logger->error($error->getMessage() , ['exception' => $error]);
            } finally {
                if ($error && !$request->getAttribute(ActionHandler::ATTR_RESPONDER)) {
                    throw $error;
                }
            }
        }

        if (!$responder = $this->getResponder($request)) {
            throw new RuntimeException(sprintf("Unable to determine responder for '%s'.", get_class($action)));
        }

        return $responder($request);
    }

    private function getValidator(ServerRequestInterface $request): ?callable
    {
        return ($validator = $request->getAttribute(self::ATTR_VALIDATOR))
            ? $this->resolve($this->container, $validator, ValidatorInterface::class)
            : null;
    }

    private function getResponder(ServerRequestInterface $request): ?callable
    {
        return ($responder = $request->getAttribute(self::ATTR_RESPONDER))
            ? $this->resolve($this->container, $responder, ResponderInterface::class)
            : null;
    }
}

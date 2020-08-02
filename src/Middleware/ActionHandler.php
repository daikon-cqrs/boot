<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Middleware;

use Daikon\Boot\Middleware\Action\ActionInterface;
use Daikon\Interop\Assertion;
use Daikon\Interop\AssertionFailedException;
use Daikon\Interop\RuntimeException;
use Daikon\Validize\Validation\ValidatorDefinition;
use Daikon\Validize\ValueObject\Severity;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class ActionHandler implements MiddlewareInterface, StatusCodeInterface
{
    use ResolvesDependency;

    public const ERRORS = '_errors';
    public const PAYLOAD = '_payload';
    public const STATUS_CODE = '_status_code';
    public const RESPONDER = '_responder';

    protected ContainerInterface $container;

    protected LoggerInterface $logger;

    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestHandler = $request->getAttribute(RoutingHandler::REQUEST_HANDLER);
        return $requestHandler instanceof ActionInterface
            ? $this->execute($requestHandler, $request)
            : $handler->handle($request);
    }

    protected function execute(ActionInterface $action, ServerRequestInterface $request): ResponseInterface
    {
        try {
            if ($validator = $action->getValidator($request)) {
                $validatorDefinition = (new ValidatorDefinition('$', Severity::critical()))->withArgument($request);
                $request = $request->withAttribute(self::PAYLOAD, $validator($validatorDefinition));
                Assertion::noContent($request->getAttribute(self::ERRORS));
            }
            $request = $action($request);
        } catch (Exception $error) {
            switch (true) {
                case $error instanceof AssertionFailedException:
                    $statusCode = self::STATUS_UNPROCESSABLE_ENTITY;
                    break;
                default:
                    $this->logger->error($error->getMessage(), ['trace' => $error->getTrace()]);
                    $statusCode = self::STATUS_INTERNAL_SERVER_ERROR;
            }
            $request = $action->handleError(
                $request
                    ->withAttribute(self::STATUS_CODE, $request->getAttribute(self::STATUS_CODE, $statusCode))
                    ->withAttribute(self::ERRORS, $request->getAttribute(self::ERRORS, $error))
            );
        }

        if (!$responder = $this->resolveResponder($request)) {
            throw $error ?? new RuntimeException(
                sprintf("Unable to determine responder for '%s'.", get_class($action))
            );
        }

        return $responder->handle($request);
    }

    protected function resolveResponder(ServerRequestInterface $request): RequestHandlerInterface
    {
        $responder = $request->getAttribute(self::RESPONDER);
        if (!$responder instanceof RequestHandlerInterface) {
            /** @var RequestHandlerInterface $responder */
            $responder = $this->resolve($this->container, $responder, RequestHandlerInterface::class);
        }
        return $responder;
    }
}

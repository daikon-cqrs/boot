<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Middleware;

use Daikon\Boot\Middleware\Action\ActionInterface;
use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Interop\Assertion;
use Daikon\Interop\AssertionFailedException;
use Daikon\Interop\DaikonException;
use Daikon\Interop\RuntimeException;
use Daikon\Validize\Validation\ValidatorDefinition;
use Daikon\Validize\ValueObject\Severity;
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
            ? $this->execute($requestHandler, DaikonRequest::wrap($request))
            : $handler->handle($request);
    }

    protected function execute(ActionInterface $action, DaikonRequest $request): ResponseInterface
    {
        try {
            if ($validator = $action->getValidator($request)) {
                $validatorDefinition = (new ValidatorDefinition('$', Severity::critical()))->withArgument($request);
                $request = $request->withPayload($validator($validatorDefinition));
                Assertion::noContent($request->getErrors());
            }
            $request = $action($request);
        } catch (DaikonException $error) {
            switch (true) {
                case $error instanceof AssertionFailedException:
                    $statusCode = self::STATUS_UNPROCESSABLE_ENTITY;
                    break;
                default:
                    $this->logger->error($error->getMessage(), ['exception' => $error->getTrace()]);
                    $statusCode = self::STATUS_INTERNAL_SERVER_ERROR;
            }
            $request = $action->handleError(
                $request
                    ->withStatusCode($request->getStatusCode($statusCode))
                    ->withErrors($request->getErrors($error))
            );
        }

        if (!$responder = $this->resolveResponder($request)) {
            throw $error ?? new RuntimeException(
                sprintf("Unable to determine responder for '%s'.", get_class($action))
            );
        }

        return $responder->handle($request);
    }

    protected function resolveResponder(DaikonRequest $request): RequestHandlerInterface
    {
        $responder = $request->getResponder();
        if (!$responder instanceof RequestHandlerInterface) {
            /** @var RequestHandlerInterface $responder */
            $responder = $this->resolve($this->container, $responder, RequestHandlerInterface::class);
        }
        return $responder;
    }
}

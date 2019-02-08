<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Middlewares\Utils\Traits\HasResponseFactory;
use Oroshi\Core\Middleware\Action\ActionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class AuthenticationHandler implements MiddlewareInterface
{
    use HasResponseFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $jwt = $request->getAttribute(JwtDecoder::ATTR_TOKEN);
        $xsrfToken = $request->getAttribute(JwtDecoder::ATTR_XSRF);

        if ($jwt && $jwt->xsrf !== $xsrfToken) {
            return $this->createResponse(401, 'Unauthorized XSRF');
        }

        if (!$jwt && $this->isSecure($request)) {
            return $this->createResponse(403);
        }

        return $handler->handle($request);
    }

    private function isSecure(ServerRequestInterface $request): bool
    {
        $requestHandler = $request->getAttribute(RoutingHandler::ATTR_HANDLER);
        return !empty($requestHandler) && $requestHandler instanceof ActionInterface
            ? $requestHandler->isSecure()
            : false;
    }
}

<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Middlewares\Utils\Factory;
use Middlewares\Utils\Traits\HasResponseFactory;
use Oroshi\Core\Logging\LoggingService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationHandler implements MiddlewareInterface
{
    use HasResponseFactory;

    /** @var string */
    private static $attribute = '_user';

    /** @var LoggingService */
    private $logger;

    public function __construct(LoggingService $logger)
    {
        $this->responseFactory = Factory::getResponseFactory();
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = null;
        if (!$user) {
            // 405 NOT ALLOWED
            return $this->createResponse(405);
        }
        $request = $request->withAttribute(self::$attribute, $user);

        return $handler->handle($request);
    }
}

<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Middlewares\Utils\Factory;
use Middlewares\Utils\Traits\HasResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class JwtHandler implements MiddlewareInterface
{
    use HasResponseFactory;

    /** @var string */
    private static $attribute = '_token';

    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->responseFactory = Factory::getResponseFactory();
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('_user');
        $jwtToken = null;
        $this->logger->info(__METHOD__);
        $request = $request->withAttribute(self::$attribute, $jwtToken);

        return $handler->handle($request);
    }
}

<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Assert\Assertion;
use Daikon\Config\ConfigProviderInterface;
use Middlewares\Utils\Traits\HasResponseFactory;
use Oroshi\Core\Exception\ConfigException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class AuthHandler implements MiddlewareInterface
{
    use HasResponseFactory;

    const ATTR_USER = 'user';

    const CONF_LOGIN = 'services.oroshi.http_pipeline.default_actions.login';

    /** @var LoggerInterface */
    private $logger;

    /** @var ConfigProviderInterface */
    private $configProvider;

    /** @var string */
    private $loginAction;

    public function __construct(LoggerInterface $logger, ConfigProviderInterface $configProvider)
    {
        if ($loginAction = $configProvider->get(self::CONF_LOGIN)) {
            Assertion::classExists($loginAction);
            $this->loginAction = $loginAction;
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $jwtToken = $request->getAttribute(JwtDecoder::ATTR_TOKEN);
        if (!$jwtToken && $this->isSecure($request)) {
            if (!$this->loginAction) {
                return $this->createResponse(401);
            }
            $request = $request->withAttribute(AuraRouting::ATTR_HANDLER, $this->loginAction);
        }
        return $handler->handle($request);
    }

    private function isSecure(ServerRequestInterface $request): bool
    {
        $routedAction = $request->getAttribute(AuraRouting::ATTR_HANDLER);
        // @todo figure out if action requires authentication
        return true;
    }
}

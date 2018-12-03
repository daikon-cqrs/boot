<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use \Firebase\JWT\JWT;
use Daikon\Config\ConfigProviderInterface;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use function GuzzleHttp\Psr7\parse_query;
use Middlewares\Utils\Factory;
use Middlewares\Utils\Traits\HasResponseFactory;
use Oro\Security\Repository\Standard\User;
use Oro\Security\Repository\Standard\Users;
use Oro\Security\ValueObject\PasswordHash;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use stdClass;

class AuthenticationHandler implements MiddlewareInterface
{
    use HasResponseFactory;

    const ATTR_TOKEN = '_token';

    const ATTR_USER = '_user';

    /** @var LoggerInterface */
    private $logger;

    /** @var ConfigProviderInterface */
    private $configProvider;

    /** @var Users */
    private $users;

    public function __construct(
        Users $users,
        LoggerInterface $logger,
        ConfigProviderInterface $configProvider
    ) {
        $this->logger = $logger;
        $this->configProvider = $configProvider;
        $this->users = $users;
        $this->responseFactory = Factory::getResponseFactory();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $jwtToken = null;
        $secretKey = $this->configProvider->get('jwt.secret', 'foobar');
        $queryParams = parse_query($request->getUri()->getQuery());
        if (!isset($queryParams['token'])) {
            if ($user = $this->authenticate($request)) {
                $jwtToken = $this->encodeToken($user, $secretKey);
            }
        } else {
            $jwtToken = $this->decodeToken($token, $secretKey);
            // @todo restore user
        }
        return $handler->handle(
            $request
                ->withAttribute(self::ATTR_TOKEN, $jwtToken)
                ->withAttribute(self::ATTR_USER, $user)
        );
    }

    private function authenticate(ServerRequestInterface $request): ?User
    {
        $queryParams = parse_query($request->getUri()->getQuery());
        if (!isset($queryParams['username']) || !isset($queryParams['password'])) {
            return null;
        }
        if (!$user = $this->users->byUsername($queryParams['username'])) {
            return null;
        }
        if (!$hash = $user->getPasswordHash()) {
            return null;
        }
        return $hash->verify($queryParams['password']) ? $user : null;
    }

    private function encodeToken(User $user, string $secretKey): string
    {
        return JWT::encode([
            'iss' => $this->configProvider->get('project.name'),
            'aud' => $this->configProvider->get('project.name'),
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 60 * 60 * 24, // 1 day expiry period
            'data' => [
                'id' => (string)$user->getAggregateId(),
                'username' => (string)$user->getUsername(),
                'role' => (string)$user->getRole(),
                'state' => (string)$user->getState()
            ]
        ], $secretKey);
    }

    private function decodeToken(string $token, string $secretKey): ?stdClass
    {
        try {
            return JWT::decode($token, $secretKey, ['HS256']);
        } catch (BeforeValidException $err) {
            return null;
        } catch (ExpiredException $err) {
            return null;
        } catch (SignatureInvalidException $err) {
            return null;
        }
    }
}

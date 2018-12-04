<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware;

use Daikon\Config\ConfigProviderInterface;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use function GuzzleHttp\Psr7\parse_query;
use Middlewares\Utils\Traits\HasResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use stdClass;

class JwtDecoder implements MiddlewareInterface
{
    use HasResponseFactory;

    const ATTR_TOKEN = '_jwt';

    /** @var LoggerInterface */
    private $logger;

    /** @var ConfigProviderInterface */
    private $configProvider;

    public function __construct(LoggerInterface $logger, ConfigProviderInterface $configProvider)
    {
        $this->logger = $logger;
        $this->configProvider = $configProvider;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $queryParams = parse_query($request->getUri()->getQuery());
        $encodedToken = $queryParams[self::ATTR_TOKEN]
            ?? $this->parseAuthHeader($request->getHeaderLine('Authorization'));
        $jwtToken = null;
        if ($encodedToken) {
            $jwtToken = $this->decodeToken($encodedToken);
        }
        return $handler->handle(
            $request->withAttribute(self::ATTR_TOKEN, $jwtToken)
        );
    }

    private function decodeToken(string $token): ?stdClass
    {
        $secretKey = $this->configProvider->get('jwt.secret', 'foobar');
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

    private static function parseAuthHeader(string $header): ?string
    {
        if (preg_match('/Bearer ([\w\.\-_]+)/', $header, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}

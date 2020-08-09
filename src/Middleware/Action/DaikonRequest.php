<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Middleware\Action;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class DaikonRequest implements ServerRequestInterface
{
    public const ERRORS = '_errors';
    public const PAYLOAD = '_payload';
    public const STATUS_CODE = '_status_code';
    public const RESPONDER = '_responder';

    protected ServerRequestInterface $serverRequest;

    private function __construct(ServerRequestInterface $serverRequest)
    {
        $this->serverRequest = $serverRequest;
    }

    /** @return static */
    public static function wrap(ServerRequestInterface $serverRequest): self
    {
        return new self($serverRequest);
    }

    public function unwrap(): ServerRequestInterface
    {
        return $this->serverRequest;
    }

    /**
     * @param mixed $errors
     * @return static
     */
    public function withErrors($errors): self
    {
        return static::wrap($this->serverRequest->withAttribute(self::ERRORS, $errors));
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function getErrors($default = null)
    {
        return $this->serverRequest->getAttribute(self::ERRORS, $default);
    }

    /**
     * @param mixed $payload
     * @return static
     */
    public function withPayload($payload): self
    {
        return static::wrap($this->serverRequest->withAttribute(self::PAYLOAD, $payload));
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function getPayload($default = null)
    {
        return $this->serverRequest->getAttribute(self::PAYLOAD, $default);
    }

    /**
     * @param mixed $responder
     * @return static
     */
    public function withResponder($responder): self
    {
        return static::wrap($this->serverRequest->withAttribute(self::RESPONDER, $responder));
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function getResponder($default = null)
    {
        return $this->serverRequest->getAttribute(self::RESPONDER, $default);
    }

    /**
     * @param mixed $statusCode
     * @return static
     */
    public function withStatusCode($statusCode): self
    {
        return static::wrap($this->serverRequest->withAttribute(self::STATUS_CODE, $statusCode));
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function getStatusCode($default = null)
    {
        return $this->getAttribute(self::STATUS_CODE, $default);
    }

    public function getServerParams()
    {
        return $this->serverRequest->getServerParams();
    }

    public function getCookieParams()
    {
        return $this->serverRequest->getCookieParams();
    }

    public function withCookieParams(array $cookies)
    {
        return static::wrap($this->serverRequest->withCookieParams($cookies));
    }

    public function getQueryParams()
    {
        return $this->serverRequest->getQueryParams();
    }

    public function withQueryParams(array $query)
    {
        return static::wrap($this->serverRequest->withQueryParams($query));
    }

    public function getUploadedFiles()
    {
        return $this->serverRequest->getUploadedFiles();
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        return static::wrap($this->serverRequest->withUploadedFiles($uploadedFiles));
    }

    public function getParsedBody()
    {
        return $this->serverRequest->getParsedBody();
    }

    public function withParsedBody($data)
    {
        return static::wrap($this->serverRequest->withParsedBody($data));
    }

    public function getAttributes()
    {
        return $this->serverRequest->getAttributes();
    }

    public function getAttribute($name, $default = null)
    {
        return $this->serverRequest->getAttribute($name, $default);
    }

    public function withAttribute($name, $value)
    {
        return static::wrap($this->serverRequest->withAttribute($name, $value));
    }

    public function withoutAttribute($name)
    {
        return static::wrap($this->serverRequest->withoutAttribute($name));
    }

    public function getRequestTarget()
    {
        return $this->serverRequest->getRequestTarget();
    }

    public function withRequestTarget($requestTarget)
    {
        return static::wrap($this->serverRequest->withRequestTarget($requestTarget));
    }

    public function getMethod()
    {
        return $this->serverRequest->getMethod();
    }

    public function withMethod($method)
    {
        return static::wrap($this->serverRequest->withMethod($method));
    }

    public function getUri()
    {
        return $this->serverRequest->getUri();
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        return static::wrap($this->serverRequest->withUri($uri, $preserveHost));
    }

    public function getProtocolVersion()
    {
        return $this->serverRequest->getProtocolVersion();
    }

    public function withProtocolVersion($version)
    {
        return static::wrap($this->serverRequest->withProtocolVersion($version));
    }

    public function getHeaders()
    {
        return $this->serverRequest->getHeaders();
    }

    public function hasHeader($name)
    {
        return $this->serverRequest->hasHeader($name);
    }

    public function getHeader($name)
    {
        return $this->serverRequest->getHeader($name);
    }

    public function getHeaderLine($name)
    {
        return $this->serverRequest->getHeaderLine($name);
    }

    public function withHeader($name, $value)
    {
        return static::wrap($this->serverRequest->withHeader($name, $value));
    }

    public function withAddedHeader($name, $value)
    {
        return static::wrap($this->serverRequest->withAddedHeader($name, $value));
    }

    public function withoutHeader($name)
    {
        return static::wrap($this->serverRequest->withoutHeader($name));
    }

    public function getBody()
    {
        return $this->serverRequest->getBody();
    }

    public function withBody(StreamInterface $body)
    {
        return static::wrap($this->serverRequest->withBody($body));
    }
}

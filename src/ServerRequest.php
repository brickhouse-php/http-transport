<?php

namespace Brickhouse\Http\Transport;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Defines a standard, server-bound HTTP request.
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @param array<string,mixed>       $serverParams   Server parameters, typically from `$_SERVER`.
     */
    public function __construct(
        string $method,
        UriInterface $uri,
        private array $serverParams = [],
        ?HeaderBag $headers = null,
        StreamInterface|string $body = "",
        ?int $contentLength = null,
        string $protocol = "1.1"
    ) {
        parent::__construct($method, $uri, $headers, $body, $contentLength, $protocol);
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     *
     * @return array<string,mixed>
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     *
     * @return array<string,mixed>
     */
    public function getCookieParams(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     *
     * @param array<string,mixed>   $cookies    Array of key/value pairs representing cookies.
     */
    public function withCookieParams(array $cookies): static
    {
        return clone $this;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     *
     * @return array<string,list<string>>
     */
    public function getQueryParams(): array
    {
        return $this->queryParameters();
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     *
     * @param array<string,string|list<string>> $parameters
     */
    public function withQueryParams(array $parameters): static
    {
        $new = clone $this;
        $new->setQueryParameters($parameters);

        return $new;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     *
     * @return array<string,mixed>
     */
    public function getUploadedFiles(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     *
     * @param array<string,mixed>       $uploadedFiles
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        return clone $this;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     *
     * @return null|array<string,mixed>|object
     */
    public function getParsedBody(): null|array|object
    {
        return null;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     *
     * @param null|array<string,mixed>|object   $data
     */
    public function withParsedBody(mixed $data): static
    {
        return clone $this;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     *
     * @return list<mixed>
     */
    public function getAttributes(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return null;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function withAttribute(string $name, mixed $value): static
    {
        return clone $this;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function withoutAttribute($name): static
    {
        return clone $this;
    }
}

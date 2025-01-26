<?php

namespace Brickhouse\Http\Transport;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Defines a standard, server-bound HTTP request.
 */
class Request extends Message implements RequestInterface
{
    public function __construct(
        private string $method,
        private UriInterface $uri,
        ?HeaderBag $headers = null,
        StreamInterface|string $body = "",
        ?int $contentLength = null,
        string $protocol = "1.1"
    ) {
        parent::__construct($headers, $contentLength, $protocol);

        $this->setContent($body);
    }

    /**
     * Get the body content of the request.
     *
     * @return StreamInterface
     */
    public function content(): StreamInterface
    {
        return $this->body;
    }

    /**
     * Sets the body content of the request.
     *
     * @param StreamInterface|string    $body
     */
    public function setContent(StreamInterface|string $body): void
    {
        if (is_string($body)) {
            $body = new MemoryStream($body);
        }

        $this->body = $body;
    }

    /**
     * Get the HTTP method of the request.
     *
     * @return non-empty-string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Sets the HTTP method of the request.
     *
     * @return void
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * Get the URI of the request.
     *
     * @return UriInterface
     */
    public function uri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Sets the URI of the request.
     *
     * @return void
     */
    public function setUri(UriInterface $uri): void
    {
        $this->uri = $uri;
    }

    /**
     * Get the HTTP scheme of the request.
     *
     * @return null|string
     */
    public function scheme(): ?string
    {
        return $this->uri->getScheme();
    }

    /**
     * Get the HTTP host of the request.
     *
     * @return null|string
     */
    public function host(): ?string
    {
        return $this->uri->getHost();
    }

    /**
     * Get the HTTP port of the request.
     *
     * @return null|int
     */
    public function port(): ?int
    {
        return $this->uri->getPort();
    }

    /**
     * Get the HTTP path of the request.
     *
     * @return string
     */
    public function path(): string
    {
        $path = $this->uri->getPath();
        if (empty($path)) {
            return '/';
        }

        return $path;
    }

    /**
     * Get the HTTP query of the request.
     *
     * @return string
     */
    public function query(): string
    {
        return $this->uri->getQuery();
    }

    /**
     * Gets the query parameters in the request URI.
     *
     * @return array<string,list<string>>
     */
    public function queryParameters(): array
    {
        $values = [];

        foreach (explode('&', $this->query()) as $param) {
            $parts = explode('=', $param);

            $key = html_entity_decode($parts[0]);
            $value = html_entity_decode($parts[1] ?? '');

            if (isset($values[$key])) {
                $values[$key][] = $value;
            } else {
                $values[$key] = [$value];
            }
        }

        return $values;
    }

    /**
     * Sets the query parameters in the request URI.
     *
     * @param array<string,string|list<string>>     $parameters
     *
     * @return void
     */
    public function setQueryParameters(array $parameters): void
    {
        $queryParameters = [];

        foreach ($parameters as $name => $values) {
            if (is_string($values)) {
                $values = [$values];
            }

            foreach ($values as $value) {
                $name = htmlentities($name, double_encode: false);
                $value = htmlentities($value, double_encode: false);

                $queryParameters[] = $name . '=' . $value;
            }
        }

        $this->uri = $this->uri->withQuery(join("&", $queryParameters));
    }

    /**
     * Get the HTTP fragment of the request.
     *
     * @return null|string
     */
    public function fragment(): ?string
    {
        return $this->uri->getFragment();
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function getRequestTarget(): string
    {
        return $this->path();
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function withRequestTarget(mixed $requestTarget): static
    {
        $new = clone $this;
        $new->uri = $this->uri->withPath($requestTarget);

        return $new;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function getMethod(): string
    {
        return $this->method();
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function withMethod(string $method): static
    {
        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function getUri(): UriInterface
    {
        return $this->uri();
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $new = clone $this;
        $new->uri = $uri;

        if ($preserveHost && $this->hasHeader('Host')) {
            return $new;
        }

        if (!($host = $uri->getHost())) {
            return $new;
        }

        if (($port = $uri->getPort() !== null)) {
            $host .= ':' . $port;
        }

        $new->headers->set('Host', $host);

        return $new;
    }
}

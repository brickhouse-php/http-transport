<?php

namespace Brickhouse\Http\Transport;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    /**
     * Defines a regular-expression for which characters are invalid, as per RFC3986.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-2.2
     *
     * @var string
     */
    private const string INVALID_CHARS_PATTERN = "/[\x00-\x1f\x7f]/";

    /**
     * Defines a regular-expression for matching schemes.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.1
     *
     * @var string
     */
    private const string SCHEME_PATTERN = "/^[a-z][a-z0-9-\+\.]*$/i";

    /**
     * Defines some of the most popular supported schemes and their corresponding default port.
     *
     * @var array<string,int|null>
     */
    private const SCHEME_DEFAULT_PORT = [
        'http' => 80,
        'https' => 443,
        'ws' => 80,
        'wss' => 443,
    ];

    protected null|string $scheme = null;
    protected null|string $userInfo = null;
    protected null|string $host = null;
    protected null|int $port = null;
    protected null|string $path = null;
    protected null|string $query = null;
    protected null|string $fragment = null;

    /**
     * Creates a new URI from the given SERVER super-global.
     *
     * @param null|array<string,mixed>      $server
     *
     * @return Uri
     */
    public static function fromGlobals(null|array $server = null): Uri
    {
        $uri = new Uri;
        $server ??= $_SERVER;

        $isHttps = in_array($server['HTTPS'] ?? '', ['on', true], strict: false);
        $uri = $uri->withScheme($isHttps ? 'https' : 'http');

        $uri->host = $server['SERVER_NAME'];
        $uri->port = intval($server['SERVER_PORT']);
        $uri->path = explode('?', $server['REQUEST_URI'])[0];

        $queryString = explode('#', $server['QUERY_STRING'] ?? '');
        $queryParameters = $queryString[0];
        $fragment = $queryString[1] ?? '';

        $uri->query = $queryParameters;
        $uri->fragment = $fragment;

        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function getScheme(): string
    {
        return $this->scheme ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getAuthority(): string
    {
        $value = '';

        if (($userInfo = $this->getUserInfo())) {
            $value .= $userInfo;
        }

        $value .= $this->getHost();

        if (($port = $this->getPort())) {
            $defaultPortForScheme = self::SCHEME_DEFAULT_PORT[$this->getScheme()] ?? null;

            if ($port !== $defaultPortForScheme) {
                $value .= ':' . $port;
            }
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getUserInfo(): string
    {
        return $this->userInfo ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getHost(): string
    {
        return $this->host ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return empty($this->path ?? '') ? '/' : $this->path;
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): string
    {
        return $this->query ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getFragment(): string
    {
        return $this->fragment ?? '';
    }

    /**
     * @inheritDoc
     */
    public function withScheme(string $scheme): UriInterface
    {
        if (!empty($scheme)) {
            if (preg_match(self::INVALID_CHARS_PATTERN, $scheme)) {
                throw new \InvalidArgumentException("Scheme contains invalid characters: {$scheme}");
            }

            if (!preg_match(self::SCHEME_PATTERN, $scheme)) {
                throw new \InvalidArgumentException("Invalid scheme provided: {$scheme}");
            }

            $scheme = strtolower($scheme);
        }

        if (empty($scheme)) {
            $scheme = null;
        }

        $new = clone $this;
        $new->scheme = $scheme;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withUserInfo(
        string $user,
        #[\SensitiveParameter] ?string $password = null
    ): UriInterface {
        $new = clone $this;
        $new->userInfo = match ($password) {
            null => $user,
            default => "{$user}:{$password}",
        };

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withHost(string $host): UriInterface
    {
        if (!empty($host)) {
            if (preg_match(self::INVALID_CHARS_PATTERN, $host)) {
                throw new \InvalidArgumentException("Host contains invalid characters: {$host}");
            }

            $host = strtolower($host);
        }

        if (empty($host)) {
            $host = null;
        }

        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withPort(?int $port): UriInterface
    {
        if ($port !== null && ($port <= 0 || $port > 65535)) {
            throw new \InvalidArgumentException("Given port number not allowed: {$port}");
        }

        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withPath(string $path): UriInterface
    {
        $new = clone $this;

        $new->path = match (true) {
            trim($path) === '' => '/',
            !str_starts_with($path, '/') => '/' . $path,
            default => $path,
        };

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withQuery(string $query): UriInterface
    {
        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withFragment(string $fragment): UriInterface
    {
        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        $value = '';

        if (($scheme = $this->getScheme())) {
            $value .= $scheme . '://';
        }

        $value .= $this->getAuthority();
        $value .= '/';

        if (($query = $this->getQuery())) {
            $value .= $query;
        }

        if (($fragment = $this->getFragment())) {
            $value .= '#' . $fragment;
        }

        return $value;
    }
}

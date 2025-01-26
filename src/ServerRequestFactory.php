<?php

namespace Brickhouse\Http\Transport;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Defines a factory which can create instances of `ServerRequest`.
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * Creates a new HTTP request from the supplied super-global values.
     *
     * @param null|array<string,mixed>  $server     The `$_SERVER` super-global value. Defaults to `$_SERVER`.
     * @param null|array<string,mixed>  $get        The `$_GET` super-global value. Defaults to `$_GET`.
     * @param null|array<string,mixed>  $post       The `$_POST` super-global value. Defaults to `$_POST`.
     * @param null|array<string,mixed>  $cookie     The `$_COOKIE` super-global value. Defaults to `$_COOKIE`.
     * @param null|array<string,mixed>  $files      The `$_FILES` super-global value. Defaults to `$_FILES`.
     *
     * @return Request
     */
    public function fromGlobals(
        null|array $server = null,
        null|array $get = null,
        null|array $post = null,
        null|array $cookie = null,
        null|array $files = null,
    ): RequestInterface {
        $server ??= $_SERVER;
        $get ??= $_GET;
        $post ??= $_POST;
        $cookie ??= $_COOKIE;
        $files ??= $_FILES;

        $headers = $this->formatServerHeaders($server);

        return new ServerRequest(
            $server['REQUEST_METHOD'] ?? 'GET',
            Uri::fromGlobals($server),
            $server,
            $headers,
            new ResourceStream(fopen('php://input', 'r')),
            null,
            $this->parseServerHttpProtocol($server),
        );
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     *
     * @param UriInterface|string   $uri
     * @param array<string,mixed>   $serverParams
     */
    public function createServerRequest(
        string $method,
        mixed $uri,
        array $serverParams = []
    ): ServerRequestInterface {
        return new ServerRequest(
            method: $method,
            uri: $uri,
            serverParams: $serverParams,
            body: new ResourceStream(fopen('php://temp', 'r'))
        );
    }

    /**
     * Parses the HTTP protocol version from the given SAPI values.
     *
     * @param array<string,string>  $server     Server values from SAPI.
     *
     * @return string
     */
    protected function parseServerHttpProtocol(array $server): string
    {
        if (!isset($server['SERVER_PROTOCOL'])) {
            return '1.1';
        }

        if (!preg_match('~^(HTTP/)?(?<version>\d+(?:\.\d)?)$~', $server['SERVER_PROTOCOL'], $matches)) {
            throw new \InvalidArgumentException("Invalid HTTP protocol version: " . $server['SERVER_PROTOCOL']);
        }

        return $matches['version'];
    }

    /**
     * Formats the headers given in the given SAPI values.
     *
     * @param array<string,string>  $server     Server values from SAPI.
     *
     * @return HeaderBag
     */
    protected function formatServerHeaders(array $server): HeaderBag
    {
        $headers = HeaderBag::empty();

        foreach ($server as $name => $value) {
            if (!is_string($name) || $name === '' || $value === '') {
                continue;
            }

            $name = strtolower($name);
            $name = strtr($name, '_', '-');

            if (str_starts_with($name, 'http-')) {
                $name = substr($name, 5);

                $headers->add($name, $value);
                continue;
            }

            if (in_array($name, ['content-type', 'content-length', 'content-md5'])) {
                $headers->add($name, $value);
                continue;
            }
        }

        return $headers;
    }
}

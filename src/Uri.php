<?php

namespace Brickhouse\Http\Transport;

class Uri extends \Laminas\Diactoros\Uri
{
    public static function new(string $uri = ''): Uri
    {
        return new Uri($uri);
    }

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

        $uri = $uri->withHost($server['SERVER_NAME']);
        $uri = $uri->withPort(intval($server['SERVER_PORT']));
        $uri = $uri->withPath(explode('?', $server['REQUEST_URI'])[0]);

        $queryString = explode('#', $server['QUERY_STRING'] ?? '');
        $queryParameters = $queryString[0];
        $fragment = $queryString[1] ?? '';

        $uri = $uri->withQuery($queryParameters);
        $uri = $uri->withFragment($fragment);

        return $uri;
    }
}

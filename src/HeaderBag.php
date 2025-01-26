<?php

namespace Brickhouse\Http\Transport;

class HeaderBag
{
    /**
     * Gets the header contents, keyed by the header name.
     *
     * @var array<string,array<int,string>>
     */
    protected array $headers = [];

    protected final function __construct()
    {
        //
    }

    /**
     * Undocumented function
     *
     * @return static
     */
    public static function empty(): static
    {
        return new static();
    }

    /**
     * Parses an array of header strings (e.g. `Host: localhost`) into an `HttpHeaderBag`.
     *
     * @param array<int,string>     $headers
     *
     * @return static
     */
    public static function parse(array $headers): static
    {
        $bag = static::empty();

        foreach ($headers as $headerString) {
            [$header, $value] = explode(":", $headerString, limit: 2);

            $bag->add($header, $value);
        }

        return $bag;
    }

    /**
     * Parses an array of header names and values into an `HttpHeaderBag`.
     *
     * @param array<string,string|list<string>> $headers
     *
     * @return static
     */
    public static function parseArray(array $headers): static
    {
        $bag = static::empty();

        foreach ($headers as $name => $values) {
            if (is_string($values)) {
                $bag->add($name, $values);
                continue;
            }

            foreach ($values as $value) {
                $bag->add($name, $value);
            }
        }

        return $bag;
    }

    /**
     * Gets all the headers in the header bag.
     *
     * @return array<string,list<string>>
     */
    public function all(): array
    {
        return $this->headers;
    }

    /**
     * Adds a new header to the header bag with the given name and value.
     * If the header already exists, it is appended.
     *
     * @param string $header
     * @param string $value
     *
     * @return void
     */
    public function add(string $header, string $value): void
    {
        [$header, $value] = self::format($header, $value);

        if (isset($this->headers[$header])) {
            $this->headers[$header][] = $value;
        } else {
            $this->headers[$header] = [$value];
        }
    }

    /**
     * Sets a new header to the header bag with the given name and value.
     *
     * @param string    $header
     * @param string    $value
     * @param bool      $overwrite  If the header already exists, defines whether it should be overwritten.
     *
     * @return void
     */
    public function set(string $header, string $value, bool $overwrite = true): void
    {
        [$header, $value] = self::format($header, $value);

        if (isset($this->headers[$header]) && !$overwrite) {
            return;
        }

        $this->headers[$header] = [$value];
    }

    /**
     * Removes a header from the header bag with the given name.
     *
     * @param string $header
     *
     * @return void
     */
    public function remove(string $header): void
    {
        [$header] = self::format($header);

        unset($this->headers[$header]);
    }

    /**
     * Gets the first instance of the given header, if it exists.
     * If multiple of the header exists, returns the first one.
     *
     * @param string $header
     *
     * @return ?string
     */
    public function get(string $header): ?string
    {
        $all = $this->getAll($header);
        if (empty($all)) {
            return null;
        }

        return $all[0];
    }

    /**
     * Gets all instances of the given header, if it exists. Otherwise, returns an empty array.
     *
     * @param string $header
     *
     * @return array<int,string>
     */
    public function getAll(string $header): array
    {
        [$header] = $this->format($header);

        return $this->headers[$header] ?? [];
    }

    /**
     * Gets whether the bag has the given header.
     * If `$value` is specified, also checks whether the header equals that value.
     *
     * @param string    $header
     * @param ?string   $value
     *
     * @return bool
     */
    public function has(string $header, ?string $value = null): bool
    {
        $headerValue = $this->get($header);

        if ($headerValue && $value !== null) {
            [, $value] = $this->format($header, $value);
            return strcasecmp($headerValue, $value) === 0;
        }

        if ($headerValue !== null) {
            return true;
        }

        return false;
    }

    /**
     * Formats the given header and value.
     *
     * @param string $header
     * @param string $value
     *
     * @return array{string,string}
     */
    public static function format(string $header, string $value = ''): array
    {
        return [
            trim(strtolower($header)),
            trim($value),
        ];
    }

    /**
     * Gets the `Accept` header value, if it exists.
     *
     * @return ?AcceptBag
     */
    public function accept(): ?AcceptBag
    {
        if (($accept = $this->get('accept')) !== null) {
            return AcceptBag::parse($accept);
        }

        return null;
    }

    /**
     * Gets the `Accept-Encoding` header value, if it exists.
     *
     * @return ?string
     */
    public function acceptEncoding(): ?string
    {
        return $this->get('accept-encoding');
    }

    /**
     * Gets the `Connection` header value, if it exists.
     *
     * @return ?string
     */
    public function connection(): ?string
    {
        return $this->get('connection');
    }

    /**
     * Gets the `Content-Encoding` header value, if it exists.
     *
     * @return ?string
     */
    public function contentEncoding(): ?string
    {
        return $this->get('content-encoding');
    }

    /**
     * Gets the `Content-Length` header value, if it exists.
     *
     * @return ?int
     */
    public function contentLength(): ?int
    {
        $value = $this->get('content-length');
        if ($value) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Gets the `Content-Type` header value, if it exists.
     *
     * @return ?string
     */
    public function contentType(): ?string
    {
        return $this->get('content-type');
    }

    /**
     * Gets the `User-Agent` header value, if it exists.
     *
     * @return ?string
     */
    public function userAgent(): ?string
    {
        return $this->get('user-agent');
    }

    /**
     * Gets the `Transfer-Encoding` header value, if it exists.
     *
     * @return ?string
     */
    public function transferEncoding(): ?string
    {
        return $this->get('transfer-encoding');
    }

    /**
     * Serializes all the headers in the header bag into their HTTP format.
     *
     * @return string
     */
    public function serialize(): string
    {
        $formatted = [];

        foreach ($this->headers as $key => $values) {
            foreach ($values as $value) {
                $formatted[] = "{$key}: {$value}";
            }
        }

        return join(Message::CHUNK_DELIMITER, $formatted);
    }
}

<?php

namespace Brickhouse\Http\Transport;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Defines a standard HTTP message.
 */
abstract class Message implements MessageInterface
{
    /**
     * Defines the delimiter between chunks in an HTTP message.
     */
    public const string CHUNK_DELIMITER = "\r\n";

    /**
     * Gets the header bag for the message.
     *
     * @var HeaderBag
     */
    public protected(set) HeaderBag $headers;

    /**
     * Contains the content of the request as a stream.
     *
     * @var StreamInterface
     */
    public protected(set) StreamInterface $body;

    public function __construct(
        ?HeaderBag $headers = null,
        protected ?int $contentLength = null,
        protected string $protocol = "1.1",
    ) {
        $this->headers = $headers ?? HeaderBag::empty();
    }

    /**
     * Get the length of the request in bytes.
     *
     * @return null|int
     */
    public function length(): ?int
    {
        return $this->contentLength;
    }

    /**
     * Set the length of the request in bytes.
     *
     * @param int $length
     *
     * @return void
     */
    public function setLength(int $length): void
    {
        $this->contentLength = $length;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     *
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     *
     * @param string $version HTTP protocol version
     *
     * @return static
     */
    public function withProtocolVersion(string $version): static
    {
        if (empty($version)) {
            throw new \InvalidArgumentException("HTTP protocol version cannot be empty.");
        }

        if (!preg_match('/^(1\.[01]|2(\.0)?)/', $version)) {
            throw new \InvalidArgumentException("Invalid or unsupported HTTP protocol version: {$version}");
        }

        $new = clone $this;
        $new->protocol = $version;

        return $new;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     *
     * @return string[][]
     */
    public function getHeaders(): array
    {
        return $this->headers->all();
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function hasHeader(string $name): bool
    {
        return $this->headers->has($name);
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function getHeader(string $name): array
    {
        return $this->headers->getAll($name);
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function getHeaderLine($name): string
    {
        $value = $this->getHeader($name);
        if (empty($value)) {
            return '';
        }

        return implode(',', $value);
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function withHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->headers->remove($name);

        if (is_array($value)) {
            foreach ($value as $headerValue) {
                $new->headers->add($name, $headerValue);
            }
        } else {
            $new->headers->set($name, $value);
        }

        return $new;
    }

    /**
     * Creates a new response with the given headers.
     * If the headers already exist within the response, they are overwritten.
     *
     * @param HeaderBag|array<string,string|list<string>>   $headers
     *
     * @return static
     */
    public function withHeaders(HeaderBag|array $headers): static
    {
        $new = clone $this;

        if (is_array($headers)) {
            $headers = HeaderBag::parseArray($headers);
        }

        foreach ($headers->all() as $name => $headers) {
            $new->headers->remove($name);

            foreach ($headers as $header) {
                $new->headers->add($name, $header);
            }
        }

        return $new;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function withAddedHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->headers->remove($name);

        if (is_array($value)) {
            foreach ($value as $headerValue) {
                $new->headers->add($name, $headerValue);
            }
        } else {
            $new->headers->add($name, $value);
        }

        return $new;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function withoutHeader($name): static
    {
        $new = clone $this;
        $new->headers->remove($name);

        return $new;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function withBody(StreamInterface $body): static
    {
        $new = clone $this;
        $new->body = $body;

        return $new;
    }

    /**
     * Sets the body content of the HTTP message.
     *
     * @param StreamInterface   $body
     *
     * @return self
     */
    public function setBody(StreamInterface $body): self
    {
        $this->body = $body;

        return $this;
    }
}

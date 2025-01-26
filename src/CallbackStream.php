<?php

namespace Brickhouse\Http\Transport;

use Psr\Http\Message\StreamInterface;

/**
 * Stream implementation which retrieves values from a callback.
 */
class CallbackStream implements StreamInterface, \Stringable
{
    /**
     * Gets the inner callback of the stream.
     *
     * @var null|callable
     */
    protected $callback = null;

    /**
     * @param callable     $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function close(): void
    {
        $this->callback = null;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function detach(): null|callable
    {
        $callback = $this->callback;

        $this->callback = null;

        return $callback;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function getSize(): int|null
    {
        return null;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function tell(): int
    {
        throw new \RuntimeException("Tell is not supported.");
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function eof(): bool
    {
        return $this->callback === null;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function isSeekable(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        throw new \RuntimeException("Seeking is not supported.");
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function rewind(): void
    {
        throw new \RuntimeException("Seeking is not supported.");
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function isWritable(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function write(string $string): int
    {
        throw new \RuntimeException("Writing is not supported.");
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function isReadable(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function read(int $length): string
    {
        throw new \RuntimeException("Reading is not supported.");
    }

    /**
     * @inheritDoc
     *
     * Blocks until all remaining content can be read.
     *
     * Exists to confirm to PSR-7.
     */
    public function getContents(): string
    {
        $callback = $this->detach();
        $contents = $callback !== null ? $callback() : '';

        return (string) $contents;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function getMetadata(null|string $key = null): mixed
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->getContents();
    }
}

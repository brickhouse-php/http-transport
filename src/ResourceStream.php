<?php

namespace Brickhouse\Http\Transport;

use Psr\Http\Message\StreamInterface;

/**
 * Stream implementation which contains an underlying resource stream.
 */
class ResourceStream implements StreamInterface, \Stringable
{
    /**
     * Gets or sets the underlying resource stream.
     *
     * @var null|resource
     */
    protected $resource;

    /**
     * @param resource  $resource       The underlying resource to read and write to.
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function close(): void
    {
        if ($this->resource !== null) {
            fclose($this->resource);
        }
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function detach()
    {
        $resource = $this->resource;

        $this->resource = null;

        return $resource;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function getSize(): int|null
    {
        if ($this->resource === null) {
            return null;
        }

        $stats = fstat($this->resource);
        if (!$stats) {
            return null;
        }

        return $stats['size'] ?? null;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function tell(): int
    {
        return ftell($this->resource);
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function eof(): bool
    {
        return feof($this->resource);
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function isSeekable(): bool
    {
        return stream_get_meta_data($this->resource)['seekable'];
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        fseek($this->resource, $offset, $whence);
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function rewind(): void
    {
        rewind($this->resource);
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function isWritable(): bool
    {
        $mode = stream_get_meta_data($this->resource)['mode'];

        return in_array(
            $mode,
            ['r+', 'w', 'w+', 'a+', 'x', 'x+', 'c', 'c+'],
            strict: true
        );
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function write(string $string): int
    {
        $bytesWritten = @fwrite($this->resource, $string);
        if ($bytesWritten === false) {
            throw new \RuntimeException(
                "Failed to write to resource stream: " . (error_get_last()['message'] ?? 'unknown error')
            );
        }

        return $bytesWritten;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function isReadable(): bool
    {
        $mode = stream_get_meta_data($this->resource)['mode'];

        return in_array(
            $mode,
            ['r', 'r+', 'w+', 'a+', 'x+', 'c+'],
            strict: true
        );
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function read(int $length): string
    {
        $result = @fread($this->resource, $length);
        if ($result === false) {
            throw new \RuntimeException(
                "Failed to read from resource stream: " . (error_get_last()['message'] ?? 'unknown error')
            );
        }

        return $result;
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
        if (!$this->isReadable()) {
            throw new \RuntimeException("Failed to read from resource stream: stream is not readable");
        }

        $result = @stream_get_contents($this->resource);
        if ($result === false) {
            throw new \RuntimeException(
                "Failed to read from resource stream: " . (error_get_last()['message'] ?? 'unknown error')
            );
        }

        return $result;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function getMetadata(null|string $key = null): mixed
    {
        $metadata = [];
        if ($this->resource !== null) {
            $metadata = stream_get_meta_data($this->resource);
        }

        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        if (!$this->isReadable()) {
            return '';
        }

        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (\RuntimeException) {
            return '';
        }
    }
}

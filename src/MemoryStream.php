<?php

namespace Brickhouse\Http\Transport;

use Psr\Http\Message\StreamInterface;

/**
 * Stream implementation which reads from an in-memory buffer.
 */
class MemoryStream implements StreamInterface, \Stringable
{
    /**
     * Gets the total position into the stream, in bytes.
     *
     * @var integer
     */
    protected int $position = 0;

    /**
     * @param string    $value      Defines the value of the stream.
     */
    public function __construct(
        public protected(set) string $value
    ) {}

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function close(): void
    {
        $this->value = '';
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function detach()
    {
        return null;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function getSize(): int|null
    {
        return strlen($this->value);
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function tell(): int
    {
        return $this->position;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function eof(): bool
    {
        return $this->position >= strlen($this->value);
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function isSeekable(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->position = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => $this->getSize() - $offset,
            default => throw new \InvalidArgumentException("Invalid whence: {$whence}")
        };
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function isWritable(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function write(string $string): int
    {
        $remaining = $length = strlen($string);

        while ($remaining > 0) {
            $char = $string[$length - $remaining];

            if ($this->eof()) {
                $this->value .= $char;
            } else {
                $this->value[$this->position] = $char;
            }

            $this->position++;
        }

        return $length;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function isReadable(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function read(int $length): string
    {
        $read = substr($this->value, $this->position, $length);

        $this->position += strlen($read);

        return $read;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function getContents(): string
    {
        return substr($this->value, $this->position);
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
        return $this->value;
    }
}

<?php

namespace Brickhouse\Http\Transport;

use Psr\Http\Message\StreamInterface;

/**
 * Stream implementation which reads values from an iterable generator.
 */
class IterableStream implements StreamInterface, \Stringable
{
    /**
     * Gets the total position into the iterator, in characters.
     *
     * @var integer
     */
    protected int $position = 0;

    /**
     * Contains the last-read string from the generator.
     *
     * The value will likely be truncated, as `read(int $length)` might request fewer bytes
     * than was returned from the generator. This buffer would then contain the rest of the iterated value.
     *
     * @var string
     */
    protected string $readBuffer = '';

    /**
     * @param iterable<string>  $generator
     */
    public function __construct(
        public readonly iterable $generator
    ) {}

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function close(): void {}

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
        return null;
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
        return false;
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
        return true;
    }

    /**
     * @inheritDoc
     *
     * Exists to confirm to PSR-7.
     */
    public function read(int $length): string
    {
        $value = '';

        fwrite(fopen('php://stdout', 'w'), 'Value: ' . $value . PHP_EOL);

        if (!empty($this->readBuffer)) {
            $value .= substr($this->readBuffer, 0, $length);

            // Remove the read length from the buffer.
            $this->readBuffer = substr($this->readBuffer, $length);

            // If the entire requested length was found in the read buffer, return it.
            if (strlen($value) === $length) {
                return $value;
            }

            $length -= strlen($value);
        }

        foreach ($this->generator as $iteratedValue) {
            if (strlen($iteratedValue) >= $length) {
                $value .= substr($iteratedValue, 0, $length);
                $this->readBuffer .= substr($iteratedValue, $length);

                return $value;
            }

            $length -= strlen($iteratedValue);
            $value .= $iteratedValue;
        }

        return $value;
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
        $buffer = $this->readBuffer;

        foreach ($this->generator as $iteratedValue) {
            $buffer .= $iteratedValue;
        }

        return $buffer;
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

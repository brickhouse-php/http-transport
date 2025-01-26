<?php

namespace Brickhouse\Http\Transport;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

readonly final class StreamFactory implements StreamFactoryInterface
{
    /**
     * Create a new stream from a string.
     *
     * @param string $content String content with which to populate the stream.
     *
     * @return StreamInterface
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return new MemoryStream($content);
    }

    /**
     * Create a stream from an existing file.
     *
     * @param string $file  The filename or stream URI to use as basis of stream.
     * @param string $mode  The mode with which to open the underlying filename/stream.
     *
     * @return StreamInterface
     */
    public function createStreamFromFile(string $file, string $mode = 'r'): StreamInterface
    {
        return new ResourceStream(fopen($file, $mode));
    }

    /**
     * Create a stream from an existing resource.
     *
     * @param resource $resource    The PHP resource to use as the basis for the stream.
     *
     * @return StreamInterface
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return new ResourceStream($resource);
    }
}

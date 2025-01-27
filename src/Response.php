<?php

namespace Brickhouse\Http\Transport;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Defines a standard, client-bound HTTP response.
 */
class Response extends Message implements ResponseInterface
{
    /**
     * Defines all the header names which should only be sent once.
     *
     * @var list<string>
     */
    private const array SINGLE_HEADER_NAMES = [
        'Connection',
        'Content-encoding',
        'Content-Length',
        'Host',
        'Transfer-Encoding',
    ];

    /**
     * Gets or sets the HTTP status code of the response.
     *
     * @var integer
     */
    public int $status = Status::OK;

    public function __construct(
        int $status = Status::OK,
        ?HeaderBag $headers = null,
        StreamInterface|string $body = "",
        ?int $contentLength = null,
        string $protocol = "1.1"
    ) {
        parent::__construct($headers, $contentLength, $protocol);

        $this->status = $status;
        $this->setBody($body);
    }

    /**
     * Creates a new `Response` instance with the given content and status code.
     *
     * @param null|StreamInterface|string   $body
     * @param int                           $status
     *
     * @return static
     */
    public static function new(null|StreamInterface|string $body = null, $status = Status::OK): static
    {
        // @phpstan-ignore new.static
        $response = new static();

        if ($body !== null) {
            $response->setBody($body);
        }

        $response->status = $status;

        return $response;
    }

    /**
     * Creates a new `Response` instance to redirect to another url.
     *
     * @param string    $url
     * @param int       $status
     *
     * @return static
     */
    public static function redirect(string $url, int $status = Status::TEMPORARY_REDIRECT): static
    {
        $response = self::new(status: $status);
        $response->headers->set('Location', $url);

        return $response;
    }

    /**
     * Creates a new `Response` instance with JSON content.
     *
     * @param mixed     $content
     *
     * @return static
     */
    public static function json(mixed $content): static
    {
        $response = self::new();

        $content = match (true) {
            $content instanceof \JsonSerializable => json_encode($content->jsonSerialize()),
            $content instanceof \Serializable => $content->serialize(),
            is_object($content) && method_exists($content, 'toArray') => json_encode($content->toArray()),
            default => json_encode($content),
        };

        // @phpstan-ignore return.type
        return $response
            ->setContentType(ContentType::JSON)
            ->setBody($content);
    }

    /**
     * Creates a new `Response` instance with HTML content.
     *
     * @param string    $content
     *
     * @return static
     */
    public static function html(string $content): static
    {
        $response = self::new();

        // @phpstan-ignore return.type
        return $response
            ->setContentType(ContentType::HTML)
            ->setBody($content);
    }

    /**
     * Creates a new `Response` instance with text content.
     *
     * @param string    $content
     * @param string    $contentType
     *
     * @return static
     */
    public static function text(string $content, string $contentType = ContentType::TXT): static
    {
        $response = self::new();

        // @phpstan-ignore return.type
        return $response
            ->setContentType($contentType)
            ->setBody($content);
    }

    /**
     * Sets the `Content-Type` header on the response.
     *
     * @param string    $type
     *
     * @return self
     */
    public function setContentType(string $type): self
    {
        $this->headers->set('Content-Type', $type);

        return $this;
    }

    /**
     * Sets the body content of the HTTP message.
     *
     * @param string|StreamInterface    $body
     *
     * @return self
     */
    public function setBody(string|StreamInterface $body): self
    {
        if ($body instanceof StreamInterface) {
            $this->body = $body;
            $this->headers->remove("content-length");

            return $this;
        }

        $this->body = new MemoryStream($body);
        $this->headers->set("content-length", (string) \strlen($body));
        $this->contentLength = \strlen($body);

        return $this;
    }

    /**
     * Sets the callback to be invoked once the response is sent back to the client.
     *
     * @param \Closure(): void  $onUpgrade
     *
     * @return void
     */
    public function upgrade(\Closure $onUpgrade): void
    {
        $this->status = Status::SWITCHING_PROTOCOLS;
    }

    /**
     * Sends the response to the output buffer.
     *
     * @return void
     */
    public function send(bool $flush = true): void
    {
        $this->sendHeaders();
        $this->sendContent();

        if (!$flush) {
            return;
        }

        // If we're running FastCGI - as opposed to CLI - let it know the request is done.
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            ob_flush();
        }
    }

    /**
     * Sends the headers of the response to the output buffer.
     *
     * @return void
     */
    protected function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code($this->status);

        foreach ($this->headers->all() as $header => $values) {
            $replace = isset(self::SINGLE_HEADER_NAMES[$header]);

            foreach ($values as $value) {
                header($header . ': ' . $value, $replace);
            }
        }
    }

    /**
     * Sends the content of the response to the output buffer.
     *
     * @return void
     */
    protected function sendContent(): void
    {
        if (!$this->body->isReadable()) {
            return;
        }

        echo $this->body;
    }

    /**
     * Gets whether the response is successful (i.e. status code is 200-299).
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->status >= Status::OK && $this->status < Status::MULTIPLE_CHOICES;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $new = clone $this;
        $new->status = $code;

        return $new;
    }

    /**
     * @inheritDoc
     *
     * Exists to conform with PSR-7.
     */
    public function getReasonPhrase(): string
    {
        return Status::getReason($this->status);
    }
}

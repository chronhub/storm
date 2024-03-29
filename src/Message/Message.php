<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message;

use Chronhub\Storm\Contracts\Reporter\Reporting;
use InvalidArgumentException;
use RuntimeException;

final class Message
{
    use HasHeaders;

    private object $event;

    public function __construct(object $event, array $headers = [])
    {
        if ($event instanceof self) {
            throw new InvalidArgumentException('Message event cannot be an instance of itself');
        }

        if (! $event instanceof Reporting) {
            $this->event = $event;
            $this->headers = $headers;

            return;
        }

        $expectedHeaders = match (true) {
            $event->headers() === [], $event->headers() === $headers => $headers,
            $headers === [] => $event->headers(),
            default => throw new RuntimeException('Invalid headers consistency for event class '.$event::class)
        };

        $this->event = $event->withHeaders([]);
        $this->headers = $expectedHeaders;
    }

    public function event(): object
    {
        if ($this->event instanceof Reporting) {
            return clone $this->event->withHeaders($this->headers);
        }

        return clone $this->event;
    }

    public function withHeader(string $key, mixed $value): self
    {
        $message = clone $this;

        $message->headers[$key] = $value;

        return $message;
    }

    public function withHeaders(array $headers): self
    {
        $message = clone $this;

        $message->headers = $headers;

        return $message;
    }

    public function isMessaging(): bool
    {
        return $this->event instanceof Reporting;
    }
}

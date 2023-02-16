<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message;

trait HasHeaders
{
    /**
     * @var array<string, null|int|float|string|bool|array|object>
     */
    protected array $headers = [];

    public function header(string $key): null|int|float|string|bool|array|object
    {
        return $this->headers[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->headers[$key]);
    }

    public function hasNot(string $key): bool
    {
        return ! isset($this->headers[$key]);
    }

    public function headers(): array
    {
        return $this->headers;
    }
}

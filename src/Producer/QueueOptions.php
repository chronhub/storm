<?php

declare(strict_types=1);

namespace Chronhub\Storm\Producer;

use JsonSerializable;

class QueueOptions implements JsonSerializable
{
    public function __construct(
        public readonly ?string $connection = null,
        public readonly ?string $name = null,
        public readonly ?int $tries = null,
        public readonly ?int $maxExceptions = null,
        public readonly null|int|string $delay = null,
        public readonly ?int $timeout = null,
        public readonly ?int $backoff = null,
        ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'connection' => $this->connection,
            'name' => $this->name,
            'tries' => $this->tries,
            'max_exceptions' => $this->maxExceptions,
            'delay' => $this->delay,
            'timeout' => $this->timeout,
            'backoff' => $this->backoff,
        ];
    }
}

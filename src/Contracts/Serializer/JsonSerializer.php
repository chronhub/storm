<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Serializer;

interface JsonSerializer
{
    public function encode(mixed $data, array $context = []): string;

    public function decode(string $data): mixed;
}

<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Reporter;

use Chronhub\Storm\Reporter\DomainType;

interface Reporting
{
    /**
     * @param array<string, int|float|string|bool|array|object|null> $content
     */
    public static function fromContent(array $content): static;

    /**
     * @return array<string, int|float|string|bool|array|object|null>
     */
    public function toContent(): array;

    /**
     * @param array<string, int|float|string|bool|array|object|null> $headers
     */
    public function withHeaders(array $headers): static;

    public function withHeader(string $header, int|float|string|bool|array|object|null $value): static;

    public function has(string $key): bool;

    public function hasNot(string $key): bool;

    public function header(string $key): int|float|string|bool|array|object|null;

    /**
     * @return array<string, int|float|string|bool|array|object|null>
     */
    public function headers(): array;

    public function type(): DomainType;
}

<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Reporter;

use Chronhub\Storm\Reporter\DomainType;

interface Reporting
{
    /**
     * @param  array<string, null|int|float|string|bool|array|object>  $content
     */
    public static function fromContent(array $content): static;

    /**
     * @return array<string, null|int|float|string|bool|array|object>
     */
    public function toContent(): array;

    /**
     * @param  array<string, null|int|float|string|bool|array|object>  $headers
     */
    public function withHeaders(array $headers): static;

    public function withHeader(string $header, null|int|float|string|bool|array|object $value): static;

    public function has(string $key): bool;

    public function hasNot(string $key): bool;

    public function header(string $key): null|int|float|string|bool|array|object;

    /**
     * @return array<string, null|int|float|string|bool|array|object>
     */
    public function headers(): array;

    public function type(): DomainType;
}

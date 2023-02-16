<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Reporter;

use Chronhub\Storm\Reporter\DomainType;

interface Reporting
{
    /**
     * Create new instance of domain from his content
     *
     * @param  array<string, null|int|float|string|bool|array|object>  $content
     */
    public static function fromContent(array $content): static;

    /**
     * Return message content
     *
     * @return array<string, null|int|float|string|bool|array|object>
     */
    public function toContent(): array;

    /**
     * Override all message headers and return new instance
     *
     * @param  array<string, null|int|float|string|bool|array|object>  $headers
     */
    public function withHeaders(array $headers): static;

    /**
     * Add header to message header and return new instance
     * Override header is allowed
     */
    public function withHeader(string $header, null|int|float|string|bool|array|object $value): static;

    /**
     * Check existence of message header
     */
    public function has(string $key): bool;

    /**
     * Check non-existence of message header
     */
    public function hasNot(string $key): bool;

    /**
     * Return message header
     */
    public function header(string $key): null|int|float|string|bool|array|object;

    /**
     * Return all headers
     *
     * @return array<string, null|int|float|string|bool|array|object>
     */
    public function headers(): array;

    /**
     * Return current domain type
     */
    public function type(): DomainType;
}

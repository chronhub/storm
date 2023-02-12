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
     * @return static
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
     * @return static
     */
    public function withHeaders(array $headers): static;

    /**
     * Add header to message header and return new instance
     * Override header is allowed
     *
     * @param  string  $header
     * @param  null|int|float|string|bool|array|object  $value
     * @return static
     */
    public function withHeader(string $header, null|int|float|string|bool|array|object $value): static;

    /**
     * Check existence of message header
     *
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Check non-existence of message header
     *
     * @param  string  $key
     * @return bool
     */
    public function hasNot(string $key): bool;

    /**
     * Return message header
     *
     * @param  string  $key
     * @return null|int|float|string|bool|array|object
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
     *
     * @return DomainType
     */
    public function type(): DomainType;
}

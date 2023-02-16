<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

use Chronhub\Storm\Message\HasHeaders;

trait HasDomain
{
    use HasHeaders;

    public function withHeader(string $header, null|int|float|string|bool|array|object $value): static
    {
        $domain = clone $this;

        $domain->headers[$header] = $value;

        return $domain;
    }

    public function withHeaders(array $headers): static
    {
        $domain = clone $this;

        $domain->headers = $headers;

        return $domain;
    }
}

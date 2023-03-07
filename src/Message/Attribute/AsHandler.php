<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class AsHandler
{
    public string $domain;

    public string $method;

    public function __construct(string $domain, ?string $method = null)
    {
        $this->domain = $domain;
        $this->method = $method ?? '__invoke';
    }
}

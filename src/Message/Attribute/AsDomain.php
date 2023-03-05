<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class AsDomain
{
    public array $handlers;

    public string $method;

    public ?string $type;

    public function __construct(?string $type = null, string $method = '__invoke', string ...$handlers)
    {
        $this->type = $type;
        $this->method = $method;
        $this->handlers = $handlers;
    }
}

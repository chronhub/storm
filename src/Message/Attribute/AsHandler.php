<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class AsHandler
{
    public array $arguments;

    public function __construct(public ?string $method = null, string ...$arguments)
    {
        $this->arguments = $arguments;
    }
}

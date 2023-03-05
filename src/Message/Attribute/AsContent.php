<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final readonly class AsContent
{
    public function __construct(public array $content)
    {
    }
}

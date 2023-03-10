<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class AsAggregateChanged
{
    public function __construct(public string $aggregateRoot,
                                public string $aggregateId,
                                public array $content = [])
    {
    }
}

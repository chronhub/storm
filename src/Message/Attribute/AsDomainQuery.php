<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message\Attribute;

use Attribute;
use InvalidArgumentException;
use function count;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class AsDomainQuery
{
    public array $handlers;

    public string $method;

    public array $content;

    public function __construct(array $content, ?string $targetMethod = null, string ...$handlers)
    {
        if (count($handlers) !== 1) {
            throw new InvalidArgumentException('One handler only must be provided');
        }

        $this->method = $targetMethod;
        $this->handlers = $handlers;
        $this->content = $content;
    }
}

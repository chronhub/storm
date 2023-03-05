<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message\Attribute;

use Attribute;
use InvalidArgumentException;
use function count;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class AsNotificationEvent
{
    public array $handlers;

    public bool $allowEmpty;

    public array $content;

    public function __construct(array $content, bool $allowEmpty = false, string ...$handlers)
    {
        if (! $allowEmpty && count($handlers) === 0) {
            throw new InvalidArgumentException('At least one handler must be provided');
        }

        $this->handlers = $handlers;
        $this->allowEmpty = $allowEmpty;
        $this->content = $content;
    }
}

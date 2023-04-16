<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message;

use Chronhub\Storm\Contracts\Message\MessageAlias;
use InvalidArgumentException;
use function class_exists;
use function sprintf;

final class AliasFromClassName implements MessageAlias
{
    public function classToAlias(string $eventClass): string
    {
        if (! class_exists($eventClass)) {
            throw new InvalidArgumentException(sprintf('Event class %s does not exists', $eventClass));
        }

        return $eventClass;
    }

    public function instanceToAlias(object $event): string
    {
        return $event::class;
    }
}

<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message;

use InvalidArgumentException;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use function class_exists;

final class AliasFromClassName implements MessageAlias
{
    public function classToAlias(string $eventClass): string
    {
        if (! class_exists($eventClass)) {
            throw new InvalidArgumentException("Event class $eventClass does not exists");
        }

        return $eventClass;
    }

    public function instanceToAlias(object $event): string
    {
        return $event::class;
    }
}

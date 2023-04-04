<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message;

use Chronhub\Storm\Contracts\Message\MessageAlias;
use InvalidArgumentException;
use function class_exists;

/**
 * @deprecated
 */
final readonly class AliasFromMap implements MessageAlias
{
    public function __construct(private iterable $map)
    {
    }

    public function classToAlias(string $eventClass): string
    {
        return $this->determineAlias($eventClass);
    }

    public function instanceToAlias(object $event): string
    {
        return $this->determineAlias($event::class);
    }

    private function determineAlias(string $eventClass): string
    {
        if (! class_exists($eventClass)) {
            throw new InvalidArgumentException("Event class $eventClass does not exists");
        }

        if ($alias = $this->map[$eventClass] ?? null) {
            return $alias;
        }

        throw new InvalidArgumentException("Event class $eventClass not found in alias map");
    }
}

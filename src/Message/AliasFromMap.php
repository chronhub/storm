<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message;

use Chronhub\Storm\Contracts\Message\MessageAlias;
use InvalidArgumentException;
use function class_exists;
use function sprintf;

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
            throw new InvalidArgumentException(sprintf('Event class %s does not exists', $eventClass));
        }

        if ($alias = $this->map[$eventClass] ?? null) {
            return $alias;
        }

        throw new MessageAliasNotFound(sprintf('Event class %s not found in alias map', $eventClass));
    }
}

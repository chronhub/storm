<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tracker;

use Chronhub\Storm\Contracts\Tracker\Listener;
use ReflectionFunction;

final class GenericListener implements Listener
{
    /**
     * @var callable
     */
    private $story;

    public function __construct(
        public readonly string $eventName,
        callable $story,
        public readonly int $eventPriority
    ) {
        $this->story = $story;
    }

    public function name(): string
    {
        return $this->eventName;
    }

    public function priority(): int
    {
        return $this->eventPriority;
    }

    public function story(): callable
    {
        return $this->story;
    }

    public function origin(): string
    {
        $origin = new ReflectionFunction($this->story);

       return $origin->getClosureScopeClass()->name;
    }
}

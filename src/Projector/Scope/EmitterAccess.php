<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterManagement;
use Chronhub\Storm\Contracts\Projector\EmitterScope;
use Chronhub\Storm\Contracts\Projector\Management;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Reporter\DomainEvent;
use DateTimeImmutable;

/**
 * @method mixed                    id()
 * @method string|DateTimeImmutable time()
 * @method array                    content()
 * @method int                      internalPosition()
 */
final class EmitterAccess implements ArrayAccess, EmitterScope
{
    use ScopeBehaviour;

    protected ?EmitterManagement $management = null;

    public function emit(DomainEvent $event): void
    {
        $this->management->emit($event);
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $this->management->linkTo($streamName, $event);
    }

    public function stop(): void
    {
        $this->management->close();
    }

    public function streamName(): string
    {
        return $this->management->getCurrentStreamName();
    }

    public function clock(): SystemClock
    {
        return $this->management->getClock();
    }

    protected function setManagement(Management $management): void
    {
        if (! $management instanceof EmitterManagement) {
            throw new RuntimeException('Management must be an instance of '.EmitterManagement::class);
        }

        $this->management = $management;
    }
}

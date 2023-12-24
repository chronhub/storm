<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\Management;
use Chronhub\Storm\Contracts\Projector\ReadModelManagement;
use Chronhub\Storm\Contracts\Projector\ReadModelScope;
use Chronhub\Storm\Contracts\Projector\StackedReadModel;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use DateTimeImmutable;

/**
 * @method mixed                    id()
 * @method string|DateTimeImmutable time()
 * @method array                    content()
 * @method int                      internalPosition()
 */
final class ReadModelAccess implements ArrayAccess, ReadModelScope
{
    use ScopeBehaviour;

    protected ?ReadModelManagement $management = null;

    public function stop(): void
    {
        $this->management->close();
    }

    public function readModel(): StackedReadModel
    {
        return $this->management->getReadModel();
    }

    public function stack(string $operation, ...$arguments): self
    {
        $this->management->getReadModel()->stack($operation, ...$arguments);

        return $this;
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
        if (! $management instanceof ReadModelManagement) {
            throw new RuntimeException('Management must be an instance of '.ReadModelManagement::class);
        }

        $this->management = $management;
    }
}

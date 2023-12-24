<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\Management;
use Chronhub\Storm\Contracts\Projector\QueryManagement;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScope;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use DateTimeImmutable;

/**
 * @method mixed                    id()
 * @method string|DateTimeImmutable time()
 * @method array                    content()
 * @method int                      internalPosition()
 */
final class QueryAccess implements ArrayAccess, QueryProjectorScope
{
    use ScopeBehaviour;

    protected ?QueryManagement $management = null;

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
        if (! $management instanceof QueryManagement) {
            throw new RuntimeException('Management must be an instance of '.QueryManagement::class);
        }

        $this->management = $management;
    }
}

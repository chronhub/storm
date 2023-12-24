<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\QueryManagement;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScope;
use DateTimeImmutable;

/**
 * @method mixed                    id()
 * @method string|DateTimeImmutable time()
 * @method array                    content()
 * @method int                      internalPosition()
 */
final class QueryAccess implements ArrayAccess, QueryProjectorScope
{
    use AccessBehaviour;

    public function __construct(private readonly QueryManagement $management)
    {
    }

    public function stop(): void
    {
        $this->management->stop();
    }

    public function streamName(): string
    {
        return $this->management->getCurrentStreamName();
    }

    public function clock(): SystemClock
    {
        return $this->management->getClock();
    }
}

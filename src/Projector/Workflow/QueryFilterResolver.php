<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\LoadLimiterProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\StreamNameAwareQueryFilter;

class QueryFilterResolver
{
    public function __construct(private readonly QueryFilter $queryFilter)
    {
    }

    public function __invoke(string $streamName, int $nextPosition, int $limit): QueryFilter
    {
        $queryFilter = $this->queryFilter;

        if ($queryFilter instanceof StreamNameAwareQueryFilter) {
            $queryFilter->setStreamName($streamName);
        }

        if ($queryFilter instanceof LoadLimiterProjectionQueryFilter) {
            $queryFilter->setLoadLimiter($limit);
        }

        if ($queryFilter instanceof ProjectionQueryFilter) {
            $queryFilter->setStreamPosition($nextPosition);
        }

        return $queryFilter;
    }
}

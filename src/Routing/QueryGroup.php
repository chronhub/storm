<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing;

use Chronhub\Storm\Reporter\DomainType;

final class QueryGroup extends Group
{
    public function getType(): DomainType
    {
        return DomainType::QUERY;
    }
}

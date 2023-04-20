<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

use Chronhub\Storm\Contracts\Reporter\Reporting;
use Chronhub\Storm\Reporter\Concern\HasDomain;

abstract class DomainQuery implements Reporting
{
    use HasDomain;

    public function type(): DomainType
    {
        return DomainType::QUERY;
    }
}

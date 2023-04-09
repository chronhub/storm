<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

use Chronhub\Storm\Contracts\Reporter\Reporting;

abstract class DomainCommand implements Reporting
{
    use HasDomain;

    public function type(): DomainType
    {
        return DomainType::COMMAND;
    }
}

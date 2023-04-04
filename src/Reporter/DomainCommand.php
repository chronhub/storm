<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

use Chronhub\Storm\Contracts\Reporter\Reporting;
use Chronhub\Storm\Message\HasHeaders;

abstract class DomainCommand implements Reporting
{
    use HasHeaders;
    use HasDomain;

    public function type(): DomainType
    {
        return DomainType::COMMAND;
    }
}

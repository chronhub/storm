<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs;

use Chronhub\Storm\Routing\Group;
use Chronhub\Storm\Reporter\DomainType;

final class GroupStub extends Group
{
    public function getType(): DomainType
    {
        return DomainType::COMMAND;
    }
}

<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs;

use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\Group;

final class GroupStub extends Group
{
    public function getType(): DomainType
    {
        return DomainType::COMMAND;
    }
}

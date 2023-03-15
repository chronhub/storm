<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs\Double;

use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Message\HasConstructableContent;

final class SomeEvent extends DomainEvent
{
    use HasConstructableContent;
}

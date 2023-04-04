<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs\Double;

use Chronhub\Storm\Message\HasConstructableContent;
use Chronhub\Storm\Reporter\DomainCommand;

final class SomeCommand extends DomainCommand
{
    use HasConstructableContent;
}

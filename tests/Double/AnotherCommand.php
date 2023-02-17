<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Double;

use Chronhub\Storm\Reporter\DomainCommand;
use Chronhub\Storm\Message\HasConstructableContent;

final class AnotherCommand extends DomainCommand
{
    use HasConstructableContent;
}

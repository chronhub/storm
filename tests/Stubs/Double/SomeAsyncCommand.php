<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs\Double;

use Chronhub\Storm\Reporter\DomainCommand;
use Chronhub\Storm\Contracts\Message\AsyncMessage;
use Chronhub\Storm\Message\HasConstructableContent;

final class SomeAsyncCommand extends DomainCommand implements AsyncMessage
{
    use HasConstructableContent;
}

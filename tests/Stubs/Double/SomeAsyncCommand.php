<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs\Double;

use Chronhub\Storm\Contracts\Message\AsyncMessage;
use Chronhub\Storm\Message\HasConstructableContent;
use Chronhub\Storm\Reporter\DomainCommand;

final class SomeAsyncCommand extends DomainCommand implements AsyncMessage
{
    use HasConstructableContent;
}

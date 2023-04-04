<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Message\Attribute\AsHandler;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use StdClass;

#[AsHandler(SomeCommand::class, 'command')]
class AsHandlerStub
{
    public function command(StdClass $class): void
    {
    }
}

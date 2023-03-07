<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use StdClass;
use Chronhub\Storm\Tests\Double\SomeCommand;
use Chronhub\Storm\Message\Attribute\AsHandler;

#[AsHandler(SomeCommand::class, 'command')]
class AsHandlerStub
{
    public function command(StdClass $class): void
    {
    }
}

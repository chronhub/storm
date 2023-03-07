<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Reporter\DomainCommand;
use Chronhub\Storm\Message\HasConstructableContent;
use Chronhub\Storm\Message\Attribute\AsDomainCommand;

#[AsDomainCommand(['foo' => 'bar'], 'command', 'SomeCommandHandler')]
class AsDomainCommandStub extends DomainCommand
{
    use HasConstructableContent;
}

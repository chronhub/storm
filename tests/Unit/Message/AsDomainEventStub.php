<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Message\Attribute\AsDomainEvent;
use Chronhub\Storm\Message\HasConstructableContent;

#[AsDomainEvent(['foo' => 'bar'], true, null, 'SomeEventHandler1', 'SomeEventHandler2')]
class AsDomainEventStub extends DomainEvent
{
    use HasConstructableContent;
}
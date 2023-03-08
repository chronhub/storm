<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Message\HasConstructableContent;
use Chronhub\Storm\Message\Attribute\AsNotificationEvent;

#[AsNotificationEvent(['foo' => 'bar'], true, 'SomeEventHandler1', 'SomeEventHandler2')]
class AsNotificationEventStub extends DomainEvent
{
    use HasConstructableContent;
}

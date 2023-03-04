<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Tracker;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tracker\GenericListener;
use Chronhub\Storm\Contracts\Tracker\Listener;

final class GenericListenerTest extends UnitTestCase
{
    #[Test]
    public function it_assert_listener(): void
    {
        $story = fn (): bool => false;

        $listener = new GenericListener('finalize', $story, -100);

        $this->assertInstanceOf(Listener::class, $listener);

        $this->assertEquals('finalize', $listener->eventName);
        $this->assertEquals('finalize', $listener->name());
        $this->assertEquals(-100, $listener->eventPriority);
        $this->assertEquals(-100, $listener->priority());
        $this->assertSame($story, $listener->callback());
    }
}

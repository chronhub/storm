<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Tracker;

use Chronhub\Storm\Contracts\Tracker\Listener;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\GenericListener;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(GenericListener::class)]
final class GenericListenerTest extends UnitTestCase
{
    #[DataProvider('provideEvent')]
    public function testListener(string $event): void
    {
        $story = fn (): bool => false;

        $listener = new GenericListener($event, $story, -100);

        $this->assertInstanceOf(Listener::class, $listener);

        $this->assertEquals($event, $listener->eventName);
        $this->assertEquals($event, $listener->name());
        $this->assertEquals(-100, $listener->eventPriority);
        $this->assertEquals(-100, $listener->priority());
        $this->assertSame($story, $listener->story());
        $this->assertSame(self::class, $listener->origin());
    }

    public static function provideEvent(): Generator
    {
        yield ['dispatch'];
        yield ['finalize'];
    }
}

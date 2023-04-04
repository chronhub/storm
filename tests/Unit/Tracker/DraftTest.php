<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Tracker;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\Draft;
use PHPUnit\Framework\Attributes\CoversClass;
use React\Promise\PromiseInterface;
use RuntimeException;
use stdClass;
use function iterator_to_array;

#[CoversClass(Draft::class)]
final class DraftTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $draft = new Draft('some_event');

        $this->assertEquals('some_event', $draft->currentEvent());
        $this->assertNull($draft->transientMessage());
        $this->assertNull($draft->promise());
        $this->assertFalse($draft->isStopped());
        $this->assertEmpty(iterator_to_array($draft->consumers()));
        $this->assertFalse($draft->hasException());
        $this->assertFalse($draft->isHandled());
        $this->assertNull($draft->exception());
    }

    public function testConstructorWithEmptyEvent(): void
    {
        $draft = new Draft(null);

        $draft->withEvent('dispatch');

        $this->assertEquals('dispatch', $draft->currentEvent());
    }

    public function TestConstructorWithEvent(): void
    {
        $draft = new Draft('finalize');

        $this->assertEquals('finalize', $draft->currentEvent());
    }

    public function testTakeOverEvent(): void
    {
        $draft = new Draft('dispatch');

        $this->assertEquals('dispatch', $draft->currentEvent());

        $draft->withEvent('finalize');

        $this->assertEquals('finalize', $draft->currentEvent());
    }

    public function testTransientMessage(): void
    {
        $draft = new Draft('dispatch');

        $draft->withTransientMessage(new stdClass());

        $this->assertInstanceOf(stdClass::class, $draft->transientMessage());

        $message = $draft->pullTransientMessage();

        $this->assertInstanceOf(stdClass::class, $message);

        $this->assertNull($draft->transientMessage());
    }

    public function testMessageHandled(): void
    {
        $draft = new Draft('dispatch');

        $this->assertFalse($draft->isHandled());

        $draft->markHandled(true);

        $this->assertTrue($draft->isHandled());
    }

    public function testMessageHandlersAdded(): void
    {
        $draft = new Draft('dispatch');

        $this->assertEmpty(iterator_to_array($draft->consumers()));

        $consumers = [
            fn (): array => [],
            fn (): array => [],
            fn (): array => [],
            fn (): array => [],
        ];

        $draft->withConsumers($consumers);

        $this->assertEquals($consumers, iterator_to_array($draft->consumers()));
    }

    public function testQueryPromise(): void
    {
        $draft = new Draft('dispatch');

        $this->assertNull($draft->promise());

        $promise = $this->createMock(PromiseInterface::class);

        $draft->withPromise($promise);

        $this->assertEquals($promise, $draft->promise());
    }

    public function TestSilentExceptionRaisedWhenDispatch(): void
    {
        $draft = new Draft('dispatch');

        $this->assertNull($draft->exception());
        $this->assertFalse($draft->hasException());

        $exception = new RuntimeException('foo');

        $draft->withRaisedException($exception);

        $this->assertTrue($draft->hasException());
        $this->assertEquals($exception, $draft->exception());
    }

    public function TestExceptionRaisedReset(): void
    {
        $draft = new Draft('dispatch');

        $this->assertNull($draft->exception());
        $this->assertFalse($draft->hasException());

        $exception = new RuntimeException('foo');

        $draft->withRaisedException($exception);

        $this->assertTrue($draft->hasException());
        $this->assertEquals($exception, $draft->exception());

        $draft->resetException();

        $this->assertNull($draft->exception());
        $this->assertFalse($draft->hasException());
    }
}

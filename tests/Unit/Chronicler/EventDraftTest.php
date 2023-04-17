<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Chronicler\EventDraft;
use Chronhub\Storm\Chronicler\Exceptions\ConcurrencyException;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\Exceptions\UnexpectedCallback;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Message\NoOpMessageDecorator;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

#[CoversClass(EventDraft::class)]
final class EventDraftTest extends UnitTestCase
{
    #[DataProvider('provideDeferredValues')]
    public function testSetCallback(mixed $value): void
    {
        $draft = new EventDraft(null);

        $draft->deferred(fn (): mixed => $value);

        $this->assertSame($value, $draft->promise());
    }

    public function testInstance(): void
    {
        $draft = new EventDraft(null);

        $this->assertFalse($draft->hasException());
        $this->assertFalse($draft->hasStreamNotFound());
        $this->assertFalse($draft->hasStreamAlreadyExits());
        $this->assertFalse($draft->hasConcurrency());
        $this->assertNull($draft->exception());
    }

    public function testStreamNotFoundException(): void
    {
        $draft = new EventDraft(null);

        $this->assertFalse($draft->hasStreamNotFound());

        $exception = StreamNotFound::withStreamName(new StreamName('foo'));

        $draft->withRaisedException($exception);

        $this->assertTrue($draft->hasStreamNotFound());

        $this->assertSame($exception, $draft->exception());
    }

    public function testStreamAlreadyExistsException(): void
    {
        $draft = new EventDraft(null);

        $this->assertFalse($draft->hasStreamAlreadyExits());

        $exception = StreamAlreadyExists::withStreamName(new StreamName('foo'));

        $draft->withRaisedException($exception);

        $this->assertTrue($draft->hasStreamAlreadyExits());

        $this->assertSame($exception, $draft->exception());
    }

    public function testConcurrencyException(): void
    {
        $draft = new EventDraft(null);

        $this->assertFalse($draft->hasConcurrency());

        $exception = new ConcurrencyException('foo');

        $draft->withRaisedException($exception);

        $this->assertTrue($draft->hasConcurrency());

        $this->assertSame($exception, $draft->exception());
    }

    public function testDecorateStreamEvents(): void
    {
        $draft = new EventDraft(null);

        $stream = new Stream(new StreamName('some_stream'), [
            new SomeEvent(['foo' => 'bar']),
            new SomeEvent(['foo' => 'bar']),
            new SomeEvent(['foo' => 'bar']),
        ]);

        $draft->deferred(fn (): Stream => $stream);

        $messageDecorator = new class() implements MessageDecorator
        {
            public function decorate(Message $message): Message
            {
                return $message->withHeader('some', 'header');
            }
        };

        $draft->decorate($messageDecorator);

        /** @var Stream $newStream */
        $newStream = $draft->promise();

        /** @var SomeEvent $event */
        foreach ($newStream->events() as $event) {
            $this->assertEquals(['some' => 'header'], $event->headers());
        }
    }

    public function testExceptionRaisedWhenNoCallbackHasBeenSetToDecorate(): void
    {
        $this->expectException(UnexpectedCallback::class);
        $this->expectExceptionMessage('No stream has been set as event callback');

        $draft = new EventDraft(null);

        $draft->deferred(fn (): bool => true);

        $draft->decorate(new NoOpMessageDecorator());
    }

    public function testExceptionRaisedWhenNoCallbackHasBeenSet(): void
    {
        $this->expectException(UnexpectedCallback::class);
        $this->expectExceptionMessage('No event callback has been set');

        $draft = new EventDraft(null);

        $draft->promise();
    }

    public static function provideDeferredValues(): Generator
    {
        yield [[]];
        yield [1];
        yield ['a string'];
        yield [new stdClass()];
    }
}

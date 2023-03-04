<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use stdClass;
use Generator;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Chronicler\EventDraft;
use Chronhub\Storm\Tests\Double\SomeEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Message\NoOpMessageDecorator;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\Exceptions\UnexpectedCallback;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\ConcurrencyException;

final class EventDraftTest extends UnitTestCase
{
    #[DataProvider('provideDeferredValues')]
    #[Test]
    public function it_set_callback(mixed $value): void
    {
        $draft = new EventDraft(null);

        $draft->deferred(fn (): mixed => $value);

        $this->assertSame($value, $draft->promise());
    }

    #[Test]
    public function it_assert_null_exceptions(): void
    {
        $draft = new EventDraft(null);

        $this->assertFalse($draft->hasException());
        $this->assertFalse($draft->hasStreamNotFound());
        $this->assertFalse($draft->hasStreamAlreadyExits());
        $this->assertFalse($draft->hasConcurrency());
        $this->assertNull($draft->exception());
    }

    #[Test]
    public function it_assert_stream_not_found_exception(): void
    {
        $draft = new EventDraft(null);

        $this->assertFalse($draft->hasStreamNotFound());

        $exception = StreamNotFound::withStreamName(new StreamName('foo'));

        $draft->withRaisedException($exception);

        $this->assertTrue($draft->hasStreamNotFound());

        $this->assertSame($exception, $draft->exception());
    }

    #[Test]
    public function it_assert_stream_already_exists_exception(): void
    {
        $draft = new EventDraft(null);

        $this->assertFalse($draft->hasStreamAlreadyExits());

        $exception = StreamAlreadyExists::withStreamName(new StreamName('foo'));

        $draft->withRaisedException($exception);

        $this->assertTrue($draft->hasStreamAlreadyExits());

        $this->assertSame($exception, $draft->exception());
    }

    #[Test]
    public function it_assert_concurrency_exception(): void
    {
        $draft = new EventDraft(null);

        $this->assertFalse($draft->hasConcurrency());

        $exception = new ConcurrencyException('foo');

        $draft->withRaisedException($exception);

        $this->assertTrue($draft->hasConcurrency());

        $this->assertSame($exception, $draft->exception());
    }

    #[Test]
    public function it_decorate_stream_events(): void
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

    #[Test]
    public function it_raise_exception_when_no_stream_has_been_set_as_callback_when_decorate(): void
    {
        $this->expectException(UnexpectedCallback::class);
        $this->expectExceptionMessage('No stream has been set as event callback');

        $draft = new EventDraft(null);

        $draft->deferred(fn (): bool => true);

        $draft->decorate(new NoOpMessageDecorator());
    }

    #[Test]
    public function it_raise_exception_when_calling_null_promise(): void
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

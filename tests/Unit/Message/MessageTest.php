<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Contracts\Reporter\Reporting;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeQuery;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use stdClass;

final class MessageTest extends UnitTestCase
{
    #[DataProvider('provideObject')]
    public function testMessageWithNonReportingInstance(object $event): void
    {
        $message = new Message($event);

        $this->assertFalse($message->isMessaging());
        $this->assertEquals($event::class, $message->event()::class);
    }

    #[DataProvider('provideDomain')]
    public function testMessageWithReportingInstance(Reporting $domain): void
    {
        $message = new Message($domain);

        $this->assertTrue($message->isMessaging());
        $this->assertEquals($domain, $message->event());
        $this->assertNotSame($domain, $message->event());

        $this->assertTrue($message->has('some'));
        $this->assertEquals(['some' => 'header'], $message->headers());
        $this->assertEquals('header', $message->header('some'));
    }

    public function testAddHeaderToMessageEvent(): void
    {
        $message = new Message(SomeCommand::fromContent([]), ['some' => 'header']);

        $this->assertEquals(['some' => 'header'], $message->headers());
    }

    public function testAddHeadersWhenBothHeadersMatched(): void
    {
        $command = SomeCommand::fromContent([])->withHeaders(['some' => 'header']);

        $message = new Message($command, ['some' => 'header']);

        $this->assertEquals(['some' => 'header'], $message->headers());
    }

    #[DataProvider('provideDomain')]
    public function testExceptionRaisedWhenHeadersMismatched(Reporting $domain): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid headers consistency for event class '.$domain::class);

        new Message($domain, ['another' => 'header']);
    }

    public function testExceptionRaisedWhenEventIsInstanceOfMessage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message event cannot be an instance of itself');

        new Message(new Message(new stdClass()));
    }

    #[DataProvider('provideDomain')]
    public function testAddHeadersToCloneMessage(Reporting $domain): void
    {
        $message = new Message($domain);

        $this->assertEquals(['some' => 'header'], $message->headers());

        $cloned = $message->withHeader('another', 'header');

        $this->assertNotSame($cloned, $domain);
        $this->assertEquals(['some' => 'header', 'another' => 'header'], $cloned->headers());
    }

    #[DataProvider('provideDomain')]
    public function testAddHeadersOnMessageEvent(Reporting $domain): void
    {
        $message = new Message($domain);

        $this->assertEquals(['some' => 'header'], $message->headers());

        $cloned = $message->withHeaders(['another' => 'header']);

        $this->assertNotSame($cloned, $domain);
        $this->assertEquals(['another' => 'header'], $cloned->headers());
    }

    #[DataProvider('provideDomain')]
    public function testAccessMessageEvent(Reporting $domain): void
    {
        $message = new Message($domain);

        $this->assertEquals($domain, $message->event());
        $this->assertNotSame($domain, $message->event());
    }

    public function testEventDoesNotReturnsOriginalEventWithHeaders()
    {
        $event = new SomeEvent([]);
        $headers = ['foo' => 'bar'];

        $message = new Message($event, $headers);

        $this->assertNotEquals($event, $message->event());
        $this->assertEquals($headers, $message->headers());
    }

    public function testEventReturnsClonedEventWithHeaders()
    {
        $event = new SomeEvent([]);
        $headers = ['foo' => 'bar'];

        $message = new Message($event, $headers);

        $clonedEvent = $message->event();

        $this->assertInstanceOf(SomeEvent::class, $clonedEvent);
        $this->assertEquals($headers, $clonedEvent->headers());
    }

    public function testWithHeaderAddsHeader()
    {
        $event = new SomeEvent([]);
        $headers = ['foo' => 'bar'];

        $message = new Message($event, $headers);

        $newHeader = ['baz' => 'qux'];

        $newMessage = $message->withHeader('baz', 'qux');

        $this->assertEquals($headers + $newHeader, $newMessage->headers());
    }

    public function testWithHeadersReplacesHeaders()
    {
        $event = new SomeEvent([]);
        $headers = ['foo' => 'bar'];

        $message = new Message($event, $headers);

        $newHeaders = ['baz' => 'qux'];

        $newMessage = $message->withHeaders($newHeaders);

        $this->assertEquals($newHeaders, $newMessage->headers());
    }

    public function testIsMessagingReturnsFalseForNonReportingEvent()
    {
        $event = new stdClass();
        $headers = ['foo' => 'bar'];

        $message = new Message($event, $headers);

        $this->assertFalse($message->isMessaging());
    }

    #[DataProvider('provideDomain')]
    public function testIsMessagingReturnsTrueForReportingEvent(Reporting $event)
    {
        $message = new Message($event);

        $this->assertTrue($message->isMessaging());
    }

    public static function provideDomain(): Generator
    {
        $headers = ['some' => 'header'];
        $content = ['name' => 'steph bug'];

        yield [SomeCommand::fromContent($content)->withHeaders($headers)];
        yield [SomeEvent::fromContent($content)->withHeaders($headers)];
        yield [SomeQuery::fromContent($content)->withHeaders($headers)];
    }

    public static function provideObject(): Generator
    {
        yield [new stdClass()];
        yield [new class()
        {
            //
        }, ];
        yield [(static fn (): bool => true)(...)];
    }
}

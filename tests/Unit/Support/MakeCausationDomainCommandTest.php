<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Support;

use Generator;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tracker\TrackMessage;
use Chronhub\Storm\Chronicler\TrackStream;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Tests\Double\SomeCommand;
use Chronhub\Storm\Chronicler\EventChronicler;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Tests\Util\ReflectionProperty;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Support\Bridge\MakeCausationDomainCommand;

class MakeCausationDomainCommandTest extends UnitTestCase
{
    #[DataProvider('provideStreamEventName')]
    #[Test]
    public function it_add_correlation_headers_from_dispatched_command_on_stream_event(string $streamEventName): void
    {
        $command = (SomeCommand::fromContent(['foo' => 'bar']))
            ->withHeaders(
                [
                    Header::EVENT_ID => '123',
                    Header::EVENT_TYPE => SomeCommand::class,
                ]
            );

        $messageTracker = new TrackMessage();
        $messageStory = $messageTracker->newStory(Reporter::DISPATCH_EVENT);
        $messageStory->withMessage(new Message($command));

        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory($streamEventName);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->createMock(Chronicler::class);
        $eventChronicler = new EventChronicler($chronicler, $streamTracker);

        $subscriber = new MakeCausationDomainCommand();
        $subscriber->attachToReporter($messageTracker);
        $subscriber->attachToChronicler($eventChronicler);

        $messageTracker->disclose($messageStory);

        $this->assertInstanceOf(SomeCommand::class, ReflectionProperty::getProperty($subscriber, 'currentCommand'));

        $streamTracker->disclose($streamStory);

        $decoratedEvent = $streamStory->promise()->events()->current();

        $this->assertEquals([
            EventHeader::EVENT_CAUSATION_ID => '123',
            EventHeader::EVENT_CAUSATION_TYPE => SomeCommand::class,
        ], $decoratedEvent->headers());

        $finalizeMessageStory = $messageTracker->newStory(Reporter::FINALIZE_EVENT);
        $messageTracker->disclose($finalizeMessageStory);

        $this->assertNull(ReflectionProperty::getProperty($subscriber, 'currentCommand'));
    }

    #[DataProvider('provideStreamEventName')]
    #[Test]
    public function it_does_not_add_correlation_headers_if_already_exists(string $streamEventName): void
    {
        $message = (SomeCommand::fromContent(['foo' => 'bar']))
            ->withHeaders(
                [
                    Header::EVENT_ID => '123',
                    Header::EVENT_TYPE => SomeCommand::class,
                ]
            );

        $messageTracker = new TrackMessage();
        $dispatchMessageStory = $messageTracker->newStory(Reporter::DISPATCH_EVENT);
        $dispatchMessageStory->withMessage(new Message($message));

        $event = (SomeEvent::fromContent(['foo' => 'bar']))->withHeaders(
            [
                EventHeader::EVENT_CAUSATION_ID => '321',
                EventHeader::EVENT_CAUSATION_TYPE => 'another-command',
            ]
        );

        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory($streamEventName);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->createMock(Chronicler::class);

        $eventChronicler = new EventChronicler($chronicler, $streamTracker);

        $subscriber = new MakeCausationDomainCommand();
        $subscriber->attachToReporter($messageTracker);
        $subscriber->attachToChronicler($eventChronicler);

        $messageTracker->disclose($dispatchMessageStory);

        $this->assertInstanceOf(SomeCommand::class, ReflectionProperty::getProperty($subscriber, 'currentCommand'));

        $streamTracker->disclose($streamStory);

        $decoratedEvent = $streamStory->promise()->events()->current();

        $this->assertEquals([
            EventHeader::EVENT_CAUSATION_ID => '321',
            EventHeader::EVENT_CAUSATION_TYPE => 'another-command',
        ], $decoratedEvent->headers());

        $finalizeMessageStory = $messageTracker->newStory(Reporter::FINALIZE_EVENT);
        $messageTracker->disclose($finalizeMessageStory);

        $this->assertNull(ReflectionProperty::getProperty($subscriber, 'currentCommand'));
    }

    #[Test]
    public function it_can_be_detach_from_event_store_and_reporter(): void
    {
        $messageTracker = new TrackMessage();
        $streamTracker = new TrackStream();

        $this->assertTrue($messageTracker->listeners()->isEmpty());
        $this->assertTrue($streamTracker->listeners()->isEmpty());

        $chronicler = $this->createMock(Chronicler::class);

        $eventChronicler = new EventChronicler($chronicler, $streamTracker);
        $this->assertCount(9, $streamTracker->listeners());

        $subscriber = new MakeCausationDomainCommand();
        $subscriber->attachToReporter($messageTracker);
        $subscriber->attachToChronicler($eventChronicler);

        $this->assertCount(2, $messageTracker->listeners());
        $this->assertCount(11, $streamTracker->listeners());

        $subscriber->detachFromReporter($messageTracker);
        $subscriber->detachFromChronicler($eventChronicler);

        $this->assertTrue($messageTracker->listeners()->isEmpty());
        $this->assertCount(9, $streamTracker->listeners());
    }

    public static function provideStreamEventName(): Generator
    {
        yield [EventableChronicler::FIRST_COMMIT_EVENT];
        yield [EventableChronicler::PERSIST_STREAM_EVENT];
    }
}

<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Support;

use Chronhub\Storm\Chronicler\EventChronicler;
use Chronhub\Storm\Chronicler\TrackStream;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Support\Bridge\MakeCausationDomainCommand;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tests\Util\ReflectionProperty;
use Chronhub\Storm\Tracker\TrackMessage;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(MakeCausationDomainCommand::class)]
final class MakeCausationDomainCommandTest extends UnitTestCase
{
    #[DataProvider('provideStreamEventName')]
    public function testCorrelationHeaderAdded(string $streamEventName): void
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
    public function testCorrelationHeaderNotOverride(string $streamEventName): void
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

    public function testSubscribersCanBeDetached(): void
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

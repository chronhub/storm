<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Contracts\Message\MessageFactory;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Reporter\Subscribers\MakeMessage;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeQuery;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(MakeMessage::class)]
final class MakeMessageTest extends UnitTestCase
{
    private MessageFactory|MockObject $messageFactory;

    protected function setUp(): void
    {
        $this->messageFactory = $this->createMock(MessageFactory::class);
    }

    public function testSubscriber(): void
    {
        $subscriber = new MakeMessage($this->messageFactory);

        AssertMessageListener::isTrackedAndCanBeUntracked(
            $subscriber,
            Reporter::DISPATCH_EVENT,
            OnDispatchPriority::MESSAGE_FACTORY->value
        );
    }

    #[DataProvider('provideMessage')]
    public function testCreateMessage(Message $message): void
    {
        $this->messageFactory
            ->expects($this->once())
            ->method('__invoke')
            ->with($message)
            ->willReturn($message);

        $tracker = new TrackMessage();
        $subscriber = new MakeMessage($this->messageFactory);
        $subscriber->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withTransientMessage($message);
        $tracker->disclose($story);

        $this->assertSame($message, $story->message());
    }

    #[DataProvider('provideMessage')]
    public function testCreateMessageFromArray(Message $message): void
    {
        $this->messageFactory
            ->expects($this->once())
            ->method('__invoke')
            ->with(['name' => 'steph bug'])
            ->willReturn($message);

        $tracker = new TrackMessage();

        $subscriber = new MakeMessage($this->messageFactory);
        $subscriber->attachToReporter($tracker);
        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withTransientMessage(['name' => 'steph bug']);
        $tracker->disclose($story);

        $this->assertSame($message, $story->message());
    }

    public static function provideMessage(): Generator
    {
        yield [new Message(new SomeCommand(['name' => 'steph bug']))];
        yield [new Message(new SomeEvent(['name' => 'steph bug']))];
        yield [new Message(new SomeQuery(['name' => 'steph bug']))];
    }
}

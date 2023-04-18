<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Contracts\Message\MessageFactory;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Reporter\Subscribers\MakeMessage;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\Attributes\CoversClass;
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

    public function testCreateMessage(): void
    {
        $message = new Message(new SomeCommand(['name' => 'steph bug']));

        $this->messageFactory->expects($this->once())
            ->method('__invoke')
            ->with($message)
            ->willReturn($message);

        $tracker = new TrackMessage();

        $subscriber = new MakeMessage($this->messageFactory);
        $subscriber->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);

        $story->withTransientMessage($message);

        $tracker->disclose($story);

        $this->assertEquals($message, $story->message());
    }

    public function tetCreateMessageFromArray(): void
    {
        $message = new Message(new SomeCommand(['some' => 'content']));

        $this->messageFactory->expects($this->once())
            ->method('__invoke')
            ->with(['some' => 'content'])
            ->willReturn($message);

        $tracker = new TrackMessage();

        $subscriber = new MakeMessage($this->messageFactory);
        $subscriber->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);

        $story->withTransientMessage(['some' => 'content']);

        $tracker->disclose($story);

        $this->assertEquals($message, $story->message());
    }
}

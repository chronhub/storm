<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\MockObject\Exception;
use Chronhub\Storm\Tests\Double\SomeCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Contracts\Message\MessageFactory;
use Chronhub\Storm\Reporter\Subscribers\MakeMessage;

#[CoversClass(MakeMessage::class)]
final class MakeMessageTest extends UnitTestCase
{
    private MessageFactory|MockObject $messageFactory;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->messageFactory = $this->createMock(MessageFactory::class);
    }

    #[Test]
    public function it_test_subscriber(): void
    {
        $subscriber = new MakeMessage($this->messageFactory);

        AssertMessageListener::isTrackedAndCanBeUntracked(
            $subscriber,
            Reporter::DISPATCH_EVENT,
            OnDispatchPriority::MESSAGE_FACTORY->value
        );
    }

    #[Test]
    public function it_create_message_instance(): void
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

    #[Test]
    public function it_create_message_instance_from_array(): void
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

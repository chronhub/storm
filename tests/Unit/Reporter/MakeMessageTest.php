<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Message\Message;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Tracker\TrackMessage;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Tests\Double\SomeCommand;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Contracts\Message\MessageFactory;
use Chronhub\Storm\Reporter\Subscribers\MakeMessage;

final class MakeMessageTest extends ProphecyTestCase
{
    private readonly MessageFactory|ObjectProphecy $messageFactory;

    public function setUp(): void
    {
        $this->messageFactory = $this->prophesize(MessageFactory::class);
    }

    /**
     * @test
     */
    public function it_test_subscriber(): void
    {
        $subscriber = new MakeMessage($this->messageFactory->reveal());

        AssertMessageListener::isTrackedAndCanBeUntracked(
            $subscriber,
            Reporter::DISPATCH_EVENT,
            OnDispatchPriority::MESSAGE_FACTORY->value
        );
    }

    /**
     * @test
     */
    public function it_create_message_instance(): void
    {
        $message = new Message(new SomeCommand(['name' => 'steph bug']));

        $this->messageFactory->__invoke($message)->willReturn($message)->shouldBeCalledOnce();

        $tracker = new TrackMessage();

        $subscriber = new MakeMessage($this->messageFactory->reveal());
        $subscriber->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);

        $story->withTransientMessage($message);

        $tracker->disclose($story);

        $this->assertEquals($message, $story->message());
    }

    /**
     * @test
     */
    public function it_create_message_instance_from_array(): void
    {
        $message = new Message(new SomeCommand(['some' => 'content']));

        $this->messageFactory->__invoke(['some' => 'content'])->willReturn($message)->shouldBeCalledOnce();

        $tracker = new TrackMessage();

        $subscriber = new MakeMessage($this->messageFactory->reveal());
        $subscriber->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);

        $story->withTransientMessage(['some' => 'content']);

        $tracker->disclose($story);

        $this->assertEquals($message, $story->message());
    }
}

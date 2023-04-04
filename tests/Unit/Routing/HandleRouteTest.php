<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Contracts\Producer\MessageProducer;
use Chronhub\Storm\Contracts\Producer\ProducerUnity;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Contracts\Routing\RouteLocator;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Routing\HandleRoute;
use Chronhub\Storm\Tests\Unit\Reporter\AssertMessageListener;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use Generator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

#[CoversClass(HandleRoute::class)]

final class HandleRouteTest extends UnitTestCase
{
    private RouteLocator|MockObject $routeLocator;

    private MessageProducer|MockObject $messageProducer;

    private ProducerUnity|MockObject $producerUnity;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->routeLocator = $this->createMock(RouteLocator::class);
        $this->messageProducer = $this->createMock(MessageProducer::class);
        $this->producerUnity = $this->createMock(ProducerUnity::class);
    }

    #[Test]
    public function testRouteHandleSync(): void
    {
        $tracker = new TrackMessage();

        $message = new Message(new stdClass());
        $dispatchedMessage = new Message(new stdClass(), ['dispatched']);

        $this->producerUnity->expects($this->once())
            ->method('isSync')
            ->with($message)
            ->willReturn(true);

        $this->routeLocator->expects($this->never())->method('onQueue');

        $this->messageProducer->expects($this->once())
            ->method('produce')
            ->with($message)
            ->willReturn($dispatchedMessage);

        $this->routeLocator->expects($this->once())
            ->method('route')
            ->with($dispatchedMessage)
            ->willReturn(new Collection(['some_message_handler']));

        $subscriber = $this->handleRouteInstance();
        $subscriber->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($message);

        $tracker->disclose($story);
    }

    #[Test]
    public function testRouteHandleAsync(): void
    {
        $tracker = new TrackMessage();

        $message = new Message(new stdClass());

        $this->producerUnity->expects($this->once())
            ->method('isSync')
            ->with($message)
            ->willReturn(false);

        $this->routeLocator->expects($this->once())
            ->method('onQueue')
            ->with($message)
            ->willReturn(['connection' => 'redis']);

        $this->messageProducer->expects($this->once())
            ->method('produce')
            ->with($this->isInstanceOf(Message::class))
            ->will($this->returnCallback(function (Message $message): Message {
                $this->assertEquals(['connection' => 'redis'], $message->header('queue'));

                return $message;
            }));

        $this->routeLocator->expects($this->never())->method('route');

        $subscriber = $this->handleRouteInstance();
        $subscriber->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($message);

        $tracker->disclose($story);
    }

    #[DataProvider('provideNullAndEmptyArray')]
    public function testRouteHandleASyncWithNoQueueOption(?array $noQueue): void
    {
        $tracker = new TrackMessage();

        $message = new Message(new stdClass());

        $this->producerUnity->expects($this->once())
            ->method('isSync')
            ->with($message)
            ->willReturn(false);

        $this->routeLocator->expects($this->once())
            ->method('onQueue')
            ->with($message)
            ->willReturn($noQueue);

        $this->messageProducer->expects($this->once())
            ->method('produce')
            ->with($this->isInstanceOf(Message::class))
            ->will($this->returnCallback(function (Message $message): Message {
                $this->assertNull($message->header('queue'));

                return $message;
            }));

        $this->routeLocator->expects($this->never())->method('route');

        $subscriber = $this->handleRouteInstance();
        $subscriber->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($message);

        $tracker->disclose($story);
    }

    public function testSubscriberCanBeUntracked(): void
    {
        $subscriber = new HandleRoute($this->routeLocator, $this->messageProducer, $this->producerUnity);

        AssertMessageListener::isTrackedAndCanBeUntracked($subscriber, Reporter::DISPATCH_EVENT, OnDispatchPriority::ROUTE->value);
    }

    public static function provideNullAndEmptyArray(): Generator
    {
        yield [null];
        yield [[]];
    }

    private function handleRouteInstance(): HandleRoute
    {
        return new HandleRoute($this->routeLocator, $this->messageProducer, $this->producerUnity);
    }
}

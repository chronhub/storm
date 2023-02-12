<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use stdClass;
use Generator;
use Prophecy\Argument;
use Illuminate\Support\Collection;
use Chronhub\Storm\Message\Message;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Routing\HandleRoute;
use Chronhub\Storm\Tracker\TrackMessage;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Contracts\Routing\RouteLocator;
use Chronhub\Storm\Contracts\Producer\ProducerUnity;
use Chronhub\Storm\Contracts\Producer\MessageProducer;
use Chronhub\Storm\Tests\Unit\Reporter\AssertMessageListener;

final class HandleRouteTest extends ProphecyTestCase
{
    private RouteLocator|ObjectProphecy $routeLocator;

    private MessageProducer|ObjectProphecy $messageProducer;

    private ObjectProphecy|ProducerUnity $producerUnity;

    protected function setUp(): void
    {
        $this->routeLocator = $this->prophesize(RouteLocator::class);
        $this->messageProducer = $this->prophesize(MessageProducer::class);
        $this->producerUnity = $this->prophesize(ProducerUnity::class);
    }

    /**
     * @test
     */
    public function it_handle_route_sync(): void
    {
        $tracker = new TrackMessage();

        $message = new Message(new stdClass());
        $dispatchedMessage = new Message(new stdClass(), ['dispatched']);

        $this->producerUnity->isSync($message)->willReturn(true)->shouldBeCalledOnce();
        $this->routeLocator->onQueue($message)->shouldNotBeCalled();
        $this->messageProducer->produce($message)->willReturn($dispatchedMessage)->shouldBeCalledOnce();

        $this->routeLocator->route($dispatchedMessage)->willReturn(new Collection(['some_message_handler']))->shouldBeCalledOnce();

        $subscriber = $this->handleRouteInstance();
        $subscriber->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($message);

        $tracker->disclose($story);
    }

    /**
     * @test
     */
    public function it_handle_route_async(): void
    {
        $tracker = new TrackMessage();

        $message = new Message(new stdClass());
        $dispatchedMessage = new Message(new stdClass(), ['dispatched']);

        $this->producerUnity->isSync($message)->willReturn(false)->shouldBeCalledOnce();
        $this->routeLocator->onQueue($message)->willReturn(['connection' => 'redis'])->shouldBeCalledOnce();
        $this->messageProducer->produce(Argument::that(function (Message $message): Message {
            $this->assertArrayHasKey('queue', $message->headers());
            $this->assertEquals(['connection' => 'redis'], $message->header('queue'));

            return $message;
        }))->willReturn($dispatchedMessage)->shouldBeCalledOnce();

        $this->routeLocator->route($dispatchedMessage)->shouldNotBeCalled();

        $subscriber = $this->handleRouteInstance();
        $subscriber->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($message);

        $tracker->disclose($story);
    }

    /**
     * @test
     *
     * @dataProvider provideNullAndEmptyArray
     */
    public function it_handle_route_async_with_no_queue_options(?array $noQueue): void
    {
        $tracker = new TrackMessage();

        $message = new Message(new stdClass());
        $dispatchedMessage = new Message(new stdClass(), ['dispatched']);

        $this->producerUnity->isSync($message)->willReturn(false)->shouldBeCalledOnce();
        $this->routeLocator->onQueue($message)->willReturn($noQueue)->shouldBeCalledOnce();

        $this->messageProducer->produce($message)->willReturn($dispatchedMessage)->shouldBeCalledOnce();

        $this->routeLocator->route($dispatchedMessage)->shouldNotBeCalled();

        $subscriber = $this->handleRouteInstance();
        $subscriber->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($message);

        $tracker->disclose($story);
    }

    /**
     * @test
     */
    public function it_assert_subscriber_can_be_untracked(): void
    {
        $subscriber = new HandleRoute($this->routeLocator->reveal(), $this->messageProducer->reveal(), $this->producerUnity->reveal());

        AssertMessageListener::isTrackedAndCanBeUntracked($subscriber, Reporter::DISPATCH_EVENT, OnDispatchPriority::ROUTE->value);
    }

    public function provideNullAndEmptyArray(): Generator
    {
        yield [null];
        yield [[]];
    }

    private function handleRouteInstance(): HandleRoute
    {
        return new HandleRoute($this->routeLocator->reveal(), $this->messageProducer->reveal(), $this->producerUnity->reveal());
    }
}

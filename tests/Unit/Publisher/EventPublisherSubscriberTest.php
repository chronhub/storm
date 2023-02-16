<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Publisher;

use Prophecy\Argument;
use Chronhub\Storm\Stream\Stream;
use Illuminate\Support\Collection;
use Chronhub\Storm\Stream\StreamName;
use Prophecy\Prophecy\ObjectProphecy;
use Illuminate\Support\LazyCollection;
use Chronhub\Storm\Chronicler\TrackStream;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Chronicler\EventChronicler;
use Chronhub\Storm\Tests\Util\ReflectionProperty;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Publisher\EventPublisherSubscriber;
use Chronhub\Storm\Chronicler\TrackTransactionalStream;
use Chronhub\Storm\Contracts\Chronicler\EventPublisher;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\TransactionalEventChronicler;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\ConcurrencyException;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;
use function count;

final class EventPublisherSubscriberTest extends ProphecyTestCase
{
    private EventPublisher|ObjectProphecy $eventPublisher;

    public function setUp(): void
    {
        parent::setUp();

        $this->eventPublisher = $this->prophesize(EventPublisher::class);
    }

    /**
     * @test
     */
    public function it_publish_events_on_first_commit_with_no_transactional_chronicler(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::FIRST_COMMIT_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->prophesize(Chronicler::class);
        $eventChronicler = new EventChronicler($chronicler->reveal(), $streamTracker);

        $this->eventPublisher->record(Argument::type(Collection::class))->shouldNotBeCalled();
        $this->eventPublisher->publish(new Collection([$event]))->shouldBeCalledOnce();

        $subscriber = new EventPublisherSubscriber($this->eventPublisher->reveal());
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    /**
     * @test
     */
    public function it_publish_events_on_first_commit_with_eventable_chronicler(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::FIRST_COMMIT_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->prophesize(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler->reveal(), $streamTracker);

        $this->eventPublisher->record(Argument::type(Collection::class))->shouldNotBeCalled();
        $this->eventPublisher->publish(new Collection([$event]))->shouldBeCalledOnce();

        $subscriber = new EventPublisherSubscriber($this->eventPublisher->reveal());
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    /**
     * @test
     */
    public function it_does_not_publish_events_on_first_commit_with_eventable_chronicler_on_stream_already_exists(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::FIRST_COMMIT_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);
        $streamStory->withRaisedException(new StreamAlreadyExists('stream already exists'));

        $chronicler = $this->prophesize(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler->reveal(), $streamTracker);

        $this->eventPublisher->record(Argument::type(Collection::class))->shouldNotBeCalled();
        $this->eventPublisher->publish(new Collection([$event]))->shouldNotBeCalled();

        $subscriber = new EventPublisherSubscriber($this->eventPublisher->reveal());
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    /**
     * @test
     */
    public function it_publish_events_on_amend_with_eventable_chronicler(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->prophesize(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler->reveal(), $streamTracker);

        $this->eventPublisher->record(Argument::type(Collection::class))->shouldNotBeCalled();
        $this->eventPublisher->publish(new Collection([$event]))->shouldBeCalledOnce();

        $subscriber = new EventPublisherSubscriber($this->eventPublisher->reveal());
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    /**
     * @test
     */
    public function it_does_not_publish_events_on_amend_with_eventable_chronicler_on_stream_not_found(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);
        $streamStory->withRaisedException(new StreamNotFound('stream not found'));

        $chronicler = $this->prophesize(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler->reveal(), $streamTracker);

        $this->eventPublisher->record(Argument::type(Collection::class))->shouldNotBeCalled();
        $this->eventPublisher->publish(new Collection([$event]))->shouldNotBeCalled();

        $subscriber = new EventPublisherSubscriber($this->eventPublisher->reveal());
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    /**
     * @test
     */
    public function it_does_not_publish_events_on_amend_with_eventable_chronicler_on_concurrency(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);
        $streamStory->withRaisedException(new ConcurrencyException('concurrency exception'));

        $chronicler = $this->prophesize(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler->reveal(), $streamTracker);

        $this->eventPublisher->record(Argument::type(Collection::class))->shouldNotBeCalled();
        $this->eventPublisher->publish(new Collection([$event]))->shouldNotBeCalled();

        $subscriber = new EventPublisherSubscriber($this->eventPublisher->reveal());
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    /**
     * @test
     */
    public function it_publish_events_on_persist_not_in_transaction(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->prophesize(TransactionalEventableChronicler::class);
        $chronicler->amend($stream)->shouldBeCalledOnce();

        $chronicler->inTransaction()->willReturn(false)->shouldBeCalledOnce();
        $eventChronicler = new TransactionalEventChronicler($chronicler->reveal(), $streamTracker);

        $pendingEvents = new Collection([$event]);

        $this->eventPublisher->pull()->willReturn($pendingEvents)->shouldNotBeCalled();
        $this->eventPublisher->publish($pendingEvents)->shouldBeCalledOnce();
        $this->eventPublisher->flush()->shouldNotBeCalled();

        $subscriber = new EventPublisherSubscriber($this->eventPublisher->reveal());
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    /**
     * @test
     */
    public function it_record_events_on_first_commit_in_transaction(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::FIRST_COMMIT_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->prophesize(TransactionalEventableChronicler::class);
        $chronicler->firstCommit($stream)->shouldBeCalledOnce();

        $chronicler->inTransaction()->willReturn(true)->shouldBeCalledOnce();
        $eventChronicler = new TransactionalEventChronicler($chronicler->reveal(), $streamTracker);

        $pendingEvents = new Collection([$event]);

        $this->eventPublisher->pull()->willReturn($pendingEvents)->shouldNotBeCalled();
        $this->eventPublisher->publish($pendingEvents)->shouldNotBeCalled();
        $this->eventPublisher->record($pendingEvents)->shouldBeCalledOnce();

        $subscriber = new EventPublisherSubscriber($this->eventPublisher->reveal());
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    /**
     * @test
     */
    public function it_record_events_on_persist_in_transaction(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->prophesize(TransactionalEventableChronicler::class);
        $chronicler->amend($stream)->shouldBeCalledOnce();

        $chronicler->inTransaction()->willReturn(true)->shouldBeCalledOnce();
        $eventChronicler = new TransactionalEventChronicler($chronicler->reveal(), $streamTracker);

        $pendingEvents = new Collection([$event]);

        $this->eventPublisher->pull()->willReturn($pendingEvents)->shouldNotBeCalled();
        $this->eventPublisher->publish($pendingEvents)->shouldNotBeCalled();
        $this->eventPublisher->record($pendingEvents)->shouldBeCalledOnce();

        $subscriber = new EventPublisherSubscriber($this->eventPublisher->reveal());
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    /**
     * @test
     */
    public function it_publish_events_on_commit_transaction(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->prophesize(TransactionalEventableChronicler::class);
        $eventChronicler = new TransactionalEventChronicler($chronicler->reveal(), $streamTracker);

        $pendingEvents = new LazyCollection([$event]);

        $this->eventPublisher->pull()->willReturn($pendingEvents)->shouldBeCalledOnce();
        $this->eventPublisher->publish($pendingEvents)->shouldBeCalledOnce();
        $this->eventPublisher->flush()->shouldNotBeCalled();

        $subscriber = new EventPublisherSubscriber($this->eventPublisher->reveal());
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    /**
     * @test
     */
    public function it_flush_pending_events_on_rollback_transaction(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->prophesize(TransactionalEventableChronicler::class);
        $eventChronicler = new TransactionalEventChronicler($chronicler->reveal(), $streamTracker);

        $this->eventPublisher->record(Argument::type(LazyCollection::class))->shouldNotBeCalled();
        $this->eventPublisher->pull()->willReturn(new LazyCollection([$event]))->shouldNotBeCalled();
        $this->eventPublisher->publish(new LazyCollection([$event]))->shouldNotBeCalled();
        $this->eventPublisher->flush()->shouldBeCalledOnce();

        $subscriber = new EventPublisherSubscriber($this->eventPublisher->reveal());
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    /**
     * @test
     */
    public function it_detach_stream_subscribers(): void
    {
        $streamTracker = new TrackTransactionalStream();
        $this->assertCount(0, $streamTracker->listeners());

        $chronicler = $this->prophesize(TransactionalEventableChronicler::class);
        $eventChronicler = new TransactionalEventChronicler($chronicler->reveal(), $streamTracker);

        $countProvidedSubscribers = count($streamTracker->listeners());

        $subscriber = new EventPublisherSubscriber($this->eventPublisher->reveal());
        $subscriber->attachToChronicler($eventChronicler);

        $countFromPublisher = is_countable(ReflectionProperty::getProperty($subscriber, 'streamSubscribers')) ? count(ReflectionProperty::getProperty($subscriber, 'streamSubscribers')) : 0;
        $this->assertEquals(4, $countFromPublisher);

        $subscriber->detachFromChronicler($eventChronicler);

        $this->assertEquals(count($streamTracker->listeners()), $countProvidedSubscribers);
    }
}

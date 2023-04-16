<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Projection;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Projector\EmitterCasterInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorManagerInterface;
use Chronhub\Storm\Projector\AbstractSubscriptionFactory;
use Chronhub\Storm\Projector\InMemorySubscriptionFactory;
use Chronhub\Storm\Projector\ProjectEmitter;
use Chronhub\Storm\Projector\ProjectorManager;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProjectEmitter::class)] //todo uncover and test
#[CoversClass(ProjectorManager::class)]
#[CoversClass(AbstractSubscriptionFactory::class)]
#[CoversClass(InMemorySubscriptionFactory::class)]
final class EmitterSubscriptionManagerTest extends InMemoryProjectorManagerTestCase
{
    private StreamName $streamName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->streamName = new StreamName('balance');
    }

    public function testInstance(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $this->assertInstanceOf(ProjectorManagerInterface::class, $manager);

        $projection = $manager->emitter('amount');
        $this->assertEquals($projection, $manager->emitter('amount'));
        $this->assertNotSame($projection, $manager->emitter('amount'));

        $this->assertSame(ProjectEmitter::class, $projection::class);
    }

    public function testEmitEvent(): void
    {
        $this->assertFalse($this->projectionProvider->exists('amount'));
        $this->assertFalse($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->eventStore->hasStream(new StreamName('amount')));

        $this->feedEventStore($this->streamName, V4AggregateId::create(), 2);

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $projection = $manager->emitter('amount');

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->fromStreams($this->streamName->name)
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var EmitterCasterInterface $this */
                UnitTestCase::assertInstanceOf(EmitterCasterInterface::class, $this);
                UnitTestCase::assertSame('balance', $this->streamName());
                UnitTestCase::assertInstanceOf(PointInTime::class, $this->clock());

                $this->emit($event);

                $state['count']++;

                return $state;
            })
            ->run(false);

        $this->assertEquals(2, $projection->getState()['count']);

        $this->assertTrue($this->projectionProvider->exists('amount'));
        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertTrue($this->eventStore->hasStream(new StreamName('amount')));
    }

    public function testLinkToNewStream(): void
    {
        $this->assertFalse($this->projectionProvider->exists('amount'));
        $this->assertFalse($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->eventStore->hasStream(new StreamName('amount')));

        $this->feedEventStore($this->streamName, V4AggregateId::create(), 2);

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $projection = $manager->emitter('amount');

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->fromStreams($this->streamName->name)
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var EmitterCasterInterface $this */
                UnitTestCase::assertInstanceOf(EmitterCasterInterface::class, $this);

                $this->linkTo('amount_duplicate', $event);

                $state['count']++;

                return $state;
            })
            ->run(false);

        $this->assertEquals(2, $projection->getState()['count']);

        $this->assertTrue($this->projectionProvider->exists('amount'));
        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->eventStore->hasStream(new StreamName('amount')));
        $this->assertTrue($this->eventStore->hasStream(new StreamName('amount_duplicate')));
    }
}

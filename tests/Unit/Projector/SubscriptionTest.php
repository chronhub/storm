<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Projector\Caster;
use Chronhub\Storm\Projector\AbstractSubscription;
use Chronhub\Storm\Projector\Options\DefaultProjectionOption;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Tests\Unit\Projector\Stubs\SubscriptionStub;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AbstractSubscription::class)]
final class SubscriptionTest extends UnitTestCase
{
    private DefaultProjectionOption $options;

    private StreamPosition $position;

    private PointInTime $clock;

    protected function setUp(): void
    {
        $this->options = new DefaultProjectionOption();
        $this->position = new StreamPosition(
            new InMemoryEventStream()
        );
        $this->clock = new PointInTime();
    }

    public function testInstance(): void
    {
        $stub = $this->newSubscription();

        $this->assertInstanceOf(AbstractSubscription::class, $stub);
        $this->assertNull($stub->currentStreamName);
        $this->assertEquals(ProjectionStatus::IDLE, $stub->currentStatus());
        $this->assertInstanceOf(StreamPosition::class, $stub->streamPosition());
        $this->assertInstanceOf(PointInTime::class, $stub->clock());
        $this->assertInstanceOf(ProjectionState::class, $stub->state());
        $this->assertInstanceOf(DefaultProjectionOption::class, $stub->option());
    }

    #[DataProvider('provideBoolean')]
    public function testCompose(bool $keepRunning): void
    {
        $caster = $this->createMock(Caster::class);
        $context = new Context();

        $stub = $this->newSubscription();

        $this->assertFalse($stub->sprint()->inProgress());
        $this->assertFalse($stub->sprint()->inBackground());

        $stub->compose($context, $caster, $keepRunning);

        $this->assertEquals(Context::class, $stub->context()::class);
        $this->assertTrue($stub->sprint()->inProgress());
        $this->assertSame($keepRunning, $stub->sprint()->inBackground());
    }

    public function testGetAndSetProjectionStatus(): void
    {
        $stub = $this->newSubscription();

        $this->assertEquals(ProjectionStatus::IDLE, $stub->currentStatus());

        $stub->setStatus(ProjectionStatus::RUNNING);

        $this->assertEquals(ProjectionStatus::RUNNING, $stub->currentStatus());
    }

    public function testInitializeAgain(): void
    {
        $stub = $this->newSubscription();

        $caster = $this->createMock(Caster::class);
        $context = new Context();
        $context->initialize(fn (): array => ['count' => 0]);

        $stub->compose($context, $caster, false);

        $this->assertSame(['count' => 0], $stub->state()->get());

        $stub->state()->put(['count' => 1]);

        $this->assertSame(['count' => 1], $stub->state()->get());

        $stub->initializeAgain();

        $this->assertSame(['count' => 0], $stub->state()->get());
    }

    public static function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }

    private function newSubscription(): SubscriptionStub
    {
        return new SubscriptionStub($this->options, $this->position, $this->clock);
    }
}

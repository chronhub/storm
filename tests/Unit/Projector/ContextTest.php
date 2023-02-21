<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Tests\Stubs\ContextStub;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\DetectGap;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Projector\Scheme\PersistentCaster;
use Chronhub\Storm\Projector\Scheme\ProcessArrayEvent;
use Chronhub\Storm\Contracts\Projector\ProjectorOption;
use Chronhub\Storm\Projector\Scheme\ProcessClosureEvent;
use Chronhub\Storm\Contracts\Projector\ProjectionProjector;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

final class ContextTest extends ProphecyTestCase
{
    private ProjectorOption|ObjectProphecy $option;

    private StreamPosition|ObjectProphecy $position;

    private ObjectProphecy|EventCounter $counter;

    private DetectGap|ObjectProphecy $gap;

    protected function setUp(): void
    {
        $this->option = $this->prophesize(ProjectorOption::class);
        $this->position = $this->prophesize(StreamPosition::class);
        $this->counter = $this->prophesize(EventCounter::class);
        $this->gap = $this->prophesize(DetectGap::class);
    }

    private function newContext(): ContextStub
    {
        return new ContextStub(
            $this->option->reveal(),
            $this->position->reveal(),
            $this->counter->reveal(),
            $this->gap->reveal()
        );
    }

    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $context = $this->newContext();

        $this->assertFalse($context->isStreamCreated);
        $this->assertNull($context->initCallback);
        $this->assertNull($context->currentStreamName);
        $this->assertEquals(ProjectionStatus::IDLE, $context->status);
        $this->assertTrue($context->isPersistent);
        $this->assertFalse($context->runner->inBackground());
        $this->assertFalse($context->runner->isStopped());
        $this->assertInstanceOf(ProjectorOption::class, $context->option);
        $this->assertInstanceOf(StreamPosition::class, $context->streamPosition);
        $this->assertInstanceOf(EventCounter::class, $context->eventCounter);
        $this->assertInstanceOf(DetectGap::class, $context->gap);
    }

    /**
     * @test
     */
    public function it_set_initialize(): void
    {
        $context = $this->newContext();

        $init = static fn (): array => ['counter' => 0];

        $context->initialize($init);

        $this->assertSame($init, $context->initCallback);
    }

    /**
     * @test
     */
    public function it_test_compose(): void
    {
        // checkMe incomplete test
        // casting closure is not tested from inside
        // to do in func testing

        $context = $this->newContext();

        $context
            ->initialize(fn (): array => ['start_at' => 1])
            ->fromStreams('foo')
            ->whenAny(fn (DomainEvent $event, array $state): array => $state)
            ->withQueryFilter($this->provideProjectionQueryFilter());

        $this->assertFalse($context->runner->inBackground());
        $this->assertFalse($context->runner->isStopped());
        $this->assertEmpty($context->state->get());

        $projector = $this->prophesize(ProjectionProjector::class);

        $caster = new PersistentCaster($projector->reveal(), $context->currentStreamName);

        $context->compose($caster, true);

        $this->assertTrue($context->runner->inBackground());
        $this->assertEquals(['start_at' => 1], $context->state->get());
        $this->assertInstanceOf(ProcessClosureEvent::class, $context->eventHandlers());
    }

    /**
     * @test
     */
    public function it_test_compose_without_init_callback_and_array_event_handlers(): void
    {
        // checkMe incomplete test
        // casting closure is not tested from inside
        // to do in func testing

        $context = $this->newContext();

        $context
            ->fromStreams('foo')
            ->when([
                DomainEvent::class => fn (DomainEvent $event, array $state): array => $state,
            ])
            ->withQueryFilter($this->provideProjectionQueryFilter());

        $this->assertFalse($context->runner->inBackground());
        $this->assertFalse($context->runner->isStopped());
        $this->assertEmpty($context->state->get());

        $projector = $this->prophesize(ProjectionProjector::class);

        $caster = new PersistentCaster($projector->reveal(), $context->currentStreamName);

        $context->compose($caster, false);

        $this->assertFalse($context->runner->inBackground());
        $this->assertEmpty($context->state->get());
        $this->assertInstanceOf(ProcessArrayEvent::class, $context->eventHandlers());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_is_already_initialized(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Projection already initialized');

        $context = $this->newContext();

        $context->initialize(fn (): string => 'ok');
        $context->initialize(fn (): string => 'ok');
    }

    /**
     * @test
     */
    public function it_set_query_filter(): void
    {
        $context = $this->newContext();

        $queryFilter = $this->provideProjectionQueryFilter();

        $this->assertEquals(0, $queryFilter->position);

        $queryFilter->setCurrentPosition(10);
        $context->withQueryFilter($queryFilter);

        $this->assertSame($queryFilter, $context->queryFilter());
        $this->assertEquals(10, $queryFilter->position);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_query_filter_already_exists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Projection query filter already set');

        $context = $this->newContext();

        $context->withQueryFilter($this->provideProjectionQueryFilter());
        $context->withQueryFilter($this->provideProjectionQueryFilter());
    }

    /**
     * @test
     */
    public function it_set_from_streams(): void
    {
        $context = $this->newContext();

        $this->assertEmpty($context->queries());

        $context->fromStreams('foo', 'bar');

        $this->assertEquals(['names' => ['foo', 'bar']], $context->queries());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_from_streams_is_already_set(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Projection streams all|names|categories already set');

        $context = $this->newContext();

        $this->assertEmpty($context->queries());

        $context->fromStreams('foo', 'bar');

        $context->fromStreams('foo', 'bar');
    }

    /**
     * @test
     */
    public function it_raise_exception_when_all_is_already_set(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Projection streams all|names|categories already set');

        $context = $this->newContext();

        $this->assertEmpty($context->queries());

        $context->fromAll();

        $context->fromStreams('foo', 'bar');
    }

    /**
     * @test
     */
    public function it_raise_exception_when_category_is_already_set(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Projection streams all|names|categories already set');

        $context = $this->newContext();

        $this->assertEmpty($context->queries());

        $context->fromCategories('foo-bar');

        $context->fromStreams('foo', 'bar');
    }

    /**
     * @test
     */
    public function it_set_from_categories(): void
    {
        $context = $this->newContext();

        $this->assertEmpty($context->queries());

        $context->fromCategories('foo-bar', 'foo-baz');

        $this->assertEquals(['categories' => ['foo-bar', 'foo-baz']], $context->queries());
    }

    /**
     * @test
     */
    public function it_set_from_all(): void
    {
        $context = $this->newContext();

        $this->assertEmpty($context->queries());

        $context->fromAll();

        $this->assertEquals(['all' => true], $context->queries());
    }

    /**
     * @test
     */
    public function it_set_event_handlers_as_closure(): void
    {
        $context = $this->newContext();

        $context->whenAny(fn (): int => 1);

        $this->assertInstanceOf(ProcessClosureEvent::class, $context->eventHandlers());
    }

    /**
     * @test
     */
    public function it_set_event_handlers_as_array(): void
    {
        $context = $this->newContext();

        $context->when([]);

        $this->assertInstanceOf(ProcessArrayEvent::class, $context->eventHandlers());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_event_handlers_is_already_set(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Projection event handlers already set');

        $context = $this->newContext();

        $context->whenAny(fn (): int => 1);
        $context->whenAny(fn (): int => 1);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_event_handlers_is_already_set_2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Projection event handlers already set');

        $context = $this->newContext();

        $context->whenAny(fn (): int => 1);
        $context->when([]);
    }

    /**
     * @test
     */
    public function it_validate_globally(): void
    {
        $this->expectExceptionMessage('Projection streams all|names|categories not set');

        $context = $this->newContext();

        $context->validateStub();
    }

    /**
     * @test
     */
    public function it_validate_globally_2(): void
    {
        $this->expectExceptionMessage('Projection event handlers not set');

        $context = $this->newContext();

        $context->fromStreams('foo');

        $context->validateStub();
    }

    /**
     * @test
     */
    public function it_validate_globally_3(): void
    {
        $this->expectExceptionMessage('Projection query filter not set');

        $context = $this->newContext();

        $context->fromStreams('foo');

        $context->when([]);

        $context->validateStub();
    }

    /**
     * @test
     */
    public function it_raise_exception_if_query_filter_is_not_an_instance_of_projection_query_filter_if_context_is_persistent(): void
    {
        $this->expectExceptionMessage('Persistent projector require a projection query filter');

        $queryFilter = new class() implements QueryFilter
        {
            public function apply(): callable
            {
                return static fn (): int => 1;
            }
        };

        $context = $this->newContext();

        $this->assertTrue($context->isPersistent);

        $context->fromStreams('foo');

        $context->when([]);

        $context->withQueryFilter($queryFilter);

        $context->validateStub();
    }

    /**
     * @test
     */
    public function it_reset_projection_state(): void
    {
        $context = $this->newContext();

        $init = static fn (): array => ['counter' => 4];

        $context
            ->initialize($init)
            ->fromStreams('foo')
            ->whenAny(fn (DomainEvent $event, array $state): array => $state)
            ->withQueryFilter($this->provideQueryFilter());

        $this->assertSame($init, $context->initCallback);

        $context->state->put(['counter' => 25]);

        $context->resetStateWithInitialize();

        $this->assertEquals(['counter' => 4], $context->state->get());
    }

    /**
     * @test
     */
    public function it_reset_projection_state_when_init_callback_not_set(): void
    {
        $context = $this->newContext();

        $context
            ->fromStreams('foo')
            ->whenAny(fn (DomainEvent $event, array $state): array => $state)
            ->withQueryFilter($this->provideQueryFilter());

        $this->assertNull($context->initCallback);

        $context->state->put(['counter' => 25]);

        $context->resetStateWithInitialize();

        $this->assertEmpty($context->state->get());
    }

    private function provideQueryFilter(): QueryFilter
    {
        return new class() implements QueryFilter
        {
            public function apply(): callable
            {
                return static fn (): int => 1;
            }
        };
    }

    private function provideProjectionQueryFilter(): ProjectionQueryFilter
    {
        return new class() implements ProjectionQueryFilter
        {
            public int $position = 0;

            public function apply(): callable
            {
                return fn (): int => $this->position;
            }

            public function setCurrentPosition(int $streamPosition): void
            {
                $this->position = $streamPosition;
            }
        };
    }
}

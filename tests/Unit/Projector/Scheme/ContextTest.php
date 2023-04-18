<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\ProcessArrayEvent;
use Chronhub\Storm\Projector\Scheme\ProcessClosureEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\Unit\Projector\Stubs\CasterStub;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Context::class)]
final class ContextTest extends UnitTestCase
{
    private Context $context;

    protected function setUp(): void
    {
        $this->context = new Context();
    }

    public function testInitializeProjection()
    {
        $initCallback = fn (): array => [];

        $this->context->initialize($initCallback);

        $this->assertSame($initCallback, $this->context->initCallback());
    }

    public function testExceptionRaisedWhenContextAlreadyInitialized()
    {
        $initCallback = fn (): array => [];

        $this->context->initialize($initCallback);

        $this->expectException(InvalidArgumentException::class);

        $this->context->initialize($initCallback);
    }

    public function testExceptionRaisedWhenClosureIsStatic(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Static closure is not allowed');

        $initCallback = static fn (): array => [];

        $this->context->initialize($initCallback);
    }

    /**
     * @test
     */
    public function testSetQueryFilter(): void
    {
        $queryFilter = new class() implements QueryFilter
        {
            public function apply(): callable
            {
                return static fn () => [];
            }
        };

        $this->context->withQueryFilter($queryFilter);

        $this->assertSame($queryFilter, $this->context->queryFilter());
    }

    public function testExceptionRaisedWhenSetQueryFilterTwice()
    {
        $queryFilter = new class() implements QueryFilter
        {
            public function apply(): callable
            {
                return static fn () => [];
            }
        };

        $this->context->withQueryFilter($queryFilter);

        $this->expectException(InvalidArgumentException::class);

        $this->context->withQueryFilter($queryFilter);
    }

    public function testSetSubscribeToManyStreams()
    {
        $this->context->fromStreams('stream_1', 'stream_2');

        $this->assertEquals(['names' => ['stream_1', 'stream_2']], $this->context->queries());
    }

    public function testSetSubscribeToManyCategories()
    {
        $this->context->fromCategories('category-1', 'category-2');

        $this->assertEquals(['categories' => ['category-1', 'category-2']], $this->context->queries());
    }

    public function testSetSubscribeToAllStreams()
    {
        $this->context->fromAll();

        $this->assertEquals(['all' => true], $this->context->queries());
    }

    /**
     * @test
     */
    public function testExceptionRaisedWhenStreamsAlreadySet()
    {
        $this->context->fromStreams('stream-1', 'stream-2');

        $this->expectException(InvalidArgumentException::class);

        $this->context->fromCategories('category-1', 'category-2');
    }

    public function testExceptionRaisedWhenStreamsAlreadySet_2()
    {
        $this->context->fromStreams('stream-1', 'stream-2');

        $this->expectException(InvalidArgumentException::class);

        $this->context->fromStreams('stream-3', 'stream-4');
    }

    public function testExceptionRaisedWhenStreamsAlreadySet_3()
    {
        $this->context->fromStreams('stream-1', 'stream-2');

        $this->expectException(InvalidArgumentException::class);

        $this->context->fromAll();
    }

    public function testExceptionRaisedWhenQueriesIsEmpty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Projection streams all|names|categories not set');

        $this->context->queries();
    }

    public function testSetEventHandlersAsArray(): void
    {
        $eventHandlers = [
            'event1' => function () {
            },
            'event2' => function () {
            },
        ];

        $this->context->when($eventHandlers);

        $this->assertEquals(new ProcessArrayEvent($eventHandlers), $this->context->eventHandlers());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Projection event handlers already set');

        $this->context->when($eventHandlers);
    }

    public function testSetEventHandlersAsClosure(): void
    {
        $eventHandlers = fn () => [];

        $this->context->whenAny($eventHandlers);

        $this->assertEquals(new ProcessClosureEvent($eventHandlers), $this->context->eventHandlers());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Projection event handlers already set');

        $this->context->whenAny($eventHandlers);
    }

    public function testExceptionRaisedWhenSetEventHandlerAsStaticClosure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Static closure is not allowed');

        $eventHandlers = static fn () => [];

        $this->context->whenAny($eventHandlers);
    }

    public function testExceptionRaisedWhenSetEventHandlersAsStaticClosure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Static closure is not allowed');

        $eventHandlers = ['foo' => static fn () => []];

        $this->context->when($eventHandlers);
    }

    public function testExceptionRaisedWhenEventHandlersNotSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Projection event handlers not set');

        $this->context->eventHandlers();
    }

    public function testExceptionRaisedWhenQueryFilterNotSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Projection query filter not set');

        $this->context->queryFilter();
    }

    public function testCastClosureEventHandlers(): void
    {
        $eventHandlers = function (SomeEvent $event): void {
            //
        };

        $this->context->whenAny($eventHandlers);
        $this->context->castEventHandlers(new CasterStub());

        $castEventHandlers = $this->context->eventHandlers();

        $process = new ProcessClosureEvent($eventHandlers);
        $this->assertEquals($process, $castEventHandlers);
    }

    public function testCastArrayEventHandlers(): void
    {
        $eventHandlers = [
            'event1' => function (SomeEvent $event): void {
                //
            },
        ];

        $this->context->when($eventHandlers);
        $this->context->castEventHandlers(new CasterStub());

        $castEventHandlers = $this->context->eventHandlers();

        $process = new ProcessArrayEvent($eventHandlers);
        $this->assertEquals($process, $castEventHandlers);
    }

    public function testCastInitCallback(): void
    {
        $init = fn (): array => ['count' => 0];

        $this->context->initialize($init);

        $result = $this->context->castInitCallback(new CasterStub());

        $this->assertSame($result, ['count' => 0]);

        $castEventHandlers = $this->context->initCallback();

        $this->assertEquals($castEventHandlers, $init);
    }

    public function testCastInitCallbackBotInitialized(): void
    {
        $this->assertNull($this->context->initCallback());

        $result = $this->context->castInitCallback(new CasterStub());

        $this->assertSame($result, []);

        $this->assertNull($this->context->initCallback());
    }
}

<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\InteractWithContext;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Tests\Unit\Projector\Stubs\InteractWithContextStub;

#[CoversClass(InteractWithContext::class)]
class InteractWithContextTest extends UnitTestCase
{
    private Context|MockObject $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = $this->createMock(Context::class);
    }

    #[Test]
    public function it_initialize_projection(): void
    {
        $init = fn (): array => ['count' => 0];
        $this->context
            ->expects($this->once())
            ->method('initialize')
            ->with($init);

        $stub = new InteractWithContextStub($this->context);

        $stub->initialize($init);
    }

    #[Test]
    public function it_define_stream(): void
    {
        $stream = 'foo';

        $this->context
            ->expects($this->once())
            ->method('fromStreams')
            ->with($stream);

        $stub = new InteractWithContextStub($this->context);

        $stub->fromStreams($stream);
    }

    #[Test]
    public function it_define_many_streams(): void
    {
        $streams = ['foo', 'bar', 'baz'];

        $this->context
            ->expects($this->once())
            ->method('fromStreams')
            ->with(...$streams);

        $stub = new InteractWithContextStub($this->context);

        $stub->fromStreams(...$streams);
    }

    #[Test]
    public function it_define_category(): void
    {
        $category = 'foo';

        $this->context
            ->expects($this->once())
            ->method('fromCategories')
            ->with($category);

        $stub = new InteractWithContextStub($this->context);

        $stub->fromCategories($category);
    }

    #[Test]
    public function it_define_many_categories(): void
    {
        $categories = ['foo', 'bar', 'baz'];

        $this->context
            ->expects($this->once())
            ->method('fromCategories')
            ->with(...$categories);

        $stub = new InteractWithContextStub($this->context);

        $stub->fromCategories(...$categories);
    }

    #[Test]
    public function it_define_all_stream(): void
    {
        $this->context
            ->expects($this->once())
            ->method('fromAll');

        $stub = new InteractWithContextStub($this->context);

        $stub->fromAll();
    }

    #[Test]
    public function it_define_event_handlers_as_array(): void
    {
        $eventHandlers = [
            'foo' => fn () => null,
            'bar' => fn () => null,
        ];

        $this->context
            ->expects($this->once())
            ->method('when')
            ->with($eventHandlers);

        $stub = new InteractWithContextStub($this->context);

        $stub->when($eventHandlers);
    }

    #[Test]
    public function it_define_event_handlers_as_closure(): void
    {
        $eventHandler = fn () => null;

        $this->context
            ->expects($this->once())
            ->method('whenAny')
            ->with($eventHandler);

        $stub = new InteractWithContextStub($this->context);

        $stub->whenAny($eventHandler);
    }

    #[Test]
    public function it_define_queryFilter(): void
    {
        $queryFilter = $this->createMock(QueryFilter::class);

        $this->context
            ->expects($this->once())
            ->method('withQueryFilter')
            ->with($queryFilter);

        $stub = new InteractWithContextStub($this->context);

        $stub->withQueryFilter($queryFilter);
    }
}

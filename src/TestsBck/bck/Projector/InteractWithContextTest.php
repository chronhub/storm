<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Projector\InteractWithContext;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(InteractWithContext::class)]
final class InteractWithContextTest extends UnitTestCase
{
    private ContextInterface|MockObject $context;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ContextInterface::class);
    }

    public function testInitializeContext(): void
    {
        $this->context->expects($this->once())->method('initialize');

        $this->newInstance()->initialize(fn () => null);
    }

    public function testFromStreams(): void
    {
        $this->context->expects($this->once())->method('fromStreams')->with('foo', 'bar');

        $this->newInstance()->fromStreams('foo', 'bar');
    }

    public function testFromCategories(): void
    {
        $this->context->expects($this->once())->method('fromCategories')->with('foo', 'bar');

        $this->newInstance()->fromCategories('foo', 'bar');
    }

    public function testFromAll(): void
    {
        $this->context->expects($this->once())->method('fromAll');

        $this->newInstance()->fromAll();
    }

    public function testWhenAny(): void
    {
        $this->context->expects($this->once())->method('whenAny')->with(fn (DomainEvent $event, array $state): array => []);

        $this->newInstance()->whenAny(fn (DomainEvent $event, array $state): array => []);
    }

    public function testWhen(): void
    {
        $eventHandlers = [
            fn (DomainEvent $event, array $state): array => [],
            fn (DomainEvent $event, array $state): array => [],
        ];

        $this->context->expects($this->once())->method('when')->with($eventHandlers);

        $this->newInstance()->when($eventHandlers);
    }

    public function testWithQueryFilter(): void
    {
        $queryFilter = $this->createMock(QueryFilter::class);
        $this->context->expects($this->once())->method('withQueryFilter')->with($queryFilter);

        $this->newInstance()->withQueryFilter($queryFilter);
    }

    public function newInstance(): object
    {
        $context = $this->context;

        return new class($context)
        {
            use InteractWithContext;

            public function __construct(protected ContextInterface $context)
            {
            }
        };
    }
}

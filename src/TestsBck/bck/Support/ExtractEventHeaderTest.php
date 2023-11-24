<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Support;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Reporter\Reporting;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Reporter\ExtractEventHeader;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use DomainException;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

final class ExtractEventHeaderTest extends UnitTestCase
{
    #[DataProvider('provideInternalPositionHeader')]
    public function testExtractInternalPosition(Message|DomainEvent $event): void
    {
        $instance = $this->extractEventHeaderInstance();

        $this->assertEquals(1, $instance->toInternalPosition($event));
    }

    #[DataProvider('provideInvalidInternalPositionHeader')]
    public function testExceptionRaisedWithInvalidInternalPosition(mixed $invalidInternalPosition): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Internal position must be a positive integer');

        $event = SomeEvent::fromContent([])->withHeader(EventHeader::INTERNAL_POSITION, $invalidInternalPosition);

        $instance = $this->extractEventHeaderInstance();

        $instance->toInternalPosition($event);
    }

    public function testExceptionRaisedWithInvalidAggregateId(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Aggregate id type type must be an instance of '.AggregateIdentity::class);

        $event = SomeEvent::fromContent([])->withHeader(EventHeader::AGGREGATE_ID, 'invalid');

        $instance = $this->extractEventHeaderInstance();

        $instance->toAggregateIdentity($event);
    }

    #[DataProvider('provideAggregateIdentityHeader')]
    public function testExtractAggregateIdentity(Message|DomainEvent $event): void
    {
        $instance = $this->extractEventHeaderInstance();

        $this->assertInstanceOf(AggregateIdentity::class, $instance->toAggregateIdentity($event));
        $this->assertEquals('32985c05-5e96-419d-b552-c55217ebeb82', $instance->toAggregateIdentity($event)->toString());
        $this->assertEquals(V4AggregateId::class, $instance->toAggregateIdentity($event)::class);
    }

    private function extractEventHeaderInstance(): object
    {
        return new class
        {
            use ExtractEventHeader;

            public function toInternalPosition(Message|Reporting $event): int
            {
                return $this->extractInternalPosition($event);
            }

            public function toAggregateIdentity(Message|Reporting $event): AggregateIdentity
            {
                return $this->extractAggregateIdentity($event);
            }
        };
    }

    public static function provideInternalPositionHeader(): Generator
    {
        $event = SomeEvent::fromContent([])->withHeader(EventHeader::INTERNAL_POSITION, 1);

        yield [$event];

        yield [new Message($event)];
    }

    public static function provideAggregateIdentityHeader(): Generator
    {
        $event = SomeEvent::fromContent([])
            ->withHeader(EventHeader::AGGREGATE_ID, '32985c05-5e96-419d-b552-c55217ebeb82')
            ->withHeader(EventHeader::AGGREGATE_ID_TYPE, V4AggregateId::class);

        yield [$event];

        yield [new Message($event)];
    }

    public static function provideInvalidInternalPositionHeader(): Generator
    {
        yield [-1];
        yield [0];
        yield [null];
        yield ['invalid'];
        yield [new stdClass()];
    }
}

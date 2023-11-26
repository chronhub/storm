<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Clock\PointInTimeFactory;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;

beforeEach(function () {
    $this->chronicler = $this->createMock(Chronicler::class);
    $this->clock = $this->createMock(SystemClock::class);

    $this->loadStreams = new LoadStreams($this->chronicler, $this->clock);
});

dataset('streamEvents', [fn () => yield [
    SomeEvent::fromContent([])->withHeaders([
        EventHeader::INTERNAL_POSITION => 11,
        Header::EVENT_TIME => PointInTimeFactory::make(),
    ]),
    SomeEvent::fromContent([])->withHeaders([
        EventHeader::INTERNAL_POSITION => 12,
        Header::EVENT_TIME => PointInTimeFactory::make(),
    ]),
]]);

dataset('streamEvents2', [fn () => yield [
    SomeEvent::fromContent([])->withHeaders([
        EventHeader::INTERNAL_POSITION => 23,
        Header::EVENT_TIME => PointInTimeFactory::make(),
    ]),
    SomeEvent::fromContent([])->withHeaders([
        EventHeader::INTERNAL_POSITION => 24,
        Header::EVENT_TIME => PointInTimeFactory::make(),
    ]),
]]);

it('can load streams', function () {
    $queryFilter = $this->createMock(ProjectionQueryFilter::class);
    $queryFilter->expects($this->exactly(2))
        ->method('setCurrentPosition');

    $this->chronicler->expects($this->exactly(2))
        ->method('retrieveFiltered')
        ->willReturnOnConsecutiveCalls(provideSomeEvents(), provideSomeEvents());

    $this->clock->expects($this->exactly(2))->method('toPointInTime')->willReturn(PointInTimeFactory::make());

    $streams = $this->loadStreams->batch(['customer-123' => 1, 'customer-456' => 2], $queryFilter);

    expect($streams)->toBeInstanceOf(MergeStreamIterator::class)
        ->and($streams->count())->toBe(4)
        ->and($streams->valid())->toBeTrue()
        ->and($streams->current())->toBeInstanceOf(SomeEvent::class)
        ->and($streams->key())->toBe(11);

});

<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Clock;

use Chronhub\Storm\Clock\PointInTimeFactory;
use DateTimeImmutable;

it('can make point in time', function (): void {
    $pointInTime = PointInTimeFactory::make();

    $this->assertInstanceOf(DateTimeImmutable::class, $pointInTime);
});

it('generate times', function (): void {
    $times = PointInTimeFactory::times(3);

    $this->assertCount(3, $times);
    $this->assertInstanceOf(DateTimeImmutable::class, $times->first());
});

describe('generate times with interval', function (): void {
    test('one per default', function (): void {
        $times = PointInTimeFactory::timesWithInterval('-1 day');

        $this->assertCount(1, $times);
        $this->assertInstanceOf(DateTimeImmutable::class, $times->first());
    });

    test('with given modifier', function (): void {
        $expectedTimes = 10;
        $times = PointInTimeFactory::timesWithInterval('+1 day', '+1 hour', $expectedTimes);

        $this->assertCount($expectedTimes, $times);

        /** @var DateTimeImmutable $startedTime */
        $startedTime = $times->shift();

        while ($times->count() > 0) {
            $this->assertInstanceOf(DateTimeImmutable::class, $times->first());
            $this->assertTrue($startedTime < $times->first());
            $this->assertEquals('+1 hour', $startedTime->diff($times->first())->format('%R%h hour'));

            $startedTime = $times->first();
            $times->shift();
        }
    });
});

describe('generate between', function (): void {
    test('two point int time per default', function (): void {
        $between = PointInTimeFactory::between('-1 day', '+1 day');

        $this->assertCount(2, $between);
        $this->assertInstanceOf(DateTimeImmutable::class, $between->first());
    });

    test('with given interval and times', function (): void {
        $expectedTimes = 5;
        $between = PointInTimeFactory::between('-1 day', '+1 day', '+1 hour', $expectedTimes);

        $this->assertCount($expectedTimes, $between);

        /** @var DateTimeImmutable $startedTime */
        $startedTime = $between->shift();

        $this->assertTrue($startedTime < PointInTimeFactory::make());

        while ($between->count() > 0) {
            $this->assertInstanceOf(DateTimeImmutable::class, $between->first());
            $this->assertTrue($startedTime < $between->first());
            $this->assertEquals('+1 hour', $startedTime->diff($between->first())->format('%R%h hour'));

            $startedTime = $between->first();
            $between->shift();
        }
    });

    test('with interval but failed expected times', function () {
        $expectedTimes = 10;
        $between = PointInTimeFactory::between('-1 day', '+1 day', '+10 hours', $expectedTimes);

        $this->assertNotEquals($expectedTimes, $between->count());
        $this->assertCount(5, $between);
    });

    test('return empty collection when end modifier greater or equal than start modifier ',
        function (string $startModifier, string $endModifier): void {
            $between = PointInTimeFactory::between($startModifier, $endModifier, '+1 hours', 100);

            $this->assertCount(0, $between);
        })->with([
            'end modifier greater' => ['+1 day', '-1 day'],
            'end modifier equal' => ['+1 day', '+1 day'],
        ]);
});

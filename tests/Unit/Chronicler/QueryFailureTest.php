<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Exception;
use Generator;
use RuntimeException;
use InvalidArgumentException;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Chronicler\Exceptions\QueryFailure;

final class QueryFailureTest extends UnitTestCase
{
    /**
     * @test
     *
     * @dataProvider provideException
     */
    public function it_wrap_exception_given(Exception $exception): void
    {
        $this->expectException(QueryFailure::class);
        $this->expectExceptionMessage('A query exception occurred: foo');
        $this->expectExceptionCode(00000);

        throw QueryFailure::fromThrowable($exception);
    }

    public function provideException(): Generator
    {
        yield [new RuntimeException('foo'), 00000];
        yield [new InvalidArgumentException('foo'), 00000];
    }
}

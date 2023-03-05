<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Exception;
use Generator;
use RuntimeException;
use InvalidArgumentException;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Chronicler\Exceptions\QueryFailure;

#[CoversClass(QueryFailure::class)]
final class QueryFailureTest extends UnitTestCase
{
    #[DataProvider('provideException')]
    #[Test]
    public function it_wrap_exception_given(Exception $exception): never
    {
        $this->expectException(QueryFailure::class);
        $this->expectExceptionMessage('A query exception occurred: foo');
        $this->expectExceptionCode(00000);

        throw QueryFailure::fromThrowable($exception);
    }

    public static function provideException(): Generator
    {
        yield [new RuntimeException('foo'), 00000];
        yield [new InvalidArgumentException('foo'), 00000];
    }
}

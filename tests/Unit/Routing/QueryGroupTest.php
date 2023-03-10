<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Routing\QueryGroup;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\CollectRoutes;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Message\AliasFromClassName;

#[CoversClass(QueryGroup::class)]
final class QueryGroupTest extends UnitTestCase
{
    #[Test]
    public function it_assert_type(): void
    {
        $routes = new CollectRoutes(new AliasFromClassName());

        $this->assertEquals(DomainType::QUERY, (new QueryGroup('default', $routes))->getType());
    }
}

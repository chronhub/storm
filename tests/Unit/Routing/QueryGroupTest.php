<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Routing\QueryGroup;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Message\AliasFromClassName;

final class QueryGroupTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_assert_type(): void
    {
        $routes = new CollectRoutes(new AliasFromClassName());

        $this->assertEquals(DomainType::QUERY, (new QueryGroup('default', $routes))->getType());
    }
}

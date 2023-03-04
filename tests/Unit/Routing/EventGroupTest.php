<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Routing\EventGroup;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Message\AliasFromClassName;

final class EventGroupTest extends UnitTestCase
{
    #[Test]
    public function it_assert_type(): void
    {
        $routes = new CollectRoutes(new AliasFromClassName());

        $this->assertEquals(DomainType::EVENT, (new EventGroup('default', $routes))->getType());
    }
}

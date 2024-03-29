<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Routing;

use Chronhub\Storm\Routing\Route;
use Countable;
use Illuminate\Support\Collection;
use JsonSerializable;

interface RouteCollection extends JsonSerializable, Countable
{
    /**
     * @param class-string $messageName
     */
    public function addRoute(string $messageName): Route;

    /**
     * @param string|class-string $messageName
     */
    public function match(string $messageName): ?Route;

    /**
     * @param class-string $messageName
     */
    public function matchOriginal(string $messageName): ?Route;

    /**
     * @return Collection<Route>
     */
    public function getRoutes(): Collection;
}

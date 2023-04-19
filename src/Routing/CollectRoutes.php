<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing;

use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Routing\RouteCollection;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Illuminate\Support\Collection;

final readonly class CollectRoutes implements RouteCollection
{
    private Collection $routes;

    public function __construct(private MessageAlias $messageAlias)
    {
        $this->routes = new Collection();
    }

    public function addRoute(string $messageName): Route
    {
        if ($this->matchOriginal($messageName) instanceof Route) {
            throw new RoutingViolation("Message name already exists $messageName");
        }

        $route = new Route($messageName);

        $route->alias($this->messageAlias->classToAlias($messageName));

        $this->routes->push($route);

        return $route;
    }

    public function match(string $messageName): ?Route
    {
        $byMessageName = static fn (Route $route): bool => ($messageName === $route->getName());

        return $this->routes->filter($byMessageName)->first();
    }

    public function matchOriginal(string $messageName): ?Route
    {
        $byOriginalMessageName = static fn (Route $route): bool => ($messageName === $route->getOriginalName());

        return $this->routes->filter($byOriginalMessageName)->first();
    }

    public function getRoutes(): Collection
    {
        return clone $this->routes;
    }

    public function count(): int
    {
        return $this->routes->count();
    }

    public function jsonSerialize(): array
    {
        return $this->routes->jsonSerialize();
    }
}

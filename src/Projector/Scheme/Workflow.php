<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Closure;
use Chronhub\Storm\Contracts\Projector\Subscription;
use function array_reduce;
use function array_reverse;

final class Workflow
{
    /**
     * @var array<callable>
     */
    private array $activities;

    public function __construct(private readonly Subscription $subscription)
    {
    }

    public function through(array $activities): self
    {
        $this->activities = $activities;

        return $this;
    }

    public function process(Closure $destination): bool
    {
        $process = array_reduce(
            array_reverse($this->activities),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $process($this->subscription);
    }

    protected function prepareDestination(Closure $destination): Closure
    {
        return static fn (Subscription $subscription) => $destination($subscription);
    }

    protected function carry(): Closure
    {
        return static fn (Closure $stack, callable $activity) => static fn (Subscription $subscription) => $activity($subscription, $stack);
    }
}

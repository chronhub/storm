<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Closure;
use function array_reduce;
use function array_reverse;

class Pipeline
{
    /**
     * @var array<callable>
     */
    private array $pipes = [];

    private Context $passable;

    public function send(Context $passable): self
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * @param  array<callable>  $pipes
     * @return $this
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;

        return $this;
    }

    public function then(Closure $destination): bool
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    protected function prepareDestination(Closure $destination): Closure
    {
        return static fn (Context $passable) => $destination($passable);
    }

    protected function carry(): Closure
    {
        return static fn (Closure $stack, callable $pipe) => static fn (Context $passable) => $pipe($passable, $stack);
    }
}

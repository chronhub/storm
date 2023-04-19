<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing;

use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use JsonSerializable;
use function array_merge;
use function class_exists;

class Route implements JsonSerializable
{
    private ?array $queue = null;

    private ?string $alias = null;

    /**
     * @return array<int, string|object>
     */
    private array $handlers = [];

    public function __construct(private readonly string $name)
    {
        if (! class_exists($this->name)) {
            throw new RoutingViolation(
                'Message name must be a valid class name, got '.$this->name
            );
        }
    }

    public function alias(string $messageAlias): static
    {
        $this->alias = $messageAlias;

        return $this;
    }

    public function to(string|object ...$messageHandlers): static
    {
        $this->handlers = array_merge($this->handlers, $messageHandlers);

        return $this;
    }

    public function onQueue(array $queueOptions = []): static
    {
        if ($queueOptions !== []) {
            $this->queue = $queueOptions;
        }

        return $this;
    }

    /**
     * @return class-string|string
     */
    public function getName(): string
    {
        return $this->alias ?? $this->name;
    }

    /**
     * @return class-string
     */
    public function getOriginalName(): string
    {
        return $this->name;
    }

    public function getHandlers(): array
    {
        return $this->handlers;
    }

    public function getQueue(): ?array
    {
        return $this->queue;
    }

    public function jsonSerialize(): array
    {
        return [
            'message_name' => $this->getName(),
            'original_message_name' => $this->getOriginalName(),
            'message_handlers' => $this->getHandlers(),
            'queue_options' => $this->getQueue(),
        ];
    }
}

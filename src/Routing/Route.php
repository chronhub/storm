<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing;

use JsonSerializable;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use function count;
use function array_merge;
use function class_exists;

class Route implements JsonSerializable
{
    private ?array $queueOptions = null;

    private ?string $messageAlias = null;

    private array $messageHandlers = [];

    public function __construct(private readonly string $messageName)
    {
        if (! class_exists($this->messageName)) {
            throw new RoutingViolation("Message name must be a valid class name, got $this->messageName");
        }
    }

    public function alias(string $messageAlias): static
    {
        $this->messageAlias = $messageAlias;

        return $this;
    }

    public function to(string|object ...$messageHandlers): static
    {
        $this->messageHandlers = array_merge($this->messageHandlers, $messageHandlers);

        return $this;
    }

    public function onQueue(array $queueOptions = []): static
    {
        if (count($queueOptions) === 0) {
            return $this;
        }

        $this->queueOptions = $queueOptions;

        return $this;
    }

    /**
     * @return class-string|string
     */
    public function getMessageName(): string
    {
        return $this->messageAlias ?? $this->messageName;
    }

    /**
     * @return class-string
     */
    public function getOriginalMessageName(): string
    {
        return $this->messageName;
    }

    public function getMessageHandlers(): array
    {
        return $this->messageHandlers;
    }

    public function getQueueOptions(): ?array
    {
        return $this->queueOptions;
    }

    public function jsonSerialize(): array
    {
        return [
            'message_name' => $this->getMessageName(),
            'original_message_name' => $this->getOriginalMessageName(),
            'message_handlers' => $this->getMessageHandlers(),
            'queue_options' => $this->getQueueOptions(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Options\DefaultOption;

interface SubscriptionFactory
{
    public function createQuerySubscription(ProjectionOption $option): QuerySubscriptionInterface;

    public function createEmitterSubscription(string $streamName, ProjectionOption $option): EmitterSubscriptionInterface;

    public function createReadModelSubscription(string $streamName, ReadModel $readModel, ProjectionOption $option): ReadModelSubscriptionInterface;

    /**
     * Creates a ProjectionOption instance with the specified options.
     *
     * Priority for non-empty array options over a provided ProjectionOption instance in the factory constructor
     * Unless, the ProjectionOption instance is immutable.
     * If an option instance is supplied in the factory constructor, it will be merged with the array options.
     * If no option instance is provided, a default instance with the merged options will be returned.
     * If an option instance is given in the factory constructor, it will be returned.
     * Otherwise, a default option instance will be created using the array provided in the factory constructor.
     *
     * @param array<ProjectionOption::*, null|string|int|bool|array> $options
     *
     * @see DefaultOption
     */
    public function createOption(array $options = []): ProjectionOption;

    public function getProjectionProvider(): ProjectionProvider;

    public function getSerializer(): JsonSerializer;

    public function getQueryScope(): ?ProjectionQueryScope;
}

<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Options;

use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionOptionImmutable;

use function array_merge;
use function get_class;

final readonly class ProjectionOptionResolver
{
    public function __construct(private ProjectionOption|array $options = [])
    {
    }

    public function __invoke(array $options = []): ProjectionOption
    {
        if ($options !== []) {
            return $this->mergeOptions($options);
        }

        if ($this->options instanceof ProjectionOption) {
            return $this->options;
        }

        return new DefaultOption(...$this->options);
    }

    private function mergeOptions(array $options): ProjectionOption
    {
        if ($this->options instanceof ProjectionOption && ! $this->options instanceof ProjectionOptionImmutable) {
            $optionClass = get_class($this->options);

            return new $optionClass(...array_merge($this->options->jsonSerialize(), $options));
        }

        return new DefaultOption(...$options);
    }
}

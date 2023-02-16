<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Projector\Scheme\ReadModelCaster;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorCaster;

final readonly class ProjectReadModel implements ReadModelProjector
{
    use InteractWithContext;
    use ProvidePersistentProjector;

    public function __construct(protected Context $context,
                                protected ProjectorRepository $repository,
                                protected Chronicler $chronicler,
                                protected string $streamName,
                                private ReadModel $readModel)
    {
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    protected function getCaster(): ReadModelProjectorCaster
    {
        return new ReadModelCaster($this, $this->context->currentStreamName);
    }
}

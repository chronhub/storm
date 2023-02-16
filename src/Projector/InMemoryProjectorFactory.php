<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Contracts\Projector\Store;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\Repository\InMemoryStore;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;
use Chronhub\Storm\Projector\Repository\ReadModelProjectorRepository;
use Chronhub\Storm\Projector\Repository\PersistentProjectorRepository;

final class InMemoryProjectorFactory extends ProjectorManagerFactory
{
    public function makeRepository(Context $context, Store $store, ?ReadModel $readModel): ProjectorRepository
    {
        $store = new InMemoryStore($store);

        if ($readModel) {
            return new ReadModelProjectorRepository($context, $store, $readModel);
        }

        return new PersistentProjectorRepository($context, $store, $this->chronicler);
    }
}

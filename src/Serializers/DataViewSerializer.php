<?php

namespace Amethyst\Serializers;

use Amethyst\Services\PermissionService;
use Illuminate\Support\Collection;
use Railken\Lem\Contracts\EntityContract;
use Railken\Lem\Serializer;
use Symfony\Component\Yaml\Yaml;

class DataViewSerializer extends Serializer
{
    /**
     * Serialize entity.
     *
     * @param \Railken\Lem\Contracts\EntityContract $entity
     * @param \Illuminate\Support\Collection        $select
     *
     * @return \Railken\Bag
     */
    public function serialize(EntityContract $entity, Collection $select = null)
    {
        $bag = parent::serialize($entity, $select);

        $config = Yaml::parse((string) $bag->get('config'));

        $agent = $this->getManager()->getAgent();

        $bag->set('processed', $config);

        return $bag;
    }
}

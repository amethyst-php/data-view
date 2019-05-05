<?php

namespace Railken\Amethyst\Serializers;

use Illuminate\Support\Collection;
use Railken\Lem\Contracts\EntityContract;
use Railken\Lem\Serializer;
use Symfony\Component\Yaml\Yaml;
use Railken\Amethyst\Services\PermissionService;

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

        if (isset($config['permissions'])) {
            $permission = app(PermissionService::class)->findFirstPermissionByPolicyCached($agent, $config['permissions'][0]);

            if ($permission && isset($config['options']['attributes'])) {

                $attrs = explode(",", $permission->pivot->attribute);
                foreach ($config['options']['attributes'] as $key => &$attribute) {

                    if (!in_array($attribute['name'], $attrs)) {
                        unset($config['options']['attributes'][$key]);
                    }
                }

            }
        }

        $bag->set('processed', $config);


        return $bag;
    }
}

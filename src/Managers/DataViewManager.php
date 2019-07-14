<?php

namespace Amethyst\Managers;

use Amethyst\Common\ConfigurableManager;
use Railken\Lem\Manager;

/**
 * @method \Amethyst\Models\DataView newEntity()
 * @method \Amethyst\Schemas\DataViewSchema getSchema()
 * @method \Amethyst\Repositories\DataViewRepository getRepository()
 * @method \Amethyst\Serializers\DataViewSerializer getSerializer()
 * @method \Amethyst\Validators\DataViewValidator getValidator()
 * @method \Amethyst\Authorizers\DataViewAuthorizer getAuthorizer()
 */
class DataViewManager extends Manager
{
    use ConfigurableManager;

    /**
     * @var string
     */
    protected $config = 'amethyst.data-view.data.data-view';
}

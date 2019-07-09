<?php

namespace Amethyst\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Amethyst\Common\ConfigurableModel;
use Railken\Lem\Contracts\EntityContract;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DataView extends Model implements EntityContract
{
    use SoftDeletes;
    use ConfigurableModel;

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->ini('amethyst.data-view.data.data-view');
        parent::__construct($attributes);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function authenticable(): MorphTo
    {
        return $this->morphTo();
    }
}

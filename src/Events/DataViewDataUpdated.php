<?php

namespace Amethyst\Events;

use Amethyst\Models\DataView;
use Illuminate\Queue\SerializesModels;

class DataViewDataUpdated
{
    use SerializesModels;

    public $dataView;

    public function __construct(DataView $dataView)
    {
        $this->dataView = $dataView;
    }
}

<?php

namespace Amethyst\Events;

use Illuminate\Queue\SerializesModels;
use Amethyst\Models\DataView;

class DataViewDataUpdated
{
    use SerializesModels;

    public $dataView;

    public function __construct(DataView $dataView)
    {
        $this->dataView = $dataView;
    }
}

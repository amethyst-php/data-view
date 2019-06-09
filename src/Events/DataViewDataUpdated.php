<?php

namespace Railken\Amethyst\Events;

use Exception;
use Illuminate\Queue\SerializesModels;
use Railken\Amethyst\Models\DataView;

class DataViewDataUpdated
{
    use SerializesModels;

    public $dataView;

    public function __construct(DataView $dataView) 
    {
    	$this->dataView = $dataView;
    }
}

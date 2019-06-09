<?php

namespace Railken\Amethyst\Events;

use Exception;
use Illuminate\Queue\SerializesModels;

class DataViewDataGenerated
{
    use SerializesModels;

    public $name;

    public function __construct(string $name) 
    {
    	$this->name = $name;
    }
}

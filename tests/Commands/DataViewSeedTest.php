<?php

namespace Amethyst\Tests\Commands;

use Amethyst\Tests\BaseTestCase;

class DataViewSeedTest extends BaseTestCase
{
    public function testSeed()
    {
        $this->artisan('amethyst:data-view:seed');
        $this->assertEquals(1, 1);
    }
}

<?php

namespace Railken\Amethyst\Tests\Commands;

use Railken\Amethyst\Tests\BaseTest;

class DataViewSeedTest extends BaseTest
{
    public function testSeed()
    {
        $this->artisan('amethyst:data-view:seed');
        $this->assertEquals(1, 1);
    }
}

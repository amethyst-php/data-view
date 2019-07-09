<?php

namespace Amethyst\Tests\Commands;

use Amethyst\Tests\BaseTest;

class DataViewSeedTest extends BaseTest
{
    public function testSeed()
    {
        $this->artisan('amethyst:data-view:seed');
        $this->assertEquals(1, 1);
    }
}

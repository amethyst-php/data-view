<?php

namespace Amethyst\Tests\Managers;

use Amethyst\Fakers\DataViewFaker;
use Amethyst\Managers\DataViewManager;
use Amethyst\Tests\BaseTest;
use Railken\Lem\Support\Testing\TestableBaseTrait;

class DataViewTest extends BaseTest
{
    use TestableBaseTrait;

    /**
     * Manager class.
     *
     * @var string
     */
    protected $manager = DataViewManager::class;

    /**
     * Faker class.
     *
     * @var string
     */
    protected $faker = DataViewFaker::class;
}

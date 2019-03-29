<?php

namespace Railken\Amethyst\Tests\Managers;

use Railken\Amethyst\Fakers\DataViewFaker;
use Railken\Amethyst\Managers\DataViewManager;
use Railken\Amethyst\Tests\BaseTest;
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

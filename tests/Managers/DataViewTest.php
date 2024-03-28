<?php

namespace Amethyst\Tests\Managers;

use Amethyst\Fakers\DataViewFaker;
use Amethyst\Managers\DataViewManager;
use Amethyst\Tests\BaseTestCase;
use Railken\Lem\Support\Testing\TestableBaseTrait;

class DataViewTest extends BaseTestCase
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

<?php

namespace Railken\Amethyst\Fakers;

use Faker\Factory;
use Railken\Bag;
use Railken\Lem\Faker;

class DataViewFaker extends Faker
{
    /**
     * @return \Railken\Bag
     */
    public function parameters()
    {
        $faker = Factory::create();

        $bag = new Bag();
        $bag->set('name', $faker->name);
        $bag->set('type', 'component');
        $bag->set('description', $faker->text);
        $bag->set('config', 'foo');
        $bag->set('enabled', 1);

        return $bag;
    }
}

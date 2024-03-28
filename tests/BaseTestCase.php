<?php

namespace Amethyst\Tests;

abstract class BaseTestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Setup the test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('vendor:publish', [
            '--provider' => 'Spatie\MediaLibrary\MediaLibraryServiceProvider',
            '--force'    => true,
        ]);
        $this->artisan('migrate:fresh');
        $this->artisan('vendor:publish --tag=assets');
        app('eloquent.mapper')->boot();
    }

    protected function getPackageProviders($app)
    {
        return [
            \Amethyst\Providers\DataViewServiceProvider::class,
            \Amethyst\Providers\FooServiceProvider::class,
        ];
    }
}

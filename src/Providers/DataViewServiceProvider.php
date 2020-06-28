<?php

namespace Amethyst\Providers;

use Amethyst\Common\CommonServiceProvider;
use Amethyst\Console\Commands\DataViewSeedCommand;

class DataViewServiceProvider extends CommonServiceProvider
{
    /**
     * @inherit
     */
    public function register()
    {
        parent::register();

        $this->app->register(\Railken\Template\TemplateServiceProvider::class);
        $this->app->register(\Amethyst\Providers\PermissionServiceProvider::class);
    }

    /**
     * @inherit
     */
    public function boot()
    {
        $this->commands([DataViewSeedCommand::class]);

        parent::boot();
    }
}

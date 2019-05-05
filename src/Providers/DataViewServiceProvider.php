<?php

namespace Railken\Amethyst\Providers;

use Railken\Amethyst\Common\CommonServiceProvider;
use Railken\Amethyst\Console\Commands\DataViewSeedCommand;
use Railken\Amethyst\Models\ModelHasPermission;
use Railken\Amethyst\Observers\DataViewPermissionObserver;

class DataViewServiceProvider extends CommonServiceProvider
{
    /**
     * @inherit
     */
    public function register()
    {
        parent::register();

        $this->app->register(\Railken\Template\TemplateServiceProvider::class);
        $this->app->register(\Railken\Amethyst\Providers\PermissionServiceProvider::class);
    }

    /**
     * @inherit
     */
    public function boot()
    {
        $this->commands([DataViewSeedCommand::class]);

        parent::boot();

        ModelHasPermission::observe(DataViewPermissionObserver::class);
    }
}

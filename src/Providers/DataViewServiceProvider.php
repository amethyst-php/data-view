<?php

namespace Amethyst\Providers;

use Amethyst\Common\CommonServiceProvider;
use Amethyst\Console\Commands\DataViewSeedCommand;
use Amethyst\Models\ModelHasPermission;
use Amethyst\Observers\DataViewPermissionObserver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;

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

        // ModelHasPermission::observe(DataViewPermissionObserver::class);

        Event::listen(\Railken\EloquentMapper\Events\EloquentMapUpdate::class, function ($event) {
            Artisan::call('amethyst:data-view:seed', [
                'data' => $event->model,
            ]);
            \Spatie\ResponseCache\Facades\ResponseCache::clear();
        });
    }
}

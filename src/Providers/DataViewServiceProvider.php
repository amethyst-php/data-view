<?php

namespace Amethyst\Providers;

use Amethyst\Console\Commands\DataViewSeedCommand;
use Amethyst\Core\Providers\CommonServiceProvider;
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
        $this->app->register(\Amethyst\Providers\FileServiceProvider::class);

        $this->app->singleton('amethyst.data-view', function ($app) {
            return new \Amethyst\Services\DataViewService();
        });
    }

    /**
     * @inherit
     */
    public function boot()
    {
        $this->commands([DataViewSeedCommand::class]);

        parent::boot();

        Event::listen(\Railken\EloquentMapper\Events\EloquentMapUpdate::class, function ($event) {

            $data = app('amethyst')->get($event->model);

            app('amethyst.data-view')->create($data);

            event(new \Amethyst\Events\DataViewDataGenerated($event->model));
            event(new \Amethyst\Events\DataViewOperationCompleted());

            \Spatie\ResponseCache\Facades\ResponseCache::clear();
        });
    }
}

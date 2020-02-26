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

        /*Event::listen(\Railken\EloquentMapper\Events\EloquentMapUpdate::class, function ($event) {
            Artisan::call('amethyst:data-view:seed', [
                'data' => $event->model,
            ]);
            \Spatie\ResponseCache\Facades\ResponseCache::clear();
        });*/
    }
}

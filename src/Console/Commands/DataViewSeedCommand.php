<?php

namespace Amethyst\Console\Commands;

use Illuminate\Console\Command;

class DataViewSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amethyst:data-view:seed {data?}';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $data = !empty($this->argument('data'))
            ? collect([$this->argument('data') => app('amethyst')->findManagerByName($this->argument('data'))])
            : app('amethyst')->getData();

        $bar = $this->output->createProgressBar($data->count());

        $this->info("Generating data-views...\n");

        $bar->start();

        $data->map(function ($manager, $key) use ($bar) {
            app('amethyst.data-view')->create($manager);

            $bar->advance();

            event(new \Amethyst\Events\DataViewDataGenerated($key));
        });

        $bar->finish();

        event(new \Amethyst\Events\DataViewOperationCompleted());
    }
}

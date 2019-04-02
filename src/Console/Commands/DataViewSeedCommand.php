<?php

namespace Railken\Amethyst\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Railken\Amethyst\Managers\DataViewManager;
use Railken\Template\Generators\TextGenerator;
use Doctrine\Common\Inflector\Inflector;

class DataViewSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amethyst:data-view:seed';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $helper = new \Railken\Amethyst\Common\Helper();

        return $helper->getData()->map(function ($data) use ($helper) {
            $name = $helper->getNameDataByModel(Arr::get($data, 'model'));
            $attributes = app(Arr::get($data, 'manager'))->getAttributes()->map(function ($attribute) {
                return [
                    'name' => $attribute->getName(),
                    'type' => preg_replace('/Attribute$/', '', (new \ReflectionClass($attribute))->getShortName()),
                ];
            });

            $this->generate($name, $attributes, 'component');
            $this->generate($name, $attributes, 'routes');
            $this->generate($name, $attributes, 'service');
        });
    }

    public function generate(string $name, $attributes, string $type)
    {
        $manager = new DataViewManager();
        $generator = new TextGenerator();
        $inflector = new Inflector();
        
        foreach (glob(__DIR__."/../../../resources/stubs/{$type}/*") as $filename) {
            $configuration = $generator->generateAndRender(file_get_contents($filename), [
                'name'       => $name,
                'api'        => $inflector->pluralize($name),
                'attributes' => $attributes,
            ]);

            $fullname = $name.'.'.basename($filename, '.yml');

            $view = $manager->findOrCreateOrFail(['name' => $fullname, 'type' => $type])->getResource();
            $manager->updateOrFail($view, ['config' => $configuration]);
        }
    }
}

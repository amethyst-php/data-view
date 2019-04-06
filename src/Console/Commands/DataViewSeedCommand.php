<?php

namespace Railken\Amethyst\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Railken\Amethyst\Managers\DataViewManager;
use Railken\Template\Generators\TextGenerator;
use Doctrine\Common\Inflector\Inflector;
use Railken\Amethyst\Common\Helper;

class DataViewSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amethyst:data-view:seed';

    /**
     * @var \Railken\Amethyst\Common\Helper
     */
    protected $helper;

    /**
     * Create a new instance
     */
    public function __construct()
    {
        $this->helper = new Helper();

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        return $this->helper->getData()->map(function ($data) {
            $this->generate($data, 'component');
            $this->generate($data, 'routes');
            $this->generate($data, 'service');
        });
    }

    public function generate($data, string $type)
    {
        $name = $this->helper->getNameDataByModel(Arr::get($data, 'model'));

        $attributes = $this->serializeAttributes(app(Arr::get($data, 'manager'))->getAttributes());
        $fillableAttributes = $this->serializeAttributes(app(Arr::get($data, 'manager'))->getAttributes()->filter(function ($attribute) {
            return $attribute->getFillable();
        }));

        $manager = new DataViewManager();
        $generator = new TextGenerator();
        $inflector = new Inflector();
        
        foreach (glob(__DIR__."/../../../resources/stubs/{$type}/*") as $filename) {
            $configuration = $generator->generateAndRender(file_get_contents($filename), [
                'name'       => $name,
                'api'        => "/admin/".$inflector->pluralize($name),
                'attributes' => $attributes,
                'fillableAttributes' => $fillableAttributes
            ]);

            $fullname = $name.'.'.basename($filename, '.yml');

            $view = $manager->findOrCreateOrFail(['name' => $fullname, 'type' => $type])->getResource();
            $manager->updateOrFail($view, ['config' => $configuration]);
        }
    }

    public function serializeAttributes($attributes)
    {
        return $attributes->map(function ($attribute) {
            return [
                'name' => $attribute->getName(),
                'type' => preg_replace('/Attribute$/', '', (new \ReflectionClass($attribute))->getShortName()),
            ];
        });
    }
}

<?php

namespace Amethyst\Console\Commands;

use Amethyst\Managers\DataViewManager;
use Doctrine\Common\Inflector\Inflector;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Railken\Lem\Attributes;
use Railken\Template\Generators\TextGenerator;
use Railken\Lem\Contracts\ManagerContract;
use Amethyst\Models\DataView;
use Amethyst\Helpers\DataViewHelper;
use Symfony\Component\Yaml\Yaml;

class DataViewSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amethyst:data-view:seed {data?}';

    /**
     * @var DataViewManager
     */
    protected $dataViewManager;

    /**
     * @var TextGenerator
     */
    protected $generator;

    /**
     * @var DataViewHelper
     */
    protected $helper;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->dataViewManager = new DataViewManager();
        $this->generator = new TextGenerator();
        $this->helper = new DataViewHelper();

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $data = !empty($this->argument('data'))
            ? collect([$this->argument('data') => app('amethyst')->findDataByName($this->argument('data'))])
            : app('amethyst')->getData();

        $bar = $this->output->createProgressBar($data->count());

        $this->info('Generating data-views...');
        $this->info('');

        $bar->start();

        $generator = new TextGenerator();

        $componentFiles = collect(glob($this->getPath('component/*')))->mapWithKeys(function ($file) use ($generator) {
            return [$file => $generator->generateViewFile(file_get_contents($file))];
        });

        $routesFiles = collect(glob($this->getPath('routes/*')))->mapWithKeys(function ($file) use ($generator) {
            return [$file => $generator->generateViewFile(file_get_contents($file))];
        });

        $serviceFiles = collect(glob($this->getPath('service/*')))->mapWithKeys(function ($file) use ($generator) {
            return [$file => $generator->generateViewFile(file_get_contents($file))];
        });

        $data->map(function ($data) use ($bar, $componentFiles, $routesFiles, $serviceFiles) {
            $name = app('amethyst')->getNameDataByModel(Arr::get($data, 'model'));
            $manager = app(Arr::get($data, 'manager'));

            $this->generate($name, $manager, $data, 'component', $componentFiles);
            $this->generate($name, $manager, $data, 'routes', $routesFiles);
            $this->generate($name, $manager, $data, 'service', $serviceFiles);
            $this->generateChildren($name, $manager, $data);

            $bar->advance();

            event(new \Amethyst\Events\DataViewDataGenerated($name));
        });

        $bar->finish();

        event(new \Amethyst\Events\DataViewOperationCompleted());
        $this->info('');
        $this->info('');
        $this->info('Done!');
    }

    public function getPath(string $path)
    {
        return __DIR__.'/../../../resources/stubs/'.$path;
    }

    public function generateChildren(string $name, ManagerContract $manager, $data)
    {
        $dataViews = $this->dataViewManager->getRepository()->newQuery()->whereIn('name', [
            sprintf("%s.page.index", $name),
            sprintf("%s.page.show", $name),
            sprintf("%s.resource.index", $name),
            sprintf("%s.resource.upsert", $name),
            sprintf("%s.resource.show", $name),
        ])->get();

        foreach ($dataViews as $dataView) {

            if ($dataView->name === sprintf("%s.page.show", $name)) {

                // HasMany/MorphMany/HasOne/MorphOne
                $relations = $this->helper->getRelationsByClassModel(Arr::get($data, 'model'))->filter(function ($relation) {
                    return in_array($relation, ['HasMany', 'MorphMany', 'HasOne', 'MorphOne']);
                });

                $this->generateComponents($dataView, $name, $this->parseRelations($relations), 'tab');

                $this->generateComponents($dataView, $name, [[
                    'name' => 'main',
                    'extends' => sprintf("%s.resource.show", $name)
                ]]);
            }

            if ($dataView->name === sprintf("%s.page.index", $name)) {
                $this->generateComponents($dataView, $name, [[
                    'name' => 'main',
                    'extends' => sprintf("%s.resource.index", $name)
                ]]);
            }

            if (in_array($dataView->name, [
                sprintf("%s.resource.upsert", $name)
            ])) {

                $relations = $this->helper->getRelationsByClassModel(Arr::get($data, 'model'))->filter(function ($relation) {
                    return in_array($relation, ['MorphToMany', 'BelongsToMany']);
                });

                $this->generateComponents(null, $name, $this->parseRelations($relations), 'relation');
                
                $this->generateAttributesWithHelper($name, $manager->getAttributes());
            }

            if (in_array($dataView->name, [
                sprintf("%s.resource.index", $name),
                sprintf("%s.resource.upsert", $name),
                sprintf("%s.resource.show", $name)
            ])) {

                $relations = $this->helper->getRelationsByClassModel(Arr::get($data, 'model'))->filter(function ($relation) {
                    return in_array($relation, ['MorphToMany', 'BelongsToMany']);
                });

                $attributes = $manager->getAttributes();


                if ($dataView->name === sprintf("%s.resource.upsert", $name)) {
                    $attributes = $attributes->filter(function ($attribute) {
                        return $attribute->getFillable();
                    });
                }

                if ($dataView->name === sprintf("%s.resource.show", $name) || $dataView->name === sprintf("%s.resource.index", $name)) {
                    $attributes = $attributes->filter(function ($attribute) {
                        return !$attribute->getHidden();
                    });
                }

                $components = $relations->merge($attributes)->map(function ($component) use ($name) {
                    return [
                        'name' => $component->getName(), 
                        'include' => $name.".".$component->getName(),
                        'require' => $name.".".$component->getName()
                    ];
                });


                $this->generateComponents($dataView, $name, $components, 'generic');

            }
        }

        $components = $this->helper
        ->getRelationsByClassModel(Arr::get($data, 'model'))
        ->map(function ($i) {
            return $i['name'];
        })->merge($manager->getAttributes()->map(function($i) {
            return $i->getName();
        }));

        $this->dataViewManager->getRepository()->getQuery()->whereNotNull('require')->whereNotIn('require', $components->map(function ($i) use ($name) {
            return $name.".".$i;
        }))->where('tag', $name)->delete();
    }

    public function generateAttributesWithHelper(string $name, iterable $attributes)
    {
        $components = $this->helper->serializeAttributes($attributes);

        foreach ($components as $component) {
            
            $view = $this->dataViewManager->findOrCreateOrFail([
                'name' => sprintf("%s.%s", $name, $component['name']),
                'type' => 'component',
                'require' => $name.".".$component['name'],
                'tag'  => $name,
            ])->getResource();

            $this->dataViewManager->updateOrFail($view, ['config' => Yaml::dump($component,10)]);
        }
    }

    public function generateComponents(DataView $parent = null, string $name, iterable $components = [], string $path = 'generic')
    {
        foreach ($components as $component) {
            $configuration = $this->generator->render($this->getPath('attribute/'.$path.'.yml'), [
                'name' => $name,
                'component' => $component
            ]);

            $view = $this->dataViewManager->findOrCreateOrFail([
                'name' => sprintf("%s.%s", $parent ? $parent->name : $name, $component['name']),
                'type' => 'component',
                'tag'  => $name,
                'require' => $component['require'] ?? null,
                'parent_id' => $parent ? $parent->id : null
            ])->getResource();

            $this->dataViewManager->updateOrFail($view, ['config' => $this->cleanYaml($configuration)]);
        }
    }

    public function cleanYaml(string $configuration)
    {
        return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $configuration);
    }

    public function generate(string $name, ManagerContract $manager, $data, string $type, $files)
    {
        $inflector = new Inflector();
        $api = '/admin/'.$inflector->pluralize($name);

        foreach ($files as $key => $filename) {

            $configuration = $this->generator->render($filename, [
                'name'       => $name,
                'api'        => $api
            ]);

            $fullname = $name.'.'.basename($key, '.yml');

            $view = $this->dataViewManager->findOrCreateOrFail([
                'name' => $fullname,
                'type' => $type,
                'tag'  => $name,
            ])->getResource();

            $this->dataViewManager->updateOrFail($view, ['config' => $this->cleanYaml($configuration)]);

            event(new \Amethyst\Events\DataViewDataUpdated($view));
        }
    }

    public function parseRelations($relations)
    {
        foreach ($relations as $k => $relation) {
            $relations[$k] = $this->parseRelation($relation);
        }

        return $relations;
    }

    public function parseRelation($relation)
    {
        $relation['scope'] = app('amethyst')->parseScope($relation['model'], $relation['scope']);

        if (app('amethyst')->findDataByName($relation['data'])) {
            $class = app('amethyst')->findManagerByName($relation['data']);
            $relation['manager'] = new $class();
        }

        return $relation;
    }
}

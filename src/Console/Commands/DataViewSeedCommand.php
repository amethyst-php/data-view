<?php

namespace Railken\Amethyst\Console\Commands;

use Doctrine\Common\Inflector\Inflector;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Railken\Amethyst\Managers\DataViewManager;
use Railken\EloquentMapper\Mapper;
use Railken\Lem\Attributes;
use Railken\Template\Generators\TextGenerator;

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
        $data = app('amethyst')->getData();

        $bar = $this->output->createProgressBar($data->count());

        $this->info('Generating data-views...');
        $this->info('');

        $bar->start();

        $generator = new TextGenerator();

        $componentFiles = collect(glob(__DIR__.'/../../../resources/stubs/component/*'))->mapWithKeys(function ($file) use ($generator) {
            return [$file => $generator->generateViewFile(file_get_contents($file))];
        });

        $routesFiles = collect(glob(__DIR__.'/../../../resources/stubs/routes/*'))->mapWithKeys(function ($file) use ($generator) {
            return [$file => $generator->generateViewFile(file_get_contents($file))];
        });

        $serviceFiles = collect(glob(__DIR__.'/../../../resources/stubs/service/*'))->mapWithKeys(function ($file) use ($generator) {
            return [$file => $generator->generateViewFile(file_get_contents($file))];
        });

        $data->map(function ($data) use ($bar, $componentFiles, $routesFiles, $serviceFiles) {
            $name = app('amethyst')->getNameDataByModel(Arr::get($data, 'model'));
            $manager = app(Arr::get($data, 'manager'));
            $attributes = $this->serializeAttributes($manager->getAttributes());
            $relations = $this->parseRelations($this->getRelationsByClassModel(Arr::get($data, 'model')));

            $this->generate($name, $manager, $data, 'component', $attributes, $relations, $componentFiles);
            $this->generate($name, $manager, $data, 'routes', $attributes, $relations, $routesFiles);
            $this->generate($name, $manager, $data, 'service', $attributes, $relations, $serviceFiles);
            $bar->advance();

            event(new \Railken\Amethyst\Events\DataViewDataGenerated($name));
        });

        $bar->finish();

        event(new \Railken\Amethyst\Events\DataViewOperationCompleted());
        $this->info('');
        $this->info('');
        $this->info('Done!');
    }

    public function generate($name, $manager, $data, string $type, $attributes, $relations, $files)
    {
        $dataViewManager = new DataViewManager();
        $generator = new TextGenerator();
        $inflector = new Inflector();
        $api = '/admin/'.$inflector->pluralize($name);

        foreach ($files as $key => $filename) {
            $configuration = $generator->render($filename, [
                'name'       => $name,
                'api'        => $api,
                'attributes' => $attributes,
                'relations'  => $relations,
                'actions'    => Arr::get($manager->getDescriptor(), 'actions'),
                'components' => Arr::get($manager->getDescriptor(), 'components'),
            ]);

            $configuration = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $configuration);

            $fullname = str_replace('.', '-', $name.'.'.basename($key, '.yml'));

            $view = $dataViewManager->findOrCreateOrFail([
                'name' => $fullname,
                'type' => $type,
            ])->getResource();

            $dataViewManager->updateOrFail($view, ['config' => $configuration]);
        }
    }

    public function getRelationsByClassModel(string $classModel)
    {
        return Collection::make(Mapper::relations($classModel))->map(function ($relation, $key) {
            return array_merge($relation->toArray(), [
                'key'  => $key,
                'data' => app('amethyst')->getNameDataByModel($relation->model),
            ]);
        });
    }

    public function getRelationByKeyName(string $classModel, string $keyName)
    {
        return $this->getRelationsByClassModel($classModel)->filter(function ($item) use ($keyName) {
            return $item['key'] === $keyName;
        })->first();
    }

    public function serializeAttributes($attributes)
    {
        return $attributes->map(function ($attribute) {
            $options = [];

            if ($attribute instanceof Attributes\BelongsToAttribute || $attribute instanceof Attributes\MorphToAttribute) {
                $options['data'] = $this->getRelationByKeyName($attribute->getManager()->getEntity(), $attribute->getRelationName())['data'];
            }

            if ($attribute instanceof Attributes\MorphToAttribute) {
                $options['relationTypes'] = $attribute->getManager()->getAttributes()->filter(function ($attr) use ($attribute) {
                    return $attr->getName() === $attribute->getRelationKey();
                })->first()->getOptions();
            }

            return [
                'name'     => $attribute->getName(),
                'type'     => $attribute->getType(),
                'fillable' => $attribute->getFillable(),
                'required' => $attribute->getRequired(),
                'options'  => $options,
            ];
        });
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

        return $relation;
    }
}

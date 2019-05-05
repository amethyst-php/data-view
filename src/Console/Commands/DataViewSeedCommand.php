<?php

namespace Railken\Amethyst\Console\Commands;

use Doctrine\Common\Inflector\Inflector;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Railken\Amethyst\Common\Helper;
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
     * @var \Railken\Amethyst\Common\Helper
     */
    protected $helper;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->helper = new Helper();

        parent::__construct();
    }

    /**con ubuntu poi è peggio, il rischio di corruzione files è
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
        $manager = new DataViewManager();
        $generator = new TextGenerator();
        $inflector = new Inflector();

        foreach (glob(__DIR__."/../../../resources/stubs/{$type}/*") as $filename) {
            $configuration = $generator->generateAndRender(file_get_contents($filename), [
                'name'       => $name,
                'api'        => '/admin/'.$inflector->pluralize($name),
                'attributes' => $attributes,
                'relations'  => $this->getRelationsByClassModel(Arr::get($data, 'model')),
            ]);

            $configuration = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $configuration);

            $fullname = str_replace('.', '-', $name.'.'.basename($filename, '.yml'));

            $view = $manager->findOrCreateOrFail([
                'name' => $fullname,
                'type' => $type,
            ])->getResource();

            $manager->updateOrFail($view, ['config' => $configuration]);
        }
    }

    public function getRelationsByClassModel(string $classModel)
    {
        return Collection::make(Mapper::relations($classModel))->map(function ($relation, $key) {
            return array_merge($relation->toArray(), [
                'key'  => $key,
                'data' => $this->helper->getNameDataByModel($relation->model),
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
}

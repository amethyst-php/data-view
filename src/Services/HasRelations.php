<?php

namespace Amethyst\Services;

use Amethyst\DataSchema\Manager;
use Illuminate\Database\Eloquent\Model;
use Railken\Bag;
use Railken\EloquentMapper\Contracts\Map as MapContract;
use Railken\Lem\Contracts\ManagerContract;
use Symfony\Component\Yaml\Yaml;

trait HasRelations
{
    /**
     * Create a new relation and attach it to all views.
     *
     * @param ManagerContract $manager
     * @param Bag             $relation
     */
    public function createRelation(ManagerContract $manager, Bag $relation)
    {
        $name = $manager->getName();

        $nameRelation = $relation->name;
        $enclosed = $this->enclose($name, $nameRelation);

        // Generate a single view-attribute
        $view = $this->dataViewManager->findOrCreateOrFail([
            'name'    => $this->enclose($name).'.'.$enclosed,
            'type'    => 'component',
            'require' => $name.'.'.$nameRelation,
            'tag'     => $name,
        ])->getResource();

        $this->dataViewManager->updateOrFail($view, ['config' => Yaml::dump($this->serializeRelation($name, $relation->toArray()), 10)]);

        $configuration = [
            'name'    => $enclosed,
            'include' => $this->enclose($name).'.'.$enclosed,
        ];

        foreach ($this->getAllMainViewsByData($name, ['resource.index', 'resource.upsert', 'resource.show']) as $dataView) {
            $view = $this->dataViewManager->findOrCreateOrFail([
                'name'      => sprintf('%s.%s', $dataView->name, $enclosed),
                'type'      => 'component',
                'tag'       => $name,
                'require'   => $name.'.'.$nameRelation,
                'parent_id' => $dataView->id,
            ])->getResource();

            $this->dataViewManager->updateOrFail($view, ['config' => Yaml::dump($configuration)]);
        }
    }

    /**
     * Create a new relation and attach it to all views.
     *
     * @param string $manager
     * @param string $nameRelation
     */
    public function createRelationByName(string $name, string $nameRelation)
    {
        $manager = $this->getManagerByName($name);

        $relation = app('eloquent.mapper')->retrieveRelationByModel($manager->newEntity(), $nameRelation);

        $this->createRelation($manager, $relation);
    }

    /**
     * Remove arelation and attach it to all views.
     *
     * @param string $data
     * @param string $nameRelation
     */
    public function removeRelationByName(string $name, string $nameRelation)
    {
        // ...
    }

    /**
     * Rename a relation.
     *
     * @param string $data
     * @param string $oldNmeRelation
     * @param string $newNameRelation
     */
    public function renameRelationByName(string $name, string $oldNameRelation, string $newNameRelation)
    {
        $manager = $this->getManagerByName($name);

        $map = app(MapContract::class);

        $relation = app('eloquent.mapper')->findRelationByKey($map->relations($manager->newEntity()), $nameRelation);

        foreach ($this->dataViewManager->getRepository()->findAll() as $view) {

            $tag = $this->renameNameComponent($view->tag, $name, $oldNameRelation, $newNameRelation, '');

            if (!empty($tag)) {
                $this->dataViewManager->updateOrFail($view, [
                    'name'    => $this->renameNameComponent($view->name, $name, $oldNameRelation, $newNameRelation),
                    'tag'     => $tag,
                    'require' => $this->renameNameComponent($view->require, $name, $oldNameRelation, $newNameRelation, ''),
                    'config'  => $this->renameNameComponent($view->config, $name, $oldNameRelation, $newNameRelation),
                ]);
            }
        }
    }

    /**
     * Create all relations given manager.
     *
     * @param ManagerContract $manager
     */
    public function createRelations(ManagerContract $manager)
    {
        $dataViews = $this->getAllMainViewsByData($manager->getName());

        $map = app(MapContract::class);

        foreach ($map->relations($manager->newEntity()) as $key => $relation) {
            $this->createRelation($manager, $relation);
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
            $relation['manager'] = app('amethyst')->findManagerByName($relation['data']);
        }

        return $relation;
    }

    public function generateRelationsWithHelper(string $name, Model $model)
    {
        foreach ($components as $component) {
            $view = $this->dataViewManager->findOrCreateOrFail([
                'name'    => sprintf('%s.%s', $name, $component['name']),
                'type'    => 'component',
                'require' => $name.'.'.$component['name'],
                'tag'     => $name,
            ])->getResource();

            $this->dataViewManager->updateOrFail($view, ['config' => Yaml::dump($component, 10)]);
        }
    }
}

<?php

namespace Amethyst\Services;

use Railken\Lem\Contracts\ManagerContract;
use Symfony\Component\Yaml\Yaml;

trait HasResources
{
    /**
     * Generate resources by manager.
     *
     * @param ManagerContract $manager
     */
    public function createResources(ManagerContract $manager)
    {
        $name = $manager->getName();

        $enclosed = $this->enclose($name);
        $api = config('amethyst.api.http.data.router.prefix');

        $configuration = [
            'label' => $enclosed,
            'icon' => "/assets/amethyst/{$enclosed}-icon.svg",
            'options' => [
                'data' => $enclosed,
                'api' => $api.'/'.$enclosed
            ]
        ];

        foreach (['data-iterator-table', 'resource-show', 'resource-upsert', 'resource-delete'] as $resource) {

            $view = $this->dataViewManager->findOrCreateOrFail([
                'name'    => $this->enclose($name).".".str_replace("-", ".", $resource),
                'type'    => 'component',
                'tag'     => $name,
                'require' => $name,
            ])->getResource();

            $iConfiguration = $configuration;

            $iConfiguration['extends'] = $resource;

            if ($resource === 'data-iterator-table') {

                $this->dataViewManager->findOrCreateOrFail([
                    'name'    => $this->enclose($name).".data.iterator.table.create",
                    'type'    => 'component-action',
                    'tag'     => $name,
                    'require' => $name,
                    'config' => Yaml::dump([
                        'extends' => "{$enclosed}-resource-upsert",
                        'type' => 'global'
                    ]),
                    'parent_id' => $view->id
                ])->getResource();

                $this->dataViewManager->findOrCreateOrFail([
                    'name'    => $this->enclose($name).".data.iterator.table.edit",
                    'type'    => 'component-action',
                    'tag'     => $name,
                    'require' => $name,
                    'config' => Yaml::dump([
                        'extends' => "{$enclosed}-resource-upsert",
                        'type' => 'resource'
                    ]),
                    'parent_id' => $view->id
                ])->getResource();

                $this->dataViewManager->findOrCreateOrFail([
                    'name'    => $this->enclose($name).".data.iterator.table.delete",
                    'type'    => 'component-action',
                    'tag'     => $name,
                    'require' => $name,
                    'config' => Yaml::dump([
                        'extends' => "{$enclosed}-resource-delete",
                        'type' => 'resource'
                    ]),
                    'parent_id' => $view->id
                ])->getResource();
            }

            if ($resource === 'resource-show') {
                $this->dataViewManager->findOrCreateOrFail([
                    'name'    => $this->enclose($name).".resource.show.edit",
                    'type'    => 'component-action',
                    'tag'     => $name,
                    'require' => $name,
                    'config' => Yaml::dump([
                        'extends' => "{$enclosed}-resource-upsert",
                        'type' => 'resource'
                    ]),
                    'parent_id' => $view->id
                ])->getResource();

                $this->dataViewManager->findOrCreateOrFail([
                    'name'    => $this->enclose($name).".resource.show.delete",
                    'type'    => 'component-action',
                    'tag'     => $name,
                    'require' => $name,
                    'config' => Yaml::dump([
                        'extends' => "{$enclosed}-resource-delete",
                        'type' => 'resource'
                    ]),
                    'parent_id' => $view->id
                ])->getResource();
            }

            $this->dataViewManager->updateOrFail($view, ['config' => Yaml::dump($iConfiguration)]);
        }
    }
}

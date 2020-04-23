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
            'label'   => $enclosed,
            'icon'    => $this->retrieveFile($name),
            'options' => [
                'data' => $enclosed,
                'api'  => $api.'/'.$enclosed,
            ],
        ];

        foreach (['data-iterator-table', 'resource-show', 'resource-upsert', 'resource-delete'] as $resource) {
            $view = $this->dataViewManager->findOrCreateOrFail([
                'name'    => $this->enclose($name).'.'.str_replace('-', '.', $resource),
                'type'    => 'component',
                'tag'     => $name,
                'require' => $name,
            ])->getResource();

            $iConfiguration = $configuration;

            $iConfiguration['extends'] = $resource;

            if ($resource === 'data-iterator-table') {
                $this->dataViewManager->findOrCreateOrFail([
                    'name'    => $this->enclose($name).'.data.iterator.table.create',
                    'type'    => 'component',
                    'tag'     => $name,
                    'require' => $name,
                    'config'  => Yaml::dump([
                        'label'   => 'upsert',
                        'extends' => "{$enclosed}-resource-upsert",
                        'type'    => 'action',
                        'scope'   => 'global',
                    ]),
                    'parent_id' => $view->id,
                ])->getResource();

                $this->dataViewManager->findOrCreateOrFail([
                    'name'    => $this->enclose($name).'.data.iterator.table.edit',
                    'type'    => 'component',
                    'tag'     => $name,
                    'require' => $name,
                    'config'  => Yaml::dump([
                        'label'   => 'upsert',
                        'extends' => "{$enclosed}-resource-upsert",
                        'type'    => 'action',
                        'scope'   => 'resource',
                    ]),
                    'parent_id' => $view->id,
                ])->getResource();

                $this->dataViewManager->findOrCreateOrFail([
                    'name'    => $this->enclose($name).'.data.iterator.table.delete',
                    'type'    => 'component',
                    'tag'     => $name,
                    'require' => $name,
                    'config'  => Yaml::dump([
                        'label'   => 'delete',
                        'extends' => "{$enclosed}-resource-delete",
                        'type'    => 'action',
                        'scope'   => 'resource',
                    ]),
                    'parent_id' => $view->id,
                ])->getResource();
            }

            if ($resource === 'resource-show') {
                $this->dataViewManager->findOrCreateOrFail([
                    'name'    => $this->enclose($name).'.resource.show.edit',
                    'type'    => 'component',
                    'tag'     => $name,
                    'require' => $name,
                    'config'  => Yaml::dump([
                        'label'   => 'upsert',
                        'extends' => "{$enclosed}-resource-upsert",
                        'type'    => 'action',
                        'scope'   => 'resource',
                    ]),
                    'parent_id' => $view->id,
                ])->getResource();

                $this->dataViewManager->findOrCreateOrFail([
                    'name'    => $this->enclose($name).'.resource.show.delete',
                    'type'    => 'component',
                    'tag'     => $name,
                    'require' => $name,
                    'config'  => Yaml::dump([
                        'label'   => 'delete',
                        'extends' => "{$enclosed}-resource-delete",
                        'type'    => 'action',
                        'scope'   => 'resource',
                    ]),
                    'parent_id' => $view->id,
                ])->getResource();
            }

            $this->dataViewManager->updateOrFail($view, ['config' => Yaml::dump($iConfiguration)]);
        }
    }
}

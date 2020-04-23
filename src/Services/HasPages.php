<?php

namespace Amethyst\Services;

use Railken\Lem\Contracts\ManagerContract;
use Symfony\Component\Yaml\Yaml;

trait HasPages
{
    /**
     * Generate pages by manager.
     *
     * @param ManagerContract $manager
     */
    public function createPages(ManagerContract $manager)
    {
        $name = $manager->getName();

        $enclosed = $this->enclose($name);
        $api = config('amethyst.api.http.data.router.prefix');

        $configuration = [
            'label'   => $enclosed,
            'options' => [
                'data' => $enclosed,
                'api'  => $api.'/'.$enclosed,
            ],
        ];

        foreach (['index', 'show'] as $page) {
            $view = $this->dataViewManager->findOrCreateOrFail([
                'name'    => $this->enclose($name).'.page.'.$page,
                'type'    => 'component',
                'tag'     => $name,
                'require' => $name,
            ])->getResource();

            $configuration['extends'] = 'page-'.$page;

            $this->dataViewManager->updateOrFail($view, ['config' => Yaml::dump($configuration)]);
        }
    }
}

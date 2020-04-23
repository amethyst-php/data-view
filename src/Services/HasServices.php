<?php

namespace Amethyst\Services;

use Railken\Lem\Contracts\ManagerContract;
use Symfony\Component\Yaml\Yaml;

trait HasServices
{
    /**
     * Generate routes by manager.
     *
     * @param ManagerContract $manager
     */
    public function createServices(ManagerContract $manager)
    {
        $name = $manager->getName();

        $enclosed = $this->enclose($name);

        $configuration = [
            'label'   => $enclosed,
            'icon'    => $this->retrieveFile($name),
            'type'    => 'bookmark',
            'options' => [
              'url' => '/'.$enclosed,
            ],
        ];

        $view = $this->dataViewManager->findOrCreateOrFail([
            'name'    => $enclosed.'.bookmark',
            'type'    => 'service',
            'tag'     => $name,
            'require' => $name,
        ])->getResource();

        $this->dataViewManager->updateOrFail($view, ['config' => Yaml::dump($configuration)]);
    }
}

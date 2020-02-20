<?php

namespace Amethyst\Services;

use Railken\Lem\Contracts\ManagerContract;

use Symfony\Component\Yaml\Yaml;

trait HasRoutes
{
    
   /**
    * Generate routes by manager
    *
    * @param ManagerContract $manager
    */
   public function createRoutes(ManagerContract $manager)
   {
        $name = $manager->getName();

        $enclosed = $this->enclose($name);

        $configuration = [
            [
                'path' => $enclosed,
                'name' => $enclosed.".index",
                'component' => $enclosed.".page.index"
            ],
            [
                'path' => $enclosed."/:id",
                'name' => $enclosed.".show",
                'component' => $enclosed.".page.show"
            ],
        ];

        $view = $this->dataViewManager->findOrCreateOrFail([
            'name'    => $this->enclose($name).'.routes',
            'type'    => 'routes',
            'tag'     => $name,
            'require' => $name,
        ])->getResource();

        $this->dataViewManager->updateOrFail($view, ['config' => Yaml::dump($configuration)]);
   }
}

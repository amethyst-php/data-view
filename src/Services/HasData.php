<?php

namespace Amethyst\Services;

use Railken\Lem\Contracts\ManagerContract;
use Railken\Template\Generators\TextGenerator;
use Symfony\Component\Yaml\Yaml;

trait HasData
{
    public function getPath(string $path)
    {
        return __DIR__.'/../../resources/stubs/'.$path;
    }

    /**
     * Create a new data.
     *
     * @param string $name
     */
    public function create(ManagerContract $manager)
    {
        $name = $manager->getName();

        $generator = new TextGenerator();

        $this->createPages($manager);
        $this->createResources($manager);
        $this->generateChildrens($manager);
        $this->createServices($manager);
        $this->createRoutes($manager);
        $this->createAttributes($manager);
        $this->createRelations($manager);
    }

    /**
     * Create a new data.
     *
     * @param string $name
     */
    public function createByName(string $name)
    {
        $this->create($this->getManagerByName($name));
    }

    /**
     * Rename data.
     *
     * @param string $oldName
     * @param string $newName
     */
    public function renameByName(string $oldName, string $newName)
    {
        foreach ($this->dataViewManager->getRepository()->findAll() as $view) {
            $this->dataViewManager->updateOrFail($view, [
                'name'    => $this->renameNameData($view->name, $oldName, $newName),
                'tag'     => $this->renameNameData($view->tag, $oldName, $newName, ''),
                'require' => $this->renameNameData($view->require, $oldName, $newName, ''),
                'config'  => $this->renameNameData($view->config, $oldName, $newName),
            ]);
        }
    }

    /**
     * Remove data.
     *
     * @param string $name
     */
    public function removeByName(string $name)
    {
        $query = $this->dataViewManager->getRepository()->newQuery()->where('require', 'LIKE', $name.'.%')->orWhere('require', 'LIKE', $name)->get()->each->delete();
    }

    public function generateChildrens(ManagerContract $manager)
    {
        $name = $manager->getName();
        $entity = $manager->newEntity();
        $enclosed = $this->enclose($name);

        $dataViews = $this->getAllMainViewsByData($name);

        foreach ($dataViews as $dataView) {
            if ($dataView->name === sprintf('%s.page.show', $enclosed)) {
                $component = sprintf('%s.resource.show', $enclosed);

                $configuration = [
                    'name'    => 'main',
                    'extends' => $component,
                ];

                $view = $this->dataViewManager->findOrCreateOrFail([
                    'name'      => sprintf('%s.%s', $dataView->name, $enclosed),
                    'type'      => 'component',
                    'tag'       => $name,
                    'require'   => $name,
                    'parent_id' => $dataView->id,
                ])->getResource();

                $this->dataViewManager->updateOrFail($view, ['config' => Yaml::dump($configuration)]);
            }

            if ($dataView->name === sprintf('%s.page.index', $enclosed)) {
                $component = sprintf('%s.data.iterator.table', $enclosed);

                $configuration = [
                    'name'    => 'main',
                    'extends' => $component,
                ];

                $view = $this->dataViewManager->findOrCreateOrFail([
                    'name'      => sprintf('%s.%s', $dataView->name, $enclosed),
                    'type'      => 'component',
                    'tag'       => $name,
                    'require'   => $name,
                    'parent_id' => $dataView->id,
                ])->getResource();

                $this->dataViewManager->updateOrFail($view, ['config' => Yaml::dump($configuration)]);
            }
        }
    }
}

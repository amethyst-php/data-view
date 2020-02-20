<?php

namespace Amethyst\Services;

use Railken\Lem\Contracts\ManagerContract;
use Railken\Template\Generators\TextGenerator;

trait HasData
{
    use HasAttributes;
    use HasRelations;
    use HasServices;
    use HasRoutes;

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

        $componentFiles = collect(glob($this->getPath('component/*')))->mapWithKeys(function ($file) use ($generator) {
            return [$file => $generator->generateViewFile(file_get_contents($file))];
        });

        $serviceFiles = collect(glob($this->getPath('service/*')))->mapWithKeys(function ($file) use ($generator) {
            return [$file => $generator->generateViewFile(file_get_contents($file))];
        });

        $this->generate($name, $manager, 'component', $componentFiles);

        $this->generateChildren($name, $manager);

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
    }

    /**
     * Rename data.
     *
     * @param string $oldName
     * @param string $newName
     */
    public function renameByName(string $oldName, string $newName)
    {
        // ...
    }

    /**
     * Remove data.
     *
     * @param string $name
     */
    public function removeByName(string $name)
    {
        // ...
    }

    public function generateChildren(string $name, ManagerContract $manager)
    {
        $entity = $manager->newEntity();
        $enclosed = $this->enclose($name);

        $dataViews = $this->getAllMainViewsByData($name);

        foreach ($dataViews as $dataView) {
            if ($dataView->name === sprintf('%s.page.show', $enclosed)) {
                $this->generateComponents($dataView, $name, [
                    'name'    => 'main',
                    'extends' => sprintf('%s.resource.show', $enclosed),
                ]);
            }

            if ($dataView->name === sprintf('%s.page.index', $name)) {
                $this->generateComponents($dataView, $name, [
                    'name'    => 'main',
                    'extends' => sprintf('%s.resource.index', $enclosed),
                ]);
            }
        }
    }
}

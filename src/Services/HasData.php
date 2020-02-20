<?php

namespace Amethyst\Services;

use Amethyst\Models;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Amethyst\DataSchema\Manager;
use Amethyst\Helpers\DataViewHelper;
use Amethyst\Managers\DataViewManager;
use Amethyst\Models\DataView;
use Doctrine\Common\Inflector\Inflector;
use Illuminate\Support\Arr;
use Railken\Lem\Contracts\ManagerContract;
use Railken\Template\Generators\TextGenerator;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Database\Eloquent\Model;

trait HasData
{
    use HasAttributes;
    use HasRelations;

    public function getPath(string $path)
    {
        return __DIR__.'/../../resources/stubs/'.$path;
    }
    
    /**
     * Create a new data
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

        $routesFiles = collect(glob($this->getPath('routes/*')))->mapWithKeys(function ($file) use ($generator) {
            return [$file => $generator->generateViewFile(file_get_contents($file))];
        });

        $serviceFiles = collect(glob($this->getPath('service/*')))->mapWithKeys(function ($file) use ($generator) {
            return [$file => $generator->generateViewFile(file_get_contents($file))];
        });

        $this->generate($name, $manager, 'component', $componentFiles);
        $this->generate($name, $manager, 'routes', $routesFiles);
        $this->generate($name, $manager, 'service', $serviceFiles);
        
        $this->generateChildren($name, $manager);

        $this->createAttributes($manager);
        $this->createRelations($manager);
    }
    /**
     * Create a new data
     *
     * @param string $name
     */
    public function createByName(string $name)
    {

    }

    /**
     * Rename data
     *
     * @param string $oldName
     * @param string $newName
     */
    public function renameByName(string $oldName, string $newName)
    {
        // ...
    }

    /**
     * Remove data
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

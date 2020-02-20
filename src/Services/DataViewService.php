<?php

namespace Amethyst\Services;

use Amethyst\DataSchema\Manager;
use Amethyst\Managers\DataViewManager;
use Amethyst\Models\DataView;
use Doctrine\Common\Inflector\Inflector;
use Illuminate\Support\Facades\Config;
use Railken\Lem\Contracts\ManagerContract;
use Railken\Template\Generators\TextGenerator;

class DataViewService
{
    use HasData;

    /**
     * @var DataViewManager
     */
    protected $dataViewManager;

    /**
     * @var TextGenerator
     */
    protected $generator;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->dataViewManager = new DataViewManager();
        $this->generator = new TextGenerator();
    }

    /**
     * Get manager by name.
     *
     * @param string $name
     *
     * @return ManagerContract
     */
    public function getManagerByName(string $name): ManagerContract
    {
        return app('amethyst')->get($name);
    }

    /**
     * Retrieve all main views by data.
     *
     * @param string $name
     *
     * @return Collection
     */
    public function getAllMainViewsByData(string $name)
    {
        $enclosed = $this->enclose($name);

        return $this->dataViewManager->getRepository()->newQuery()->whereIn('name', [
            sprintf('%s.page.index', $enclosed),
            sprintf('%s.page.show', $enclosed),
            sprintf('%s.resource.index', $enclosed),
            sprintf('%s.resource.upsert', $enclosed),
            sprintf('%s.resource.show', $enclosed),
        ])->get();
    }

    /**
     * Enclose data and subcomponent in reference variable placeholder.
     *
     * @param string $data
     * @param string $sub
     *
     * @return string
     */
    public function enclose(string $data, string $sub = null): string
    {
        $arr = [$data];

        if ($sub) {
            $arr[] = $sub;
        }

        return '~'.implode('.', $arr).'~';
    }

    public function generateComponents(DataView $parent = null, string $name, $component, string $path = 'generic')
    {
        $configuration = $this->generator->render($this->getPath('attribute/'.$path.'.yml'), [
            'name'      => $this->enclose($name),
            'component' => $component,
        ]);

        $view = $this->dataViewManager->findOrCreateOrFail([
            'name'      => sprintf('%s.%s', $parent ? $parent->name : $name, $name, $component['name']),
            'type'      => 'component',
            'tag'       => $name,
            'require'   => $component['require'] ?? null,
            'parent_id' => $parent ? $parent->id : null,
        ])->getResource();

        $this->dataViewManager->updateOrFail($view, ['config' => $this->cleanYaml($configuration)]);
    }

    public function cleanYaml(string $configuration)
    {
        return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $configuration);
    }

    public function generate(string $name, ManagerContract $manager, string $type, $files)
    {
        $inflector = new Inflector();
        $api = config('amethyst.api.http.data.router.prefix');

        foreach ($files as $key => $filename) {
            $configuration = $this->generator->render($filename, [
                'name' => $this->enclose($name),
                'api'  => $api,
            ]);

            $fullname = $this->enclose($name).'.'.basename($key, '.yml');

            $view = $this->dataViewManager->findOrCreateOrFail([
                'name'    => $fullname,
                'type'    => $type,
                'tag'     => $name,
                'require' => $name,
            ])->getResource();

            $this->dataViewManager->updateOrFail($view, ['config' => $this->cleanYaml($configuration)]);
        }
    }
}

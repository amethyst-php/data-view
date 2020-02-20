<?php

namespace Amethyst\Services;

use Amethyst\DataSchema\Manager;
use Railken\Lem\Attributes\BaseAttribute;
use Railken\Lem\Contracts\ManagerContract;
use Symfony\Component\Yaml\Yaml;
use Railken\Lem\Attributes\BelongsToAttribute;

trait HasAttributes
{
    use HasAttributeSerializer;

    /**
     * Create a new attribute.
     *
     * @param ManagerContract                       $manager
     * @param \Railken\Lem\Attributes\BaseAttribute $attribute
     */
    public function createAttribute(ManagerContract $manager, BaseAttribute $attribute)
    {
        // Skip MorphTo/BelongsTo
        if ($attribute instanceof BelongsToAttribute) {
            return;
        }

        $name = $manager->getName();
        $nameAttribute = $attribute->getName();

        $enclosed = $this->enclose($name, $nameAttribute);

        // Generate a single view-attribute
        $view = $this->dataViewManager->findOrCreateOrFail([
            'name'    => $enclosed,
            'type'    => 'component',
            'require' => $name.'.'.$nameAttribute,
            'tag'     => $name,
        ])->getResource();

        $this->dataViewManager->updateOrFail($view, ['config' => Yaml::dump($this->serializeAttribute($attribute), 10)]);
        
        $configuration = [
            'name'    => "~".$name.".".$nameAttribute."~",
            'include' => "~".$name.'.'.$nameAttribute."~"
        ];


        foreach ($this->getAllMainViewsByData($name) as $dataView) {
            $view = $this->dataViewManager->findOrCreateOrFail([
                'name'      => sprintf('%s.%s', $dataView->name, $enclosed),
                'type'      => 'component',
                'tag'       => $name,
                'require'   => $name.'.'.$nameAttribute,
                'parent_id' => $dataView->id,
            ])->getResource();

            $this->dataViewManager->updateOrFail($view, ['config' => Yaml::dump($configuration)]);
        }
    }

    /**
     * Create an attribute and attach it to all views.
     *
     * @param string $name
     * @param string $nameAttribute
     */
    public function createAttributeByName(string $name, string $nameAttribute)
    {
        $manager = $this->getManagerByName($name);

        $attribute = $manager->getAttributes()->first(function ($attribute) use ($nameAttribute) {
            return $attribute->getName() === $nameAttribute;
        });

        $this->createAttribute($manager, $attribute);
    }

    /**
     * Remove an attribute and attach it to all views.
     *
     * @param string $name
     * @param string $nameAttribute
     */
    public function removeAttributeByName(string $name, string $nameAttribute)
    {
        $manager = $this->getManagerByName($name);

        $attribute = $manager->getAttributes()->first(function ($attribute) use ($nameAttribute) {
            return $attribute->getName() === $nameAttribute;
        });

        // ...
    }

    /**
     * Rename an attribute.
     *
     * @param string $name
     * @param string $oldNmeAttribute
     * @param string $newNameAttribute
     */
    public function renameAttributeByName(string $name, string $oldNameAttribute, string $newNameAttribute)
    {
        $manager = $this->getManagerByName($name);

        $attribute = $manager->getAttributes()->first(function ($attribute) use ($newNameAttribute) {
            return $attribute->getName() === $newNameAttribute;
        });

        // ...
    }

    /**
     * Create attributes from data.
     *
     * @param string $name
     */
    public function createAttributes(ManagerContract $manager)
    {
        foreach ($manager->getAttributes() as $attribute) {
            $this->createAttribute($manager, $attribute);
        }
    }
}

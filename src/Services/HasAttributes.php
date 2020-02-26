<?php

namespace Amethyst\Services;

use Amethyst\DataSchema\Manager;
use Railken\Lem\Attributes\BaseAttribute;
use Railken\Lem\Attributes\BelongsToAttribute;
use Railken\Lem\Contracts\ManagerContract;
use Symfony\Component\Yaml\Yaml;

trait HasAttributes
{
    /**
     * Create a new attribute.
     *
     * @param ManagerContract                       $manager
     * @param \Railken\Lem\Attributes\BaseAttribute $attribute
     * @param bool                                  $related
     */
    public function createAttribute(ManagerContract $manager, BaseAttribute $attribute, bool $related = true)
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
            'name'    => $this->enclose($name).'.'.$enclosed,
            'type'    => 'component',
            'require' => $name.'.'.$nameAttribute,
            'tag'     => $name,
        ])->getResource();

        $this->dataViewManager->updateOrFail($view, ['config' => Yaml::dump($this->serializeAttribute($attribute), 10)]);

        if (!$related) {
            return;
        }

        $configuration = [
            'name'    => $enclosed,
            'include' => $this->enclose($name).'.'.$enclosed,
        ];

        foreach ($this->getAllMainViewsByData($name, ['resource.index', 'resource.upsert', 'resource.show']) as $dataView) {
            if (
                ($dataView->name === sprintf('%s.resource.upsert', $this->enclose($name)) && $attribute->getFillable()) ||
                ($dataView->name === sprintf('%s.resource.index', $this->enclose($name)) && !$attribute->getHidden()) ||
                ($dataView->name === sprintf('%s.resource.show', $this->enclose($name)) && !$attribute->getHidden())
            ) {
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
    }

    /**
     * Create an attribute and attach it to all views.
     *
     * @param string $name
     * @param string $nameAttribute
     * @param bool   $related
     */
    public function createAttributeByName(string $name, string $nameAttribute, bool $related = true)
    {
        $manager = $this->getManagerByName($name);

        $attribute = $manager->getAttributes()->first(function ($attribute) use ($nameAttribute) {
            return $attribute->getName() === $nameAttribute;
        });

        $this->createAttribute($manager, $attribute, $related);
    }

    /**
     * Regenerate an attribute and attach it to all views.
     *
     * @param string $name
     * @param string $nameAttribute
     */
    public function regenerateAttributeByName(string $name, string $nameAttribute)
    {
        return $this->createAttributeByName($name, $nameAttribute, false);
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

        $this->dataViewManager->getRepository()->newQuery()->where([
            'require'   => $name.'.'.$nameAttribute,
        ])->delete();
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

        foreach ($this->dataViewManager->getRepository()->findAll() as $view) {
            $tag = $this->renameNameComponent($view->tag, $name, $oldNameAttribute, $newNameAttribute, '');

            if (!empty($tag)) {
                $this->dataViewManager->updateOrFail($view, [
                    'name'    => $this->renameNameComponent($view->name, $name, $oldNameAttribute, $newNameAttribute),
                    'tag'     => $tag,
                    'require' => $this->renameNameComponent($view->require, $name, $oldNameAttribute, $newNameAttribute, ''),
                    'config'  => $this->renameNameComponent($view->config, $name, $oldNameAttribute, $newNameAttribute),
                ]);
            }
        }
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

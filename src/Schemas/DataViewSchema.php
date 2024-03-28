<?php

namespace Amethyst\Schemas;

use Amethyst\Managers\DataViewManager;
use Railken\Lem\Attributes;
use Railken\Lem\Schema;

class DataViewSchema extends Schema
{
    /**
     * Get all the attributes.
     *
     * @var array
     */
    public function getAttributes()
    {
        return [
            Attributes\IdAttribute::make(),
            Attributes\TextAttribute::make('name')
                ->setRequired(true)
                ->setUnique(true),
            Attributes\TextAttribute::make('slug')
                ->setRequired(true)
                ->setUnique(true)
                ->setDefault(function ($i) {
                    return microtime();
                }),
            Attributes\TextAttribute::make('type')
                ->setRequired(true),
            Attributes\TextAttribute::make('tag'),
            Attributes\TextAttribute::make('require'),
            Attributes\LongTextAttribute::make('description'),
            Attributes\YamlAttribute::make('config'),
            Attributes\BooleanAttribute::make('enabled'),
            Attributes\BelongsToAttribute::make('parent_id')
                ->setRelationName('parent')
                ->setRelationManager(DataViewManager::class),
            Attributes\CreatedAtAttribute::make(),
            Attributes\UpdatedAtAttribute::make(),
        ];
    }
}

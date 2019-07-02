<?php

namespace Railken\Amethyst\Schemas;

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
            Attributes\TextAttribute::make('type')
                ->setRequired(true),
            Attributes\LongTextAttribute::make('description'),
            Attributes\YamlAttribute::make('config'),
            Attributes\BooleanAttribute::make('enabled'),
            Attributes\EnumAttribute::make('authenticable_type', app('amethyst')->getMorphListable('data-view', 'authenticable')),
            Attributes\MorphToAttribute::make('authenticable_id')
                ->setRelationKey('authenticable_type')
                ->setRelationName('authenticable')
                ->setRelations(app('amethyst')->getMorphRelationable('data-view', 'authenticable')),
            Attributes\CreatedAtAttribute::make(),
            Attributes\UpdatedAtAttribute::make(),
            Attributes\DeletedAtAttribute::make(),
        ];
    }
}

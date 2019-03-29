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
            Attributes\TextAttribute::make('permission'),
            Attributes\BooleanAttribute::make('enabled'),
            Attributes\CreatedAtAttribute::make(),
            Attributes\UpdatedAtAttribute::make(),
            Attributes\DeletedAtAttribute::make(),
        ];
    }
}

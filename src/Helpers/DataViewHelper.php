<?php

namespace Amethyst\Helpers;

use Railken\Lem\Attributes;
use Illuminate\Support\Collection;

class DataViewHelper
{

    public function getRelationsByClassModel(string $classModel)
    {
        return collect(\Railken\EloquentMapper\Mapper::relations($classModel))->map(function ($relation, $key) {
            return array_merge($relation->toArray(), [
                'key'  => $key,
                'data' => app('amethyst')->getNameDataByModel($relation->model),
            ]);
        });
    }

    public function getRelationByKeyName(string $classModel, string $keyName)
    {
        return $this->getRelationsByClassModel($classModel)->filter(function ($item) use ($keyName) {
            return $item['key'] === $keyName;
        })->first();
    }

    public function getKeysFromMorphTo(Attributes\MorphToAttribute $attribute) {
        return $attribute->getManager()->getAttributes()->first(function ($attr) use ($attribute) {
            return $attr->getName() === $attribute->getRelationKey();
        })->getOptions();
    }

    public function serializeAttributes(Collection $attributes): Collection
    {
        $result = Collection::make();

        foreach ($attributes as $attribute) {
            $method = sprintf("serialize%sAttribute", $attribute->getType());

            if (!method_exists($this, $method)) {
                $method = "serializeBaseAttribute";
            }

            $result = $result->merge(collect($this->$method($attribute)));
        };

        return $result;
    }

    public function serializeBaseAttribute(Attributes\BaseAttribute $attribute): iterable
    {
        $params = [
            'name' => $attribute->getName(),
            'extends' => 'attribute-input',
            'type' => 'attribute',
            'options' => [
                'size'       => 12,
                'name'       => $attribute->getName(),
                'type'       => $attribute->getType(),
                'fillable'   => (bool) $attribute->getFillable(),
                'required'   => (bool) $attribute->getRequired(),
                'unique'     => (bool) $attribute->getUnique(),
                'hidden'     => (bool) $attribute->getHidden(),
                'default'    => $attribute->getDefault($attribute->getManager()->newEntity()),
                'descriptor' => $attribute->getDescriptor(),
                'extract' => $attribute->getName(),
                'inject' => $attribute->getName(),
                'select' => [
                    'attributes' => [
                        $attribute->getName() => "{{ resource.{$attribute->getName()} }}"
                    ],
                ],
                'readable' => [
                    'type' => 'default',
                    'label' => sprintf("{{ resource.%s }}", $attribute->getName()),
                ],
                'persist' => [
                    'attributes' => [
                        $attribute->getName() => "{{ resource.{$attribute->getName()} }}"
                    ],
                ]
            ],
        ];

        return [$params];
    }

    public function serializeEnumAttribute(Attributes\EnumAttribute $attribute): iterable
    {
        return collect($this->serializeBaseAttribute($attribute))->map(function ($attr) use ($attribute) {
            $attr['options']['items'] = $attribute->getOptions();

            return $attr;
        })->toArray();
    }
    
    public function serializeBelongsToAttribute(Attributes\BelongsToAttribute $attribute): iterable
    {
        $data = $this->getRelationByKeyName($attribute->getManager()->getEntity(), $attribute->getRelationName())['data'];

        $params = [
            'name' => $attribute->getRelationName(),
            'extends' => 'attribute-input',
            'type' => 'attribute',
            'options' => [
                'size' => 12,
                'extract' => $attribute->getRelationName(),
                'inject' => $attribute->getName(),
                'type' => 'autocomplete',
                'select' => [
                    'data' => $data,
                    'query' => sprintf("concat({{ %s | raw}}) ct '{{ __key__ }}", $attribute->getRelationManager()->getPrimaryAttributeNames()->implode('," ",')),
                ],
                'readable' => [
                    'type' => 'default',
                    'label' => sprintf("{{ %s }}", $attribute->getRelationManager()->getPrimaryAttributeNames()->implode(' \}\} - \{\{ ')),
                ],
                'actions' => [
                    'update' => sprintf('%s-resource-upsert', $data),
                ]
            ]
        ];


        return [$params];
    }

    public function serializeMorphToAttribute(Attributes\MorphToAttribute $attribute): iterable
    {
        $data = $this->getRelationByKeyName($attribute->getManager()->getEntity(), $attribute->getRelationName())['data'];

        $return = [];

        foreach ($this->getKeysFromMorphTo($attribute) as $key) {
            $params = [
                'name' => $attribute->getRelationName(),
                'extends' => 'attribute-input',
                'type' => 'attribute',
                'options' => [
                    'size' => 12,
                    'extract' => $attribute->getRelationName(),
                    'inject' => $attribute->getName(),
                    'include' => $data,
                    'type' => 'autocomplete',
                    'condition' => sprintf("%s === {{ resource.%s", $key, $attribute->getRelationKey()),
                    'select' => [
                        'data' => $key,
                        'query' => sprintf("concat({{ %s | raw}}) ct '{{ __key__ }}", $attribute->getRelationManagerByKey($key)->getPrimaryAttributeNames()->implode('," ",')),
                    ],
                    'readable' => [
                        'type' => 'default',
                        'label' => sprintf("{{ %s }}", $attribute->getRelationManagerByKey($key)->getPrimaryAttributeNames()->implode(' \}\} - \{\{ ')),
                    ],
                    'actions' => [
                        'update' => sprintf('%s-resource-upsert', $key),
                    ]
                ]
            ];

            $return[] = $params;
        }

        return $return;
    }
}
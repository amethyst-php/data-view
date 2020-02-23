<?php

namespace Amethyst\Services;

use Illuminate\Database\Eloquent\Model;
use Railken\Lem\Attributes;

trait HasRelationSerializer
{
    public function serializeRelation(string $name, array $relation): array
    {
        $method = sprintf('serialize%s', $relation['type']);

        if (method_exists($this, $method)) {
            return $this->$method($name, $relation);
        }

        return [];
    }

    public function serializeBelongsTo($name, $relation): iterable
    {
        $manager = app('amethyst')->get($name);

        $attribute = $manager->getAttributes()->first(function ($attribute) use ($relation) {
            return $attribute->getName() === $relation['localKey'];
        });

        $data = $attribute->getManager()->newEntity()->getMorphClass();

        $nameComponent = $this->enclose($name, $relation['name']);
        $nameComponentField = $this->enclose($name, $attribute->getName());

        $relatedName = $relation['related'];

        $params = [
            'name'    => $nameComponent,
            'extends' => 'attribute-input',
            'type'    => 'attribute',
            'options' => [
                'name'     => $nameComponent,
                'type'     => 'autocomplete',
                'required' => (bool) $attribute->getRequired(),
                'extract'  => [
                    'attributes' => [
                        $nameComponent => [
                            'path' => $nameComponent,
                        ],
                        /*$attribute->getName() => [
                            'data' => $data,
                            'query' => sprintf('id eq {{ resource.%s }}', $attribute->getName())
                        ]*/
                    ],
                ],
                'readable' => [
                    'type'  => 'default',
                    'label' => $attribute
                        ->getRelationManager()
                        ->getPrimaryAttributeNames()
                        ->map(function ($x) use ($relatedName) {
                            return '{{ value.~'.$relatedName."$x~ }}";
                        })->implode(' '),
                ],
                'include' => [$attribute->getRelationName()],
                'select'  => [
                    'data'  => $data,
                    'query' => sprintf(
                        "concat(%s) ct '{{ __key__ }}'",
                        $attribute
                            ->getRelationManager()
                            ->getPrimaryAttributeNames()
                            ->map(function ($x) use ($attribute, $relatedName) {
                                return '~'.$relatedName.".$x~";
                            })->implode(',')
                    ),
                    'label' => $attribute
                        ->getRelationManager()
                        ->getPrimaryAttributeNames()
                        ->map(function ($x) use ($attribute, $relatedName) {
                            return "{{ ~$relatedName.$x~ }}";
                        })->implode(' - '),
                ],
                'persist' => [
                    'attributes' => [
                        $nameComponentField => [
                            'template' => '{{ value.id }}',
                        ],
                        $nameComponent => [
                            'path' => 'value',
                        ],
                    ],
                ],
                'actions' => [
                    'update' => sprintf('%s-resource-upsert', $data),
                ],
            ],
        ];

        return $params;
    }

    public function serializeMorphTo($name, $relation): iterable
    {
        $manager = app('amethyst')->get($name);

        $attribute = $manager->getAttributes()->first(function ($attribute) use ($relation) {
            return $attribute->getName() === $relation['localKey'];
        });

        $data = $attribute->getManager()->newEntity()->getMorphClass();

        $nameComponent = $this->enclose($name, $relation['name']);
        $nameComponentField = $this->enclose($name, $attribute->getName());

        $enclosedRelationKey = $this->enclose($name, $attribute->getRelationKey());

        $params = [
            'name'    => $nameComponent,
            'extends' => 'attribute-input',
            'type'    => 'attribute',
            'options' => [
                'name'     => $nameComponent,
                'type'     => 'autocomplete',
                'required' => (bool) $attribute->getRequired(),
                'extract'  => [
                    'attributes' => [
                        $nameComponent => [
                            'path' => $nameComponent,
                        ],
                        /*$attribute->getName() => [
                            'data' => $key,
                            'query' => sprintf('id eq {{ resource.%s }}', $attribute->getName())
                        ]*/
                    ],
                ],
                'readable' => [
                    'type'  => 'default',
                    'label' => sprintf('{{ values(value, data(resource.%s).getPrimaryAttributes()|mapByKey("name")).join(",") }}', $enclosedRelationKey),
                ],
                'include'   => [$nameComponent],
                'persist'   => [
                    'attributes' => [
                        $nameComponentField => [
                            'template' => '{{ value.id }}',
                        ],
                        $nameComponent => [
                            'path' => 'value',
                        ],
                    ],
                ],
                'condition' => sprintf('{{ hasData(resource.%s) ? 1 : 0 }}', $enclosedRelationKey),
                'select' => [
                    'data'  => sprintf("{{ resource.%s }}", $enclosedRelationKey),
                    'query' => sprintf(
                        "concat(%s) ct '{{ __key__ }}'",
                        sprintf('{{ data(resource.%s).getPrimaryAttributes()|mapByKey("name").join(",") }}', $enclosedRelationKey)
                    ),
                    'label' => sprintf('{{ values(value, data(resource.%s).getPrimaryAttributes()|mapByKey("name")).join(" ") }}', $enclosedRelationKey),
                ],
                'actions' => [
                    'update' => sprintf('{{ resource.%s }}-resource-upsert', $enclosedRelationKey),
                ],
            ],
        ];


        return $params;
    }

    public function getRelationByKeyName(Model $model, string $keyName)
    {
        return $this->getRelationsByClassModel($model)->filter(function ($item) use ($keyName) {
            return $item['key'] === $keyName;
        })->first();
    }

    public function getKeysFromMorphTo(Attributes\MorphToAttribute $attribute)
    {
        return $attribute->getManager()->getAttributes()->first(function ($attr) use ($attribute) {
            return $attr->getName() === $attribute->getRelationKey();
        })->getOptions();
    }

    public function serializeMorphToMany(string $name, array $relation): iterable
    {
        $relationManager = app('amethyst')->findManagerByName($relation['related']);

        $relatedPivotKey = $relation['relatedPivotKey'];
        $foreignPivotKey = $relation['foreignPivotKey'];

        $fixed = [
            $foreignPivotKey       => '{{ resource.id }}',
            $relation['morphType'] => $relation['morphClass'],
        ];

        $relatedName = $relation['related'];

        foreach ($relation['scope'] as $scope) {
            $column = $scope['column'];
            $columns = explode('.', $column);
            if ((count($columns) > 1) && $columns[0] === $relation['table']) {
                $columns = [$columns[1]];
            }

            $column = implode('.', $columns);

            $fixed[$column] = $scope['value'];
        }


        $nameComponent = $this->enclose($name, $relation['name']);

        $params = [
            'name'    => $nameComponent,
            'extends' => 'attribute-input',
            'type'    => 'attribute',
            'options' => [
                'name'     => $nameComponent,
                'type'     => 'autocomplete',
                'hide'     => false,
                'required' => false,
                'multiple' => true,
                'default'  => [],
                'include'  => [$nameComponent],
                'extract'  => [
                    'attributes' => [
                        $nameComponent => [
                            'path' => $nameComponent,
                        ],
                    ],
                ],
                'readable' => [
                    'type'  => 'default',
                    'label' => $relationManager
                        ->getPrimaryAttributeNames()
                        ->map(function ($x) use ($relatedName) {
                            return "{{ value.~$relatedName.$x~ }}";
                        })->implode(' '),
                ],
                'select' => [
                    'data'  => "~$relatedName~",
                    'query' => sprintf(
                        "concat(%s) ct '{{ __key__ }}'",
                        $relationManager
                        ->getPrimaryAttributeNames()
                        ->map(function ($x) use ($relatedName) {
                            return "~$relatedName.$x~";
                        })->implode(',')
                    ),
                    'label' => $relationManager
                        ->getPrimaryAttributeNames()
                        ->map(function ($x) use ($relatedName) {
                            return "{{ ~$relatedName.$x~ }}";
                        })->implode(' - '),
                ],
                'persist' => [
                    'attributes' => [
                        $nameComponent => [
                            'path' => 'value',
                        ],
                    ],
                    'data' => [
                        'name'       => app('amethyst')->getNameDataByModel($relation['intermediate']),
                        'scopes'     => $fixed,
                        'attributes' => [
                            $relatedPivotKey => '{{ value.id }}',
                        ],
                    ],
                ],
            ],
        ];

        return $params;
    }
}
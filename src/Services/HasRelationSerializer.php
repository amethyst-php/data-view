<?php

namespace Amethyst\Services;

use Illuminate\Database\Eloquent\Model;
use Railken\Lem\Attributes;

trait HasRelationSerializer
{
    public function serializeRelation(array $relation): array
    {
        $method = sprintf('serialize%s', $relation['type']);

        if (method_exists($this, $method)) {
            return $this->$method($relation);
        }

        return [];
    }

    public function serializeBelongsToAttribute(Attributes\BelongsToAttribute $attribute): iterable
    {
        $data = $attribute->getManager()->newEntity()->getMorphClass();

        $params = [
            'name'    => $attribute->getName(),
            'extends' => 'attribute-input',
            'type'    => 'attribute',
            'options' => [
                'name'     => $attribute->getName(),
                'type'     => 'autocomplete',
                'required' => (bool) $attribute->getRequired(),
                'extract'  => [
                    'attributes' => [
                        $attribute->getRelationName() => [
                            'path' => $attribute->getRelationName(),
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
                        ->map(function ($x) {
                            return "{{ value.$x }}";
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
                            ->map(function ($x) use ($attribute) {
                                return "$x";
                            })->implode(',')
                    ),
                    'label' => $attribute
                        ->getRelationManager()
                        ->getPrimaryAttributeNames()
                        ->map(function ($x) use ($attribute) {
                            return "{{ $x }}";
                        })->implode(' - '),
                ],
                'persist' => [
                    'attributes' => [
                        $attribute->getName() => [
                            'template' => '{{ value.id }}',
                        ],
                        $attribute->getRelationName() => [
                            'path' => 'value',
                        ],
                    ],
                ],
                'actions' => [
                    'update' => sprintf('%s-resource-upsert', $data),
                ],
            ],
        ];

        return [$params];
    }

    public function serializeMorphToAttribute(Attributes\MorphToAttribute $attribute): iterable
    {
        $return = [];

        foreach ($this->getKeysFromMorphTo($attribute) as $key) {
            $params = [
                'name'    => $attribute->getName(),
                'extends' => 'attribute-input',
                'type'    => 'attribute',
                'options' => [
                    'name'     => $attribute->getName(),
                    'type'     => 'autocomplete',
                    'required' => (bool) $attribute->getRequired(),
                    'extract'  => [
                        'attributes' => [
                            $attribute->getRelationName() => [
                                'path' => $attribute->getRelationName(),
                            ],
                            /*$attribute->getName() => [
                                'data' => $key,
                                'query' => sprintf('id eq {{ resource.%s }}', $attribute->getName())
                            ]*/
                        ],
                    ],
                    'readable' => [
                        'type'  => 'default',
                        'label' => $attribute
                            ->getRelationManagerByKey($key)
                            ->getPrimaryAttributeNames()
                            ->map(function ($x) {
                                return "{{ value.$x }}";
                            })->implode(' '),
                    ],
                    'include'   => [$attribute->getRelationName()],
                    'condition' => sprintf('%s === {{ resource.%s }}', $key, $attribute->getRelationKey()),
                    'persist'   => [
                        'attributes' => [
                            $attribute->getName() => [
                                'template' => '{{ value.id }}',
                            ],
                            $attribute->getRelationName() => [
                                'path' => 'value',
                            ],
                        ],
                    ],
                    'select' => [
                        'data'  => $key,
                        'query' => sprintf(
                            "concat(%s) ct '{{ __key__ }}'",
                            $attribute
                                ->getRelationManagerByKey($key)
                                ->getPrimaryAttributeNames()
                                ->map(function ($x) use ($attribute) {
                                    return "$x";
                                })->implode(',')
                            ),
                        'label' => $attribute
                            ->getRelationManagerByKey($key)
                            ->getPrimaryAttributeNames()
                            ->map(function ($x) use ($attribute) {
                                return "{{ $x }}";
                            })->implode(' - '),
                    ],
                    'actions' => [
                        'update' => sprintf('%s-resource-upsert', $key),
                    ],
                ],
            ];

            $return[] = $params;
        }

        return $return;
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

    public function serializeMorphToMany(array $relation): iterable
    {
        $relationManager = app('amethyst')->findManagerByName($relation['related']);
        $name = $relation['name'];

        $relatedPivotKey = $relation['relatedPivotKey'];
        $foreignPivotKey = $relation['foreignPivotKey'];

        $fixed = [
            $foreignPivotKey       => '{{ resource.id }}',
            $relation['morphType'] => $relation['morphClass'],
        ];

        foreach ($relation['scope'] as $scope) {
            $column = $scope['column'];
            $columns = explode('.', $column);
            if ((count($columns) > 1) && $columns[0] === $relation['table']) {
                $columns = [$columns[1]];
            }

            $column = implode('.', $columns);

            $fixed[$column] = $scope['value'];
        }

        $params = [
            'name'    => $name,
            'extends' => 'attribute-input',
            'type'    => 'attribute',
            'options' => [
                'name'     => $name,
                'type'     => 'autocomplete',
                'hide'     => false,
                'required' => false,
                'multiple' => true,
                'default'  => [],
                'include'  => [$name],
                'extract'  => [
                    'attributes' => [
                        $name => [
                            'path' => $name,
                        ],
                    ],
                ],
                'readable' => [
                    'type'  => 'default',
                    'label' => $relationManager
                        ->getPrimaryAttributeNames()
                        ->map(function ($x) {
                            return "{{ value.$x }}";
                        })->implode(' '),
                ],
                'select' => [
                    'data'  => $relation['data'],
                    'query' => sprintf(
                        "concat(%s) ct '{{ __key__ }}'",
                        $relationManager
                        ->getPrimaryAttributeNames()
                        ->map(function ($x) {
                            return "$x";
                        })->implode(',')
                    ),
                    'label' => $relationManager
                        ->getPrimaryAttributeNames()
                        ->map(function ($x) {
                            return "{{ $x }}";
                        })->implode(' - '),
                ],
                'persist' => [
                    'attributes' => [
                        $name => [
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

        return [$params];
    }
}

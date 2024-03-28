<?php

namespace Amethyst\Services;

use Amethyst\Core\Attributes\DataNameAttribute;

trait HasTabSerializer
{
    public function serializeTab(string $name, array $relation): array
    {
        $method = sprintf('serializeTab%s', $relation['type']);

        if (method_exists($this, $method)) {
            try {
                return $this->$method($name, $relation);
            } catch (\Amethyst\Core\Exceptions\DataNotFoundException $e) {
                return [];
            }
        }

        return [];
    }

    public function serializeTabMorphToMany(string $name, array $relation): iterable
    {
        $relationManager = app('amethyst')->findManagerByName($relation['related']);

        $relatedPivotKey = $relation['relatedPivotKey'];
        $foreignPivotKey = $relation['foreignPivotKey'];

        $fixed = [
            $foreignPivotKey       => '{{ containerResource.id }}',
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

        $relatedEnclosed = $this->enclose($relatedName);

        $intermediate = app('amethyst')->getNameDataByModel($relation['intermediate']);

        foreach (app('amethyst')->get($intermediate)->getAttributes() as $attribute) {
            if ($attribute instanceof DataNameAttribute) {
                if (isset($fixed[$attribute->getName()])) {
                    $fixed[$attribute->getName()] = $this->enclose($fixed[$attribute->getName()]);
                }
            }
        }

        $inversed = app('eloquent.mapper')->getInversedRelation($name, $relation['related'], $relation['name']);

        $query = $inversed
            ? sprintf('%s.id eq {{ containerResource.id }}', $inversed)
            : sprintf('id in (0, {{ containerResource.%s|mapByKey("id").join(",") }})', $nameComponent);

        $fixed[$relatedPivotKey] = '{{ resource.id }}';

        $params = [
            'name'    => $nameComponent,
            'extends' => $relatedEnclosed.'.data.iterator.table',
            'options' => [
                'containerInclude' => [$nameComponent],
                'query'            => $query,
                'persist'          => [
                    'data' => [
                        'name'       => $intermediate,
                        'attributes' => $fixed,
                    ],
                ],
            ],
        ];

        return $params;
    }

    public function serializeTabMorphMany(string $name, array $relation): iterable
    {
        $relationManager = app('amethyst')->findManagerByName($relation['related']);

        $foreignKey = str_replace('_id', '', $relation['foreignKey']);

        $fixed = [
            $foreignKey => [
                'path' => 'containerResource',
            ],
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

        $relatedEnclosed = $this->enclose($relatedName);

        $inversed = app('eloquent.mapper')->getInversedRelation($name, $relation['related'], $relation['name']);

        $query = $inversed
            ? sprintf('%s.id eq {{ containerResource.id }}', $inversed)
            : sprintf('id in (0, {{ containerResource.%s|mapByKey("id").join(",") }})', $nameComponent);

        $params = [
            'name'    => $nameComponent,
            'extends' => $relatedEnclosed.'.data.iterator.table',
            'options' => [
                'containerInclude' => [$nameComponent],
                'query'            => $query,
                'fixed'            => [
                    'attributes' => $fixed,
                ],
            ],
        ];

        return $params;
    }
}

<?php

namespace Amethyst\Services;

use Railken\Lem\Attributes;

trait HasAttributeSerializer
{
    public function serializeRelationAttribute(Attributes\BaseAttribute $attribute): array
    {
        $method = sprintf('serialize%sAttribute', $attribute->getType());

        if (!method_exists($this, $method)) {
            $method = 'serializeBaseAttribute';
        }

        return $this->$method($attribute);
    }

    public function serializeBaseAttribute(Attributes\BaseAttribute $attribute): iterable
    {
        $params = [
            'name'    => '~'.$attribute->getName().'~',
            'extends' => 'attribute-input',
            'type'    => 'attribute',
            'options' => [
                'name' => '~'.$attribute->getName().'~',
                'type' => $attribute->getType(),
                'hide' => false, // 'hide' => in_array($attribute->getType(), ['LongText', 'Json', 'Array', 'Object'], true),
                // 'fillable'   => (bool) $attribute->getFillable(),
                'required' => (bool) $attribute->getRequired(),
                'unique'   => (bool) $attribute->getUnique(),
                'default'  => $attribute->getDefault($attribute->getManager()->newEntity()),
                // 'descriptor' => $attribute->getDescriptor(),
                'extract' => [
                    'attributes' => [
                        $attribute->getName() => [
                            'path' => '~'.$attribute->getName().'~',
                        ],
                    ],
                ],
                'readable' => [
                    'type'  => 'default',
                    'label' => '{{ value }}',
                ],
                // 'inject' => $attribute->getName(),
                'persist' => [
                    'attributes' => [
                        '~'.$attribute->getName().'~' => [
                            'path' => 'value',
                        ],
                    ],
                ],
                'select' => [
                    'attributes' => [
                        '~'.$attribute->getName().'~' => "{{ resource.~{$attribute->getName()}~ }}",
                    ],
                ],
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
}

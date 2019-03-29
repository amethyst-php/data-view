<?php

return [
    'table'      => 'amethyst_data_views',
    'comment'    => 'DataView',
    'model'      => Railken\Amethyst\Models\DataView::class,
    'schema'     => Railken\Amethyst\Schemas\DataViewSchema::class,
    'repository' => Railken\Amethyst\Repositories\DataViewRepository::class,
    'serializer' => Railken\Amethyst\Serializers\DataViewSerializer::class,
    'validator'  => Railken\Amethyst\Validators\DataViewValidator::class,
    'authorizer' => Railken\Amethyst\Authorizers\DataViewAuthorizer::class,
    'faker'      => Railken\Amethyst\Fakers\DataViewFaker::class,
    'manager'    => Railken\Amethyst\Managers\DataViewManager::class,
];

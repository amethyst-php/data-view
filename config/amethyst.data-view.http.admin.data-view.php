<?php

return [
    'enabled'    => true,
    'controller' => Railken\Amethyst\Http\Controllers\Admin\DataViewsController::class,
    'router'     => [
        'as'     => 'data-view.',
        'prefix' => '/data-views',
    ],
];

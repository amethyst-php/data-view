<?php

return [
    'enabled'    => true,
    'controller' => Amethyst\Http\Controllers\Admin\DataViewsController::class,
    'router'     => [
        'as'     => 'data-view.',
        'prefix' => '/data-views',
    ],
];

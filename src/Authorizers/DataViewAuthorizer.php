<?php

namespace Amethyst\Authorizers;

use Railken\Lem\Authorizer;
use Railken\Lem\Tokens;

class DataViewAuthorizer extends Authorizer
{
    /**
     * List of all permissions.
     *
     * @var array
     */
    protected $permissions = [
        Tokens::PERMISSION_CREATE => 'data-view.create',
        Tokens::PERMISSION_UPDATE => 'data-view.update',
        Tokens::PERMISSION_SHOW   => 'data-view.show',
        Tokens::PERMISSION_REMOVE => 'data-view.remove',
    ];
}

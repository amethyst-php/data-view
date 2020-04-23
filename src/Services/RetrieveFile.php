<?php

namespace Amethyst\Services;

trait RetrieveFile
{
    public function retrieveFile(string $name)
    {
        $filename = storage_path("assets/amethyst/$name-icon.svg");

        if (!file_exists($filename)) {
            $filename = storage_path('assets/amethyst/data-view-icon.svg');
        }

        if (!file_exists($filename)) {
            throw new \Exception('No icons found. Please publish assets using `php artisan vendor:publish --tag=assets`');
        }

        $result = app('amethyst')->get('file')->createOrFail([
            'name'   => 'data-view.icon.'.$name.'.svg',
            'public' => 1,
        ]);

        $resource = $result->getResource();
        $resource
            ->addMedia($filename)
            ->preservingOriginal()
            ->toMediaCollection('data-view');

        return $resource->getFullUrl();
    }
}

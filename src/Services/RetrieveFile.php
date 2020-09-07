<?php

namespace Amethyst\Services;

trait RetrieveFile
{
    public static $cache = [];

    public function retrieveFile(string $name)
    {
        $filename = storage_path("assets/amethyst/$name-icon.svg");

        if (isset(self::$cache[$filename])) {
            return self::$cache[$filename];
        }

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

        self::$cache[$filename] = $resource->getFullUrl();

        return $resource->getFullUrl();
    }
}

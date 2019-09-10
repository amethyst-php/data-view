<?php

namespace Amethyst\Observers;

use Amethyst\Managers\DataViewManager;
use Amethyst\Models\ModelHasPermission;
use Amethyst\Models\Permission;

class DataViewPermissionObserver
{
    public function getPermission()
    {
        return Permission::where('name', 'data-view.show')->first();
    }

    public function getValuesByAction(string $action, string $data): array
    {
        $values = [];

        if ($action === 'show') {
            $values = array_merge($values, [
                sprintf('%s-bookmark', $data),
                sprintf('%s-routes', $data),
                sprintf('%s-resource-index', $data),
                sprintf('%s-resource-show', $data),
                sprintf('%s-page-index', $data),
                sprintf('%s-page-show', $data),
            ]);
        }

        if ($action === 'upsert') {
            $values = array_merge($values, [
                sprintf('%s-resource-upsert', $data),
            ]);
        }

        if ($action === 'remove') {
            $values = array_merge($values, [
                sprintf('%s-resource-delete', $data),
            ]);
        }

        return $values;
    }

    /**
     * Handle the ModelHasPermission "created" event.
     *
     * @param \Amethyst\Models\ModelHasPermission $modelHasPermission
     */
    public function created(ModelHasPermission $modelHasPermission)
    {
        // When a new permission is added, add automatically the data-view corrisponding

        $permission = $this->getPermission();

        if (!$permission) {
            return;
        }

        list($data, $action) = explode('.', $modelHasPermission->permission->name);

        if ($data === 'data-view') {
            return;
        }

        $values = $this->getValuesByAction($action, $data);

        $dataViews = app(DataViewManager::class)->getRepository()->newQuery()
            ->whereIn('name', $values)
            ->get();

        foreach ($dataViews as $dataView) {
            ModelHasPermission::firstOrCreate([
                'permission_id' => $permission->id,
                'object_type'   => 'data-view',
                'object_id'     => $dataView->id,
                'model_type'    => $modelHasPermission->model_type,
                'model_id'      => $modelHasPermission->model_id,
                'attribute'     => 'id,name,type,description,config,enabled,created_at,updated_at,deleted_at',
            ]);
        }
    }

    /**
     * Handle the ModelHasPermission "deleted" event.
     *
     * @param \Amethyst\Models\ModelHasPermission $modelHasPermission
     */
    public function deleted(ModelHasPermission $modelHasPermission)
    {
    }
}

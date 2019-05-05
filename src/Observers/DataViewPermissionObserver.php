<?php

namespace Railken\Amethyst\Observers;

use Railken\Amethyst\Models\ModelHasPermission;
use Railken\Amethyst\Models\Permission;
use Railken\Amethyst\Managers\DataViewManager;

class DataViewPermissionObserver
{
    public function getPermissionId()
    {
        return Permission::where("name", "data-view.show")->first()->id;
    }

    public function getValuesByAction(string $action, string $data): array
    {
        $values = [];

        if ($action === 'show') {
            $values = array_merge($values, [
                sprintf("%s-bookmark", $data),
                sprintf("%s-routes", $data),
                sprintf("%s-resource", $data),
                sprintf("%s-resource-index", $data),
                sprintf("%s-resource-show", $data),
                sprintf("%s-page-index", $data),
                sprintf("%s-page-show", $data)
            ]);
        }

        if ($action === 'create') {
            $values = array_merge($values, [
                sprintf("%s-resource-create", $data),
            ]);
        }

        if ($action === 'update') {
            $values = array_merge($values, [
                sprintf("%s-resource-update", $data),
                sprintf("%s-resource-create-or-update", $data),
            ]);
        }

        if ($action === 'remove') {
            $values = array_merge($values, [
                sprintf("%s-resource-remove", $data),
                sprintf("%s-resource-delete", $data),
            ]);
        }

        return $values;
    }

    /**
     * Handle the ModelHasPermission "created" event.
     *
     * @param  \Railken\Amethyst\Models\ModelHasPermission $modelHasPermission
     * @return void
     */
    public function created(ModelHasPermission $modelHasPermission)
    {

        // When a new permission is added, add automatically the data-view corrisponding

        list($data, $action) = explode(".", $modelHasPermission->permission->name);

        if ($data === 'data-view') {
            return;
        }

        $values = $this->getValuesByAction($action, $data);

        $dataViews = app(DataViewManager::class)->getRepository()->newQuery()
            ->whereIn('name', $values)
            ->get();

        foreach ($dataViews as $dataView) {
            ModelHasPermission::firstOrCreate([
                'permission_id' => $this->getPermissionId(),
                'object_type' => 'data-view',
                'object_id' => $dataView->id,
                'model_type' => $modelHasPermission->model_type,
                'model_id' => $modelHasPermission->model_id,
                'attribute' => 'id,name,type,description,config,enabled,created_at,updated_at,deleted_at'
            ]);
        }
    }

    /**
     * Handle the ModelHasPermission "deleted" event.
     *
     * @param  \Railken\Amethyst\Models\ModelHasPermission  $modelHasPermission
     * @return void
     */
    public function deleted(ModelHasPermission $modelHasPermission)
    {
        //
    }
}
<?php


namespace Ycbl\AdminAuth\Dao;

use Ycbl\AdminAuth\Model\AuthGroup as Model;

class AuthGroup
{
    public function getGroupsById($ids)
    {
        return Model::query()->whereIn('id', $ids)->get();
    }


    public function getEnableGroupsById($ids)
    {
        return Model::query()->select('id', 'pid', 'name', 'rules')
            ->whereIn('id', $ids)
            ->where('status', '=', '1')
            ->get();
    }

    public function getEnableGroups()
    {
        return Model::query()
            ->where('status', '=', '1')
            ->get();
    }

    public function getOneGroupsById($id)
    {
        return Model::query()->where('id', $id)->first();
    }

    public function insertGroup($data)
    {
        return Model::query()->insert($data);
    }
}
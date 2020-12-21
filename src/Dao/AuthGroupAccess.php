<?php


namespace Ycbl\AdminAuth\Dao;

use Ycbl\AdminAuth\Model\AuthGroupAccess as Model;

class AuthGroupAccess
{
    public function getUserGroupIds($uid)
    {
        return Model::query()->where('uid',$uid)->pluck('group_id');
    }

}
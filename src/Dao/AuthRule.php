<?php


namespace Ycbl\AdminAuth\Dao;

use Ycbl\AdminAuth\Model\AuthRule as Model;

class AuthRule
{
    public function getRuleList()
    {
        return Model::query()
            ->select(["id", "pid", "path", "auth", "title", "icon", "ismenu", "weigh", "status"])
            ->orderBy('weigh', 'DESC')->orderBy('id')
            ->get();
    }

    public function getAllMenu()
    {
        $where[] = ['status', '=', '1'];
        $where[] = ['ismenu', '=', '1'];
        return Model::where($where)->get();
    }

    public function getEnableRulesById($ids)
    {
        $rules = Model::query()
            ->select(['id', 'pid', 'path', 'auth', 'icon', 'title', 'ismenu'])
            ->where('status', '=', '1');
        if (!in_array('*', $ids)) {
            $rules->whereIn('id', $ids);
        }
        return $rules->get();
    }
}
<?php


namespace Ycbl\AdminAuth\Dao;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use Ycbl\AdminAuth\Model\AuthGroup as Model;

class AuthGroup
{
    /**
     * @var Model
     */
    protected $model;

    public function __construct(ContainerInterface $container, ConfigInterface $config)
    {
        $this->model = $container->get($config->get('admin_auth.auth_group'));
    }

    public function getGroupsById($ids)
    {
        return $this->model::query()->whereIn('id', $ids)->get();
    }


    public function getEnableGroupsById($ids)
    {
        return $this->model::query()->select('id', 'pid', 'name', 'rules')
            ->whereIn('id', $ids)
            ->where('status', '=', '1')
            ->get();
    }

    public function getEnableGroups()
    {
        return $this->model::query()
            ->where('status', '=', '1')
            ->get();
    }

    public function getOneGroupsById($id)
    {
        return $this->model::query()->where('id', $id)->first();
    }

    public function insertGroup($data)
    {
        return $this->model::query()->insert($data);
    }
}
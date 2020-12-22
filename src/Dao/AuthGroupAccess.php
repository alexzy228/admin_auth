<?php


namespace Ycbl\AdminAuth\Dao;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use Ycbl\AdminAuth\Model\AuthGroupAccess as Model;

class AuthGroupAccess
{
    /**
     * @var Model
     */
    protected $model;

    public function __construct(ContainerInterface $container, ConfigInterface $config)
    {
        $this->model = $container->get($config->get('admin_auth.auth_group_access'));
    }

    public function getUserGroupIds($uid)
    {
        return $this->model::query()->where('uid', $uid)->pluck('group_id');
    }

    public function getUsersByGroupId($group_id)
    {
        return $this->model::query()->where('group_id', $group_id)->get();
    }

    public function saveAll($data)
    {
        return $this->model::insert($data);
    }

    public function deleteByUid($uid)
    {
        return $this->model::query()->where('uid', $uid)->delete();
    }
}
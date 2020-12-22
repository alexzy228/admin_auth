<?php

namespace Ycbl\AdminAuth\Dao;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use Ycbl\AdminAuth\Model\User as Model;

class User
{
    /**
     * @var Model
     */
    protected $model;

    public function __construct(ContainerInterface $container, ConfigInterface $config)
    {
        $this->model = $container->get($config->get('admin_auth.user'));
    }

    public function getAllUserIds()
    {
        return $this->model::query()->get()->pluck('id');
    }
}
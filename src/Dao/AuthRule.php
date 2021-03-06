<?php


namespace Ycbl\AdminAuth\Dao;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use Ycbl\AdminAuth\Model\AuthRule as Model;

class AuthRule
{
    /**
     * @var Model
     */
    protected $model;

    public function __construct(ContainerInterface $container, ConfigInterface $config)
    {
        $this->model = $container->get($config->get('admin_auth.auth_rule'));
    }

    public function getRuleList()
    {
        return $this->model::query()
            ->select(["id", "pid", "path", "auth", "title", "icon", "ismenu", "weigh", "status"])
            ->orderBy('weigh', 'DESC')->orderBy('id')
            ->get();
    }

    public function getOneRuleById($id)
    {
        return $this->model::query()->where('id', $id)->first();
    }

    public function insertRule($data)
    {
        return $this->model::query()->insert($data);
    }

    public function updateRuleById($id, $data)
    {
        return $this->model::query()->where('id', $id)->update($data);
    }

    public function deleteRule($ids)
    {
        return $this->model::query()->whereIn('id',$ids)->delete();
    }

    public function getAllMenu()
    {
        $where[] = ['status', '=', '1'];
        $where[] = ['ismenu', '=', '1'];
        return $this->model::where($where)->get();
    }

    public function getEnableRulesById($ids)
    {
        $rules = $this->model::query()
            ->select(['id', 'pid', 'path', 'auth', 'icon', 'title', 'ismenu', 'remark'])
            ->where('status', '=', '1');
        if (!in_array('*', $ids)) {
            $rules->whereIn('id', $ids);
        }
        return $rules->get();
    }
}
<?php

namespace Ycbl\AdminAuth\Service;

use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Collection;
use Ycbl\AdminAuth\Dao\AuthGroup;
use Ycbl\AdminAuth\Dao\AuthGroupAccess;
use Ycbl\AdminAuth\Dao\AuthRule;
use Ycbl\AdminAuth\Dao\User;

class AuthGroupService
{
    /**
     * @Inject
     * @var AuthGroup
     */
    protected $authGroupDao;

    /**
     * @Inject
     * @var AuthRule
     */
    protected $authRuleDao;

    /**
     * @Inject
     * @var AuthGroupAccess
     */
    protected $authGroupAccessDao;

    /**
     * @Inject
     * @var User
     */
    protected $userDao;

    /**
     * @Inject
     * @var AuthService
     */
    protected $auth;

    /**
     * 获取当前用户权限组列表
     * @return array
     */
    public function getList()
    {
        $children_group_ids = $this->getChildrenGroupIds(true);
        $group_list = $this->authGroupDao->getGroupsById($children_group_ids)->toArray();

        $result = [];
        $authTree = make(TreeService::class)->init($group_list);
        if ($this->auth->isSuperAdmin()) {
            $result = $authTree->getTreeList($authTree->getTreeArray(0));
        } else {
            $groups = $this->auth->getGroups();
            foreach ($groups as $m => $n) {
                $result = array_merge($result, $authTree->getTreeList($authTree->getTreeArray($n['pid'])));
            }
        }

        $group_name = [];
        foreach ($result as $k => $v) {
            $group_name[$v['id']] = $v['name'];
        }

        $list = $this->authGroupDao->getGroupsById(array_keys($group_name))->toArray();
        $group_list = [];
        foreach ($list as $k => $v) {
            $group_list[$v['id']] = $v;
        }
        $list = [];
        foreach ($group_name as $k => $v) {
            if (isset($group_list[$k])) {
                $group_list[$k]['name'] = $v;
                $list[] = $group_list[$k];
            }
        }
        return $list;
    }

    /**
     * 创建权限组
     * @param $pid
     * @param $name
     * @param $rules
     * @return bool
     * @throws Exception
     */
    public function createGroup($pid, $name, $rules)
    {
        $children_group_ids = $this->getChildrenGroupIds(true);
        if (!in_array($pid, $children_group_ids)) {
            throw new Exception('父组别超出权限范围');
        }
        $parent_model = $this->authGroupDao->getOneGroupsById($pid);
        if (!$parent_model) {
            throw new Exception('父组别未找到');
        }
        // 父级别的规则节点
        $parent_rules = explode(',', $parent_model->rules);
        // 当前组别的规则节点
        $current_rules = $this->auth->getRuleIds();// 当前组别的规则节点
        // 如果父组不是超级管理员则需要过滤规则节点,不能超过父组别的权限
        $rules = in_array('*', $parent_rules) ? $rules : array_intersect($parent_rules, $rules);
        // 如果当前组别不是超级管理员则需要过滤规则节点,不能超当前组别的权限
        $rules = in_array('*', $current_rules) ? $rules : array_intersect($current_rules, $rules);

        $data = [
            'pid' => $pid,
            'name' => $name,
            'rules' => $rules,
        ];
        return $this->authGroupDao->insertGroup($data);
    }

    /**
     * 编辑权限组
     * @param $group_id
     * @param $pid
     * @param $rules
     * @param $name
     * @param $status
     * @return bool
     * @throws Exception
     */
    public function editAuthGroup($group_id, $pid, $rules, $name, $status)
    {
        $current_group = $this->authGroupDao->getOneGroupsById($group_id);
        if (!$current_group) {
            throw new Exception('记录未找到');
        }
        $children_group_ids = $this->getChildrenGroupIds(true);
        if (!in_array($group_id, $children_group_ids)) {
            throw new Exception('您没有权限');
        }
        if (!in_array($pid, $children_group_ids)) {
            throw new Exception('父组别超出权限范围');
        }
        $children_group_list = $this->authGroupDao->getGroupsById($children_group_ids);
        $tree = make(TreeService::class)->init($children_group_list->toArray());
        if (in_array($pid, $tree->getChildrenIds($group_id))) {
            throw new Exception('父组别不能是它的子组别及本身');
        }

        $rules = explode(',', $rules);

        $parent_group = $this->authGroupDao->getOneGroupsById($pid);
        if (!$parent_group) {
            throw new Exception('父组别未找到');
        }
        // 父级别的规则节点
        $parent_rules = explode(',', $parent_group->rules);

        // 当前组别的规则节点
        $current_rules = $this->auth->getRuleIds();

        // 如果父组不是超级管理员则需要过滤规则节点,不能超过父组别的权限
        $rules = in_array('*', $parent_rules) ? $rules : array_intersect($parent_rules, $rules);
        // 如果当前组别不是超级管理员则需要过滤规则节点,不能超当前组别的权限
        $rules = in_array('*', $current_rules) ? $rules : array_intersect($current_rules, $rules);

        $rules = implode(',', $rules);
        Db::transaction(function () use ($children_group_list, $group_id, $status, $rules, $name, $pid) {
            $updateData = [
                'pid' => $pid,
                'name' => $name,
                'rules' => $rules,
                'status' => $status,
            ];
            $this->authGroupDao->updateGroupById($group_id, $updateData);
            foreach ($children_group_list as $key => $value) {
                $value->rules = implode(',', array_intersect(explode(',', $value->rules), explode(',', $rules)));
                $value->save();
            }
        });
        return true;
    }

    /**
     * 删除权限组
     * @param $ids
     * @return int|mixed
     * @throws Exception
     */
    public function deleteAuthGroup($ids)
    {
        $ids = explode(',', $ids);
        $group_list = $this->auth->getGroups();
        $group_ids = array_map(function ($group) {
            return $group['id'];
        }, $group_list);
        // 移除掉当前管理员所在组别
        $ids = array_diff($ids, $group_ids);
        $group_list = $this->authGroupDao->getGroupsById($ids);
        foreach ($group_list as $key => $value) {
            $group_user = $this->authGroupAccessDao->getUsersByGroupId($value->id);
            if ($group_user){
                $ids = array_diff($ids, [$value->id]);
                continue;
            }
            $group_child = $this->authGroupDao->getGroupsByPid($value->id);
            if ($group_child) {
                $ids = array_diff($ids, [$value->id]);
                continue;
            }
        }
        if (!$ids){
            throw new Exception('你不能删除含有子组和管理员的组');
        }
        return $this->authGroupDao->deleteGroup($ids);
    }

    /**
     * 获取权限组节点树
     * @param $pid
     * @param null $id
     * @return array
     * @throws Exception
     */
    public function getRoueTree($pid, $id = null)
    {
        $parent_group = $this->authGroupDao->getOneGroupsById($pid);
        $current_group = $this->authGroupDao->getOneGroupsById($id);

        if (($pid || $parent_group) && (!$id || $current_group)) {
            $rule_list = $this->authRuleDao->getRuleList()->toArray();
            //读取父类角色所有节点列表
            $parent_rule_list = [];
            if (in_array('*', explode(',', $parent_group->rules ?? ""))) {
                $parent_rule_list = $rule_list;
            } else {
                $parent_rule_ids = explode(',', $parent_group->rules ?? "");
                foreach ($rule_list as $k => $v) {
                    if (in_array($v['id'], $parent_rule_ids)) {
                        $parent_rule_list[] = $v;
                    }
                }
            }
            //当前所有可选规则列表
            $rule_tree = make(TreeService::class)->init($parent_rule_list);
            //当前所有角色组列表
            $children_group_ids = $this->getChildrenGroupIds(true);
            $children_groups = $this->authGroupDao->getGroupsById($children_group_ids)->toArray();
            $group_tree = make(TreeService::class)->init($children_groups);
            //当前角色下的规则ID
            $admin_rule_ids = $this->auth->getRuleIds();
            //是否是超级管理员
            $super_admin = $this->auth->isSuperAdmin();
            //当前拥有的规则ID集合
            $current_rule_ids = $id ? explode(',', $current_group->rules ?? "") : [];
            if (!$id || !in_array($pid, $children_group_ids) || !in_array($pid, $group_tree->getChildrenIds($id, true))) {
                $parent_rule_list = $rule_tree->getTreeList($rule_tree->getTreeArray(0));
                $has_childes = [];
                foreach ($parent_rule_list as $k => $v) {
                    if ($v['hasChild']) {
                        $has_childes[] = $v['id'];
                    }
                }
                $parent_rule_ids = array_map(function ($item) {
                    return $item['id'];
                }, $parent_rule_list);

                $node_list = [];
                foreach ($parent_rule_list as $K => $v) {
                    if (!$super_admin && !in_array($v['id'], $admin_rule_ids)) {
                        continue;
                    }
                    if ($v['pid'] && !in_array($v['id'], $parent_rule_ids)) {
                        continue;
                    }
                    $state = array('selected' => in_array($v['id'], $current_rule_ids) && !in_array($v['id'], $has_childes));
                    $node_list[] = array('id' => $v['id'], 'parent' => $v['pid'] ? $v['pid'] : '#', 'text' => $v['title'], 'type' => 'menu', 'state' => $state);
                }
                return $node_list;
            } else {
                throw new Exception("父组别不能是它的子组别");
            }
        } else {
            throw new Exception("角色组未找到");
        }
    }

    /**
     * 取出当前管理员所拥有权限的分组ID
     * @param false $withSelf
     * @return array
     */
    public function getChildrenGroupIds($withSelf = false)
    {
        $groups = $this->auth->getGroups();
        $groups_ids = [];
        foreach ($groups as $k => $v) {
            $groups_ids[] = $v['id'];
        }
        $origin_group_ids = $groups_ids;
        foreach ($groups as $k => $v) {
            if (in_array($v['pid'], $origin_group_ids)) {
                $groups_ids = array_diff($groups_ids, [$v['id']]);
                unset($groups[$k]);
            }
        }
        // 取出所有分组
        $group_list = $this->authGroupDao->getEnableGroups()->toArray();
        $obj_list = [];
        foreach ($groups as $k => $v) {
            if ($v['rules'] === '*') {
                $obj_list = $group_list;
                break;
            }
            $tree = make(TreeService::class)->init($group_list);
            $children_list = $tree->getChildren($v['id'], true);

            $children_tree = make(TreeService::class)->init($children_list);
            $obj = $children_tree->getTreeList($children_tree->getTreeArray($v['pid']));
            $obj_list = array_merge($obj_list, $obj);
        }
        $children_group_ids = [];
        foreach ($obj_list as $k => $v) {
            $children_group_ids[] = $v['id'];
        }
        if (!$withSelf) {
            $children_group_ids = array_diff($children_group_ids, $groups_ids);
        }
        return $children_group_ids;
    }

    /**
     * 获取当前用户的子用户ID
     * @param false $with_self
     * @return array|Collection
     */
    public function getChildrenAdminIds($with_self = false)
    {
        if (!$this->auth->isSuperAdmin()) {
            $group_ids = $this->getChildrenGroupIds();
            $children_admin_ids = $this->authGroupAccessDao->getUsersByGroupId($group_ids)->pluck('uid');
        } else {
            $children_admin_ids = $this->userDao->getAllUserIds()->toArray();
        }

        if ($with_self) {
            //包含自身 则添加自身ID
            if (!in_array($this->auth->getUserId(), $children_admin_ids)) {
                $children_admin_ids[] = $this->auth->getUserId();
            }
        } else {
            //不包含自身则排除自身ID
            $children_admin_ids = array_diff($children_admin_ids, [$this->auth->getUserId()]);
        }
        return $children_admin_ids;
    }
}
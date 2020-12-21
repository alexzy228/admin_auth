<?php


namespace Ycbl\AdminAuth\Service;


use _HumbugBox39a196d4601e\Nette\Neon\Exception;
use Hyperf\Di\Annotation\Inject;
use Ycbl\AdminAuth\Dao\AuthGroup;
use Ycbl\AdminAuth\Dao\AuthRule;

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
     * @var AuthService
     */
    protected $auth;

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
     * 取出当前管理员所拥有权限的分组
     * @param false $withSelf
     * @return array
     */
    public function getChildrenGroupIds($withSelf = false)
    {
        $groups = $this->auth->getGroups();
        $groups_ids = [];
        foreach ($groups as $k => $v){
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
        foreach ($groups as $k => $v){
            if ($v['rules'] === '*'){
                $obj_list = $group_list;
                break;
            }
            $tree = make(TreeService::class)->init($group_list);
            $children_list = $tree->getChildren($v['id'],true);

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
}
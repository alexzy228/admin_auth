<?php


namespace Ycbl\AdminAuth\Service;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Collection;
use Hyperf\Utils\Context;
use Qbhy\HyperfAuth\AuthManager;
use Ycbl\AdminAuth\Dao\AuthGroup;
use Ycbl\AdminAuth\Dao\AuthGroupAccess;
use Ycbl\AdminAuth\Dao\AuthRule;

class AuthService
{
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
     * @var AuthGroup
     */
    protected $authGroupDao;

    /**
     * @Inject
     * @var AuthManager
     */
    protected $authManager;

    const TREE = 1;
    const LIST = 2;
    /**
     * @var mixed
     */
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config->get('admin_auth');
    }

    public function getMenuList(int $type = self::TREE)
    {
        // 读取管理员当前拥有的权限节点
        $user_role = $this->getRuleList();
        // 获取所有菜单项
        $menu_list = $this->authRuleDao->getAllMenu()->toArray();
        //
        foreach ($menu_list as $k => $v) {
            if (!in_array($v['auth'], $user_role)) {
                unset($menu_list[$k]);
            }
        }
        $menu_tree = make(TreeService::class)->init($menu_list);
        $menu = $menu_tree->getTreeArray(0);
        if ($type === self::LIST) {
            return $menu_tree->getTreeList($menu);
        } else {
            return $menu;
        }

    }

    /**
     * 获取用户权限组
     * @param $uid
     * @return array|Collection|mixed
     */
    public function getGroups($uid = '')
    {
        $uid = $uid ? $uid : $this->authManager->user()->getId();
        if (Context::has('auth_group.' . $uid)) {
            return Context::get('auth_group.' . $uid);
        }

        $group_ids = $this->authGroupAccessDao->getUserGroupIds($uid);
        $user_group = $this->authGroupDao->getEnableGroupsById($group_ids)->toArray();

        Context::set('auth_group.' . $uid, $user_group ?: []);
        return Context::get('auth_group.' . $uid);
    }

    /**
     * 获取用户的所有规则ID
     * @param $uid
     * @return array
     */
    public function getRuleIds($uid = '')
    {
        $uid = $uid ? $uid : $this->authManager->user()->getId();
        $groups = $this->getGroups($uid);
        $ids = [];
        foreach ($groups as $group) {
            $ids = array_merge($ids, explode(',', trim($group['rules'], ',')));
        }
        $ids = array_unique($ids);
        return $ids;
    }

    /**
     * 获取规则列表
     * @param $uid
     * @return array|mixed|null
     */
    public function getRuleList($uid = '')
    {
        $uid = $uid ? $uid : $this->authManager->user()->getId();
        if (Context::has('auth_rule_list.' . $uid)) {
            return Context::get('auth_rule_list.' . $uid);
        }
        $redis = redis_pool('default');
        $redis_rule_list = $redis->get('_rule_list_' . $uid);
        if (2 == $this->config['auth_type'] && !empty($redis_rule_list)) {
            return json_decode($redis_rule_list, true);
        }
        $ids = $this->getRuleIds($uid);
        if (empty($ids)) {
            Context::set('auth_rule_list.' . $uid, []);
            return [];
        }
        $rules = $this->authRuleDao->getEnableRulesById($ids)->toArray();
        $rule_list = [];
        //拥有的规则id 包含* 则直接返回*
        if (in_array('*', $ids)) {
            $rule_list[] = '*';
        }
        foreach ($rules as $rule) {
            $rule_list[$rule['id']] = $rule['auth'];
        }
        Context::set('auth_rule_list.' . $uid, $rule_list);
        if (2 == $this->config['auth_type']) {
            //规则列表结果保存到session
            $redis->set('_rule_list_' . $uid, json_encode($rule_list));
        }
        return array_unique($rule_list);
    }

    public function cleanCache()
    {
        $redis = redis_pool('default');
        $redis->del("_rule_list_" . $this->authManager->user()->getId());
    }

    /**
     * 检查权限
     * @param $name
     * @param $uid
     * @param string $relation
     * @return bool
     */
    public function check($name, $uid = '', $relation = 'or')
    {
        $uid = $uid ? $uid : $this->authManager->user()->getId();
        //权限认证开关未开启状态直接返回验证成功
        if (!$this->config['auth_on']) {
            return true;
        }

        $ruleList = $this->getRuleList($uid);

        //规则列表包含* 则直接返回验证通过
        if (in_array('*', $ruleList)) {
            return true;
        }

        //判断验证数组还是字符串，转换为数组形式
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = [$name];
            }
        }
        //保存验证通过的规则名
        $list = [];

        foreach ($ruleList as $rule) {
            if (in_array($rule, $name)) {
                $list[] = $rule;
            }
        }
        if ('or' == $relation && !empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ('and' == $relation && empty($diff)) {
            return true;
        }
        return false;
    }

    public function isSuperAdmin()
    {
        return in_array('*', $this->getRuleIds()) ? true : false;
    }

}
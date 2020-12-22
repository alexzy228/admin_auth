<?php


namespace Ycbl\AdminAuth\Service;


use Exception;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Ycbl\AdminAuth\Dao\AuthRule;
use Hyperf\Di\Annotation\Inject;
use Ycbl\AdminAuth\Exception\AdminAuthException;

class RuleService
{
    /**
     * @Inject
     * @var AuthRule
     */
    protected $authRuleDao;

    /**
     * @Inject
     * @var ValidatorFactoryInterface
     */
    protected $validationFactory;

    const TREE = 1;
    const LIST = 2;

    /**
     * @Inject
     * @var AuthService
     */
    protected $auth;

    public function __construct()
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new AdminAuthException('仅超级管理组可以访问');
        }
    }

    /**
     * 获取所有权限规则
     * @param int $type TREE OR LIST
     * @return array
     */
    public function getAllRule(int $type = self::TREE): array
    {
        $list = $this->authRuleDao->getRuleList()->toArray();
        $tree = make(TreeService::class)->init($list);
        $arrTree = $tree->getTreeArray(0);
        if ($type == self::TREE) {
            return $arrTree;
        } else {
            return $tree->getTreeList($arrTree, 'title');
        }
    }

    /**
     * 创建规则
     * @param string $title 权限标题
     * @param string $path 前端路由
     * @param string $auth api路由
     * @param string $icon 图标
     * @param int $pid 父级ID
     * @param int $is_menu 是否菜单
     * @param int $weigh 权重
     * @param string $remark 备注
     * @return bool
     * @throws Exception
     */
    public function createRule(string $title, string $path, string $auth, $icon = '', $pid = 0, $is_menu = 0, $weigh = 0, $remark = '')
    {
        if (!$is_menu && !$pid) {
            throw new Exception('非菜单规则节点必须有父级');
        }
        $data = [
            'pid' => $pid,
            'path' => $path,
            'auth' => $auth,
            'title' => $title,
            'icon' => $icon,
            'remark' => $remark,
            'ismenu' => $is_menu,
            'weigh' => $weigh,
        ];
        $validator = $this->validationFactory->make($data, [
            'pid' => 'required|integer',
            'path' => 'required|unique:auth_rule',
            'auth' => 'required|unique:auth_rule',
            'title' => 'required',
            'icon' => 'required',
            'remark' => 'required',
            'ismenu' => 'required|boolean',
            'weigh' => 'required|integer',
        ]);
        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
        return $this->authRuleDao->insertRule($data);
    }

    /**
     * 编辑规则
     * @param $id
     * @param string $title
     * @param string $path
     * @param string $auth
     * @param $icon
     * @param $pid
     * @param $is_menu
     * @param $weigh
     * @param $remark
     * @return int
     * @throws Exception
     */
    public function editRule($id, string $title, string $path, string $auth, $icon, $pid, $is_menu, $weigh, $remark)
    {
        $rule = $this->authRuleDao->getOneRuleById($id);
        if (!$rule) {
            throw new Exception('记录未找到');
        }
        if (!$is_menu && !$pid) {
            throw new Exception('非菜单规则节点必须有父级');
        }
        if ($pid != $rule->pid) {
            //获取当前节点的所有子节点ID
            $all_rule = $this->authRuleDao->getRuleList()->toArray();
            $children_ids = make(TreeService::class)->init($all_rule)->getChildrenIds($rule->id);
            if (in_array($pid, $children_ids)) {
                throw new Exception("变更的父组别不能是它的子组别");
            }
        }
        $data = [
            'pid' => $pid,
            'path' => $path,
            'auth' => $auth,
            'title' => $title,
            'icon' => $icon,
            'remark' => $remark,
            'ismenu' => $is_menu,
            'weigh' => $weigh,
        ];

        $validator = $this->validationFactory->make($data, [
            'pid' => 'required|integer',
            'path' => 'required|unique:auth_rule,path,' . $id . ',id',
            'auth' => 'required|unique:auth_rule,auth,' . $id . ',id',
            'title' => 'required',
            'icon' => 'required',
            'remark' => 'required',
            'ismenu' => 'required|boolean',
            'weigh' => 'required|integer',
        ]);
        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
        return $this->authRuleDao->updateRuleById($id, $data);
    }

    /**
     * 删除规则
     * @param $ids
     * @return int|mixed
     */
    public function deleteRule($ids)
    {
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $del_ids = [];
        foreach ($ids as $k => $v) {
            $all_rule = $this->authRuleDao->getRuleList()->toArray();
            $children_ids = make(TreeService::class)->init($all_rule)->getChildrenIds($v);
            $del_ids = array_merge($del_ids, $children_ids);
        }
        return $this->authRuleDao->deleteRule($del_ids);
    }
}
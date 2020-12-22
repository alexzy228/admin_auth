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
}
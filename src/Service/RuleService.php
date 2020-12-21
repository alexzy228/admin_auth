<?php


namespace Ycbl\AdminAuth\Service;


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

    public function createRule()
    {

    }
}
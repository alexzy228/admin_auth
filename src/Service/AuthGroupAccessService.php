<?php


namespace Ycbl\AdminAuth\Service;

use Exception;
use Hyperf\Di\Annotation\Inject;
use Ycbl\AdminAuth\Dao\AuthGroupAccess;

class AuthGroupAccessService
{
    /**
     * @Inject
     * @var AuthGroupService
     */
    protected $authGroup;

    /**
     * @Inject
     * @var AuthGroupAccess
     */
    protected $authGroupAccessDao;

    /**
     * 增加用户权限组关系
     * @param $uid
     * @param string|array $group_id
     * @return bool
     */
    public function saveAuthGroupAccess(int $uid, $group_id)
    {
        if (!is_array($group_id)) {
            $group_id = explode(',', $group_id);
        }
        $children_group_ids = $this->authGroup->getChildrenGroupIds(true);
        //过滤不允许的组别,避免越权
        $groups = array_intersect($children_group_ids, $group_id);
        $dataSet = [];
        foreach ($groups as $group) {
            $dataSet[] = ['uid' => $uid, 'group_id' => $group];
        }
        return $this->authGroupAccessDao->saveAll($dataSet);
    }

    /**
     * 更新权限组
     * @param int $uid
     * @param $group_id
     * @return bool
     */
    public function updateAuthGroupAccess(int $uid, $group_id)
    {
        $this->authGroupAccessDao->deleteByUid($uid);
        return $this->saveAuthGroupAccess($uid, $group_id);
    }

    /**
     * 删除权限组
     * @param int $uid
     * @return int|mixed
     * @throws Exception
     */
    public function deleteAuthGroupAccess(int $uid)
    {
        $children_admin_ids = $this->authGroup->getChildrenAdminIds(true);
        if (!in_array($uid,$children_admin_ids)){
            throw new Exception('您没有权限');
        }
        return $this->authGroupAccessDao->deleteByUid($uid);
    }
}
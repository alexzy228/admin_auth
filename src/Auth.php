<?php

namespace Ycbl\AdminAuth;

use Hyperf\Di\Annotation\Inject;
use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\AuthManager;
use Ycbl\AdminAuth\Service\AuthService;

class Auth
{
    /**
     * @Inject
     * @var AuthService
     */
    protected $auth;

    /**
     * @Inject
     * @var AuthManager
     */
    protected $authManager;

    /**
     * 退出登录方法(清除权限缓存)
     * @return mixed
     */
    public function logout()
    {
        $this->auth->cleanCache();
        return $this->authManager->logout();
    }

    /**
     * 登录方法
     * @param Authenticatable $user
     * @return mixed
     */
    public function login(Authenticatable $user)
    {
        return $this->authManager->login($user);
    }

    /**
     * 检查是否登录方法
     * @return bool
     */
    public function isLogin()
    {
        return $this->authManager->check();
    }

    /**
     * 是否为超级管理员
     * @return bool
     */
    public function isSuperAdmin()
    {
        return $this->auth->isSuperAdmin();
    }

    /**
     * 检查权限
     * @param $name
     * @param string $uid
     * @param string $relation
     * @return bool
     */
    public function check($name, $uid = '', $relation = 'or')
    {
        return $this->auth->check($name, $uid, $relation);
    }
}
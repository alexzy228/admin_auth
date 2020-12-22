<?php

return [

    'auth_on' => true,//权限开关
    'auth_type' => 1,// 认证方式，1为实时认证；2为登录认证。
    'auth_group' => \Ycbl\AdminAuth\Model\AuthGroup::class, // 用户组数据模型
    'auth_group_access' => \Ycbl\AdminAuth\Model\AuthGroupAccess::class, // 用户-用户组模型
    'auth_rule' => \Ycbl\AdminAuth\Model\AuthRule::class, // 权限规则模型
    'auth_user' => \Ycbl\AdminAuth\Model\User::class, // 用户信息模型
];
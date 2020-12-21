<?php

declare(strict_types=1);

/*
 * 容器实例
 */

use Hyperf\Utils\ApplicationContext;

if (! function_exists('container')) {
    function container()
    {
        return ApplicationContext::getContainer();
    }
}


if (! function_exists('redis_pool')) {
    /**
     * redis 客户端连接池.
     * @param mixed $config
     * @return Redis
     */
    function redis_pool($config)
    {
        return container()->get(\Hyperf\Redis\RedisFactory::class)->get($config);
    }
}
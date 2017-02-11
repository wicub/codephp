<?php

namespace Tasks\Controller;

/**
 * 任务锁
 *
 * @author charles.jia <252069993@qq.com>
 */
class TaskLockController extends \Think\Controller {

    /**
     * 锁 key 前缀
     * @var string
     */
    private $lockKeyPrefix = 'redis|task|lock';

    /**
     * 获取一个锁
     * @param string $lockName 锁名称
     * @param boolean $autoRelease 是否在程序关闭的时候自动释放锁
     * @return boolean 获取成功返回true，获取失败返回false
     */
    public function acquireLock($lockName, $autoRelease = true) {
        $key = $this->lockKeyPrefix . '.' . $lockName;
        $cache = getRedis('queue');
        $result = $cache->get($key);
        if ($result) {
            return false;
        }

        $cache->set($key, 10010, 180);     //15分钟

       if ($autoRelease) {
            $callback = array($this, 'releaseLock');
            register_shutdown_function($callback, $lockName);
        }

        return true;
    }

    /**
     * 删除相应的存储内容
     * @param $lockName
     */
    public function releaseLock($lockName) {
        $key = $this->lockKeyPrefix . '.' . $lockName;
        $cache = getRedis('queue');
        $cache->delete($key);
    }

}

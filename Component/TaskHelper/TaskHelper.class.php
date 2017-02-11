<?php

namespace Tasks\Component\TaskHelper;

use Exception;

/**
 * 任务帮助基类
 * @author charles.jia <252069993@qq.com>
 */
abstract class TaskHelper {

    /**
     * wst2lv任务表(原生)记录<p>
     * 一般情况下很少用到此属性值
     * </p>
     * @var array 
     */
    protected $rawTask;

    /**
     * 通知到wst的url域名配置key
     * @var string 
     */
    protected $notifyUrlDomainConfKey = 'DOMAIN_FBS2';
    
    /**
     * 通知到wst的url程序文件名
     * @var string 
     */
    protected $notifyUrlScriptFilename = 'index.php';

    /**
     * 通知到wst的url路径
     * @var string 
     */
    protected $notifyUrlPath;

    public function setRawTask($rawTask) {
        $this->rawTask = $rawTask;
    }

    public function getNotifyUrlDomainConfKey() {
        return $this->notifyUrlDomainConfKey;
    }
    
    public function getNotifyUrlScriptFilename() {
        return $this->notifyUrlScriptFilename;
    }

    public function getNotifyUrlPath() {
        return $this->notifyUrlPath;
    }

    /**
     * 执行实际的任务，即任务入口
     * @param array $task 任务数据<p>
     * wst2lv任务表<b>task_data</b>字段json_decode为关联数组之后的值
     * </p>
     * @throws Exception 可能会抛出异常
     */
    abstract public function doTask($task);

    /**
     * 执行了任务之后会被调用，可以做一些清理的动作
     */
    //sabstract public function afterDoTask();

    /**
     * 取得给定任务的通知数据
     * @param array $rawTask wst2lv任务表(原生)记录
     */
    abstract public function getTaskNotifyData($rawTask);
}

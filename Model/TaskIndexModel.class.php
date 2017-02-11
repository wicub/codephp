<?php
/**
 * Created by PhpStorm.
 * User: charles.jia
 * Date: 2016-11-23
 * Time: 18:30
 */
namespace Tasks\Model;
use Think\Model;

class TaskIndexModel extends Model
{
    protected $logModel;

    /**
     * 处理状态：未处理
     */
    const STATUS_INITIAL = 0;
    /**
     * 处理状态：处理失败
     */
    const STATUS_FAILED = 1;
    /**
     * 处理状态：处理成功，待反馈
     */
    const STATUS_SUCCESS = 2;


    public function _initialize()
    {
        $this->tableName = 'lv_info_redis_logs';
        $this->logModel = M('lv_info_redis_logs');
      }

    /**
     * 记录日志
     * @param $log
     * @return mixed
     */
    public function  addRedisLog($log){
        $data =  $this->logModel->add($log);
        return  $data;
    }

    /**
     * 结束操作后的处理
     * @param $id
     * @param string $msg
     * @return mixed
     */
    public function finishTask($id, $msg = ''){
        $r = $this->logModel->where(array('id' => $id,'status'=>self::STATUS_INITIAL))->save(array('status' => self::STATUS_SUCCESS, 'reject_reason' => $msg));
        log_message('finishTask:' .$this->logModel->getlastsql());
        return $r;
    }

    /**
     * 设置后台任务处理发生错误
     * @param int $id
     * @param string $errMsg
     * @return mixed
     */
    public function setTaskFailed($id, $errMsg) {
        return $this->logModel->where(array('id' => $id, 'status' => self::STATUS_INITIAL))
            ->save(array('status' => self::STATUS_FAILED, 'reject_reason' => $errMsg));
    }



}
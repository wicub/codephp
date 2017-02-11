<?php
namespace Tasks\Controller;
use Tasks\Controller\TaskLockController,
    Tasks\Component\TaskHelper\TaskHelper,
    Tasks\Component\TaskHelper\GoodsSetInOutHelper,
    Tasks\Component\TaskHelper\GoodsSyncHelper;

/**
 * 进入接口
 *
 * @author charles.jia <252069993@qq.com>
 */
class TaskIndexController extends \Think\Controller {
    /**
     * 队列：商品审核结果消息队列
     */
    const Q_ADDNEW_RESULT = 'queue|wst2lv|product|addnew|result';

    /**
     * 队列：商品上下架操作
     */
    const Q_STATUS_RESULT = 'queue|wst2lv|product|status|result';

    /**
     * 队列的定义
     * @var array
     */
    public $dataListName = array(
        0 => self::Q_ADDNEW_RESULT, //商品审核结果消息队列
        1 => self::Q_STATUS_RESULT //商品上下架操作
    );



    private $taskHelperMap;
    public $taskIndexModel;

    public function _initialize() {
        $this->taskIndexModel = D('TaskIndex');
        //注册任务帮助对象
        $this->registerTaskHelper(self::Q_ADDNEW_RESULT, new GoodsSyncHelper());
        $this->registerTaskHelper(self::Q_STATUS_RESULT, new GoodsSetInOutHelper());
        //
        $this->executedTaskHelpers = array();
    }

    /**
     * 注册任务帮助对象
     * @param string $name 任务对列名称
     * @param TaskHelper $taskHelper 任务帮助对象
     */
    private function registerTaskHelper($name, $taskHelper) {
        $this->taskHelperMap[$name] = $taskHelper;
    }
    /**
     * 返回某task的任务帮助对象
     * @param array $queue 任务队列名称
     * @return TaskHelper
     */
    private function getTaskHelper($queue) {
        return $this->taskHelperMap[$queue];
    }


    /**
     * 相应的接入路口
     */
    public function doTaskData(){
        $redis = getRedis('queue');
        $taskLock = new TaskLockController();
        foreach($this->dataListName as $v){
            set_time_limit(0);
            $lockName = 'doTask';
            //1、上锁
            if (!$taskLock->acquireLock($lockName)) {
                echo "doTask: acquire lock failed!\n";
                return;
            }
            //2、每次读取并记录日志
            $count = $this->doTask($v,$redis);
            //3、插入数据库，重复三次之前，开启报警机制
            $taskLock->releaseLock($lockName);
            echo "doTask: {$count}\n";

            //4.两个月删一次，暂时不做
        }
    }

    /**
     * 读取队列并进行相应操作
     * @param $queue
     * @param $redis
     * @return int
     * @throws Exception
     */
    public function doTask($queue,$redis)
    {
        $count = 0;
        $executeCount = 20;    //执行次数。注：每次20条

        for ($i = 0; $i < $executeCount; $i++) {
            //while($msg=$redis->RPOP($queue)){
            try{
                $msg=$redis->LPOP($queue);
                if(!$msg){
                    continue;
                }

                if ($msg['doTaskError'] == 3) {
                    //记录日志
                    throw new \Exception("doTask: " . $queue . "3 times failed!\n");
                    //echo "doTask: ".$key. "3 times failed!\n";return
                } else {
                    //存入数据库
                    $msg = json_decode($msg, true);
                    $r = $this->addRedisLog($msg, $queue);
                    if ($r === false) {
                        throw new \Exception($queue . '数据：' . $msg . '保存数据库出错');
                    }
                    $this->taskIndexModel->startTrans();
                    //根据不同的key进行拆分，实例化
                    $this->dispatchTask($queue, $msg);
                    $this->finishTask($r);
                    $this->taskIndexModel->commit();
                    $count++;
                }
            }catch(\Exception $e){
                $this->taskIndexModel->rollback();
                //处理错误则回滚，并扔回redis里去。
                if(isset($msg['doTaskError']) && $msg['doTaskError']){
                    $msg['doTaskError'] = $msg['doTaskError'] + 1;
                }else{
                    $msg['doTaskError'] =  1;
                }
                $msg['doTaskErrorMsg'] =  defined(APP_DEBUG) && APP_DEBUG
                    ? sprintf("[%s] %s\n%s", date('Y-m-d H:i:s'), $e->getMessage(), $e->getTraceAsString())
                    : sprintf("[%s] %s", date('Y-m-d H:i:s'), $e->getMessage());

                $this->taskIndexModel->setTaskFailed($r, $msg['doTaskErrorMsg']);
                $this->pushData($queue,$msg,$redis);
            }
        }
        //做一些后续的处理，暂不用$this->afterDoTask();
        return $count;
    }

    /**
     * 将数据存入redis
     * @param $key
     * @param $data
     * @param $redis
     */
    public function pushData($key,$data,$redis){
        $redis->LPUSH($key,json_encode($data));
        echo '存储成功';
    }

    /**
     * 执行任务
     * @param $key
     * @param $task
     */
    private function dispatchTask($key,$task) {
        $queue = $key;
       /* if (!in_array($queue, $this->executedTaskHelpers)) {
            $this->executedTaskHelpers[] = $queue;
        }*/
        $taskData = $task;

        $taskHelper = $this->getTaskHelper($queue);
        $taskHelper->setRawTask($task);
        $taskHelper->doTask($taskData);
    }

    /**
     * 记录处理结束
     * @param $id
     * @param string $msg
     * @throws Exception
     */
    private function finishTask($id,$msg = '处理结束'){
        if ($this->taskIndexModel->finishTask($id,$msg) === false) {
            throw new Exception('修改任务为“已成功待处理”状态失败！');
        }

    }

    /**
     * 记录日志
     * @param $spu
     * @param $skus
     * @param $content
     * @return mixed
     */
    public function addRedisLog($data,$remark){
        $log['operate_content'] = json_encode($data);
        $log['operate_time'] = time();
        $log['remark'] = $remark;
        $r = $this->taskIndexModel->addRedisLog($log);
        return  $r;
    }



}

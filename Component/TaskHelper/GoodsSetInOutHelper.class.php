<?php

namespace Tasks\Component\TaskHelper;

use Tasks\Model\GoodsModel;

/**
 * 商品上下架 帮助类
 *
 * @author charles.jia <252069993@qq.com>
 */
class GoodsSetInOutHelper extends TaskHelper {
    protected $goodsModel;

    public function _initialize()
    {
        $this->goodsModel = D('GoodsModel');
    }

    /**
     * 执行相关接口
     * @param array $task
     * @throws Exception
     */
    public function doTask($task) {
        $this->doSetInOutGoods($task);
    }

    /**
     * 设置相应的提醒
     * @param array $rawTask
     * @return array
     */
    public function getTaskNotifyData($rawTask) {
        $taskData = json_decode($rawTask['logs_id'], true);
        return array('batchNo' => $rawTask['batch_no'], 'goodsNoList' => $taskData['goods_no_list']);
    }

    /**
     * 执行商品上下架动作
     * @param array $task 任务数据
     * @throws Exception
     */
    private function doSetInOutGoods($task) {
        $goodsModel =  new GoodsModel();
        $data = $task['data'];
        //task就是整个数据,将上下架数据进行整合，1是上架，0是下架{"150100": "1", "150101": "1"}
        foreach($data as $k=>$val ){
            $meiciSku =  $k;
            $status = $val == 1 ? 2 : 3;
            $result= $goodsModel->goodsSetInOut($meiciSku,$status);
            if ($result === false) {
                throw new Exception($val == 1 ? '商品上架失败！' : '商品下架失败！');
            }
        }
    }

   /* private function doSomethingMore($goodsIdArr, $action) {
        if ($action == 1) {         //如果是商品上架，则上架商品下面fbs那边未删除的所有的规格
            $newData = array('is_putaway' => 1);
            $specModel = new SpecModel();
            if ($specModel->updateSpecsByGoodsIdsWithoutFbsDeleted($goodsIdArr, $newData) === false) {
                throw new Exception('商品规格上架失败');
            }
        } else {
            $this->removeGoodsFromSpecial($goodsIdArr);
        }
    }*/

}

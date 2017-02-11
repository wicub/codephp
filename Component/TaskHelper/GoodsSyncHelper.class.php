<?php

namespace Tasks\Component\TaskHelper;

use Tasks\Model\GoodsModel;


/**
 * 商品同步 帮助类
 *
 * @author charles.jia <252069993@qq.com>
 */
class GoodsSyncHelper extends TaskHelper {

    protected $notifyUrlPath = 'Sync_good/syncGoodReturn';
   // protected $goodsModel;

    public function _initialize()
    {
        //$this->goodsModel = new GoodsModel();
    }

    /**
     * 执行相应的入口
     * @param array $task
     * @throws \Exception
     */
    public function doTask($task) {
        $this->doSyncGoods($task);
    }

    /**
     * 相应的提醒
     * @param array $rawTask
     * @return array
     */
    public function getTaskNotifyData($rawTask) {
        $taskData = json_decode($rawTask['task_data'], true);
        return array('batchNo' => $rawTask['batch_no'], 'goods_no' => $taskData['goods_no']);
    }

    /**
     * 更新商品审核的结果
     * @param $data
     * @throws \Exception
     */
    private function doSyncGoods($data) {
        $whereis['meici_spu'] = $data['spu'];
        //$whereis['supplier_id'] = $data['supplier_id'];
        //根据supplier_id得到相关的价格类型$bill_type
        $supplierInfo =  api('org','/api/supplier/detail',array('id'=>$data['supplier_id']));
        if(empty($supplierInfo['data'])){
            throw new \Exception('供应商相关类型信息错误');
        }
        $bill_type = empty($supplierInfo['data']['bill_type']) ? array() :$supplierInfo['data']['bill_type'];

        //修改审核结果，批量过来的就是全对，错误的会单独发送
        if(isset($data['check_info']) && $data['check_info']){
            $this->validationParam($data['check_info'],$data['reason'],$whereis,'check_info');
        }

        if(isset($data['check_img']) && $data['check_img']){
            $this->validationParam($data['check_img'],$data['reason'],$whereis,'check_img');
        }

        //audit_price_status是一栏，new_price,price,last_price
        if(isset($data['check_price']) && $data['check_price']){
            $this->validationParam($data['check_price'],$data['reason'],$whereis,'check_price',$bill_type);
        }
    }

    /**
     * 参数的判断
     * @param $data
     * @param $reason
     * @param $whereis
     * @param $param
     * @param string $bill_type
     * @throws \Exception
     */
    private function validationParam($data,$reason,$whereis,$param,$bill_type=''){
        $goodsModel =  new GoodsModel();
        $meiciSkuData =  explode(',',$data['skus']);
        $where = $whereis;
        //log_message('validationParam:' .$data['audit_info_status']);
        foreach($meiciSkuData as $v){
            $where['meici_sku'] = $v;
            if($param == 'check_info'){
                $sendData['meici_audit_info_status'] =   $data['audit_info_status'];
                $sendData['meici_audit_info_status_msg'] =   $reason;
                if($sendData['meici_audit_info_status'] == 2){
                    $sendData['meici_status']  = 4;
                }

                $r = $goodsModel->doSyncGoods($where,$sendData);
                if($r === false){
                   throw new \Exception('商品审核信息更新失败');
               }
            }
            //查看图片
            if($param == 'check_img'){
                $sendData['meici_audit_pic_status'] =   $data['audit_pic_status'];
                $sendData['meici_audit_pic_status_msg'] =   $reason;
                if($sendData['meici_audit_info_status'] == 2){
                    $sendData['meici_status']  = 4;
                }
                $r = $goodsModel->doSyncGoods($where,$sendData);
                if($r === false){
                    throw new \Exception('商品图片信息更新失败');
                }

            }
            //查看价格
            if($param == 'check_price'){
                //调用修改价格的model
                $sendData['meici_audit_price_status'] =   $data['audit_info_status'];
                $sendData['meici_audit_price_status_msg'] =   $reason;
                if($sendData['meici_audit_info_status'] == 2){
                    $sendData['meici_status']  = 4;
                }
                $r = $goodsModel->doSyncGoods($where,$sendData);
                if($r === false){
                    throw new \Exception('商品价格信息更新失败');
                }
                if($data['audit_info_status'] == 1){
                    //1、new_price，price，last_price
                    $data = $goodsModel->doSyncGoodsPrice($where,$bill_type);
                    if(empty($data)){
                        throw new \Exception('商品价格信息获取失败');
                    }
                    //2、将new_price保存到price，price保存到last_price
                    $r = $goodsModel->doSyncGoodsPriceChange($where,$data[0],$bill_type);
                    if($r === false){
                        throw new \Exception('商品价格信息保存失败');
                    }
                }
            }

        }
    }

}

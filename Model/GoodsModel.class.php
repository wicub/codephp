<?php
/**
 * Created by PhpStorm.
 * User: charles.jia
 * Date: 2016-11-23
 * Time: 18:30
 */
namespace Tasks\Model;
use Think\Model;

class GoodsModel extends Model
{
    private $logModel;
    private $skuModel;
    /**
     * 美西状态：上架
     */
    const STATUS_UP = 2;

    /**
     * 美西状态：下架
     */
    const STATUS_DOWN = 3;


    public function _initialize()
    {
        $this->tableName = 'lv_info_product_sku';
        $this->skuModel = M('lv_info_product_sku');
        $this->logModel = M('lv_info_redis_logs');

      }

    /**
     * 处理相关商品
     * @param $where
     * @param $data
     * @return mixed
     */
    public function doSyncGoods($where,$data){
        $result = $this->skuModel->where($where)->save($data);
        log_message('doSyncGoods:' . $this->skuModel->getlastsql());
        return  $result;
    }

    /**
     * 处理相关价格
     * @param $where
     * @param $bill_type
     * @return mixed
     */
    public function doSyncGoodsPrice($where,$bill_type){
        if($bill_type == 1){
            $result = $this->skuModel->field('new_cost_price,cost_price,last_cost_price')->where($where)->select();
        }else{
            $result = $this->skuModel->field('new_price,price,last_price')->where($where)->select();
        }
        log_message('where:' . json_encode($where));
        log_message('doSyncGoodsPrice:' . $this->skuModel->getlastsql());
        return  $result;
    }

    /**
     * 处理相应的价格变化
     * @param $where
     * @param $data
     * @param $bill_type
     * @return mixed
     */
    public function doSyncGoodsPriceChange($where,$data,$bill_type){
        if($bill_type == 1){
            $saveData['cost_price'] = $data['new_cost_price'];
            $saveData['last_cost_price'] = $data['cost_price'];
        }else{
            $saveData['price'] = $data['new_price'];
            $saveData['last_price'] = $data['price'];
        }
        //2017.1.11价格审核通过同时上架
        $saveData['meici_status']  = self::STATUS_UP;
        $result = $this->skuModel->where($where)->save($saveData);
        log_message('doSyncGoodsPriceChange:' . $this->skuModel->getlastsql());
        return  $result;
    }

    /**
     * 商品上下架
     * @param $meiciSku
     * @param $status
     * @return mixed
     */
    public function goodsSetInOut($meiciSku,$status){
        $where['meici_sku'] = $meiciSku;
        $data['meici_status']  =  $status;
        $result = $this->skuModel->where($where)->save($data);
        log_message('goodsSetInOut:' . $this->skuModel->getlastsql());
        return  $result;
    }


    /**
     * 记录日志
     * @param $log
     * @return mixed
     */
    public function  saveRedisLog($log){
        $data =  $this->logModel->add($log);
        return  $data;
    }



}
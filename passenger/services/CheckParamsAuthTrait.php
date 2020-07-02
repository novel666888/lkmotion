<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/11/8
 * Time: 16:15
 */
namespace passenger\services;

use passenger\models\Order;

trait CheckParamsAuthTrait
{
    /**
     * 检查订单是否属于某用户
     *
     * @param $userId
     * @param $orderId
     * @return bool
     */
    public function checkOrderIdBelongToUser($userId,$orderId)
    {
        $orderActiveRecord = Order::findOne(['passenger_info_id'=>$userId,'id'=>$orderId]);

        return boolval($orderActiveRecord);
    }
}
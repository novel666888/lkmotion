<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/9/5
 * Time: 15:15
 */
namespace common\logic\order;

use common\models\OrderPayment;
use common\models\Order;
use common\models\OrderCancelRecord;
use yii\base\UserException;

trait  OrderDetailStatusTrait
{
    /**
     * @param $orderId
     * @return int
     * @throws UserException
     */
    protected function _getOrderState($orderId)
    {
            if(empty($orderId)){
                throw new UserException('Params error!',1001);
            }
            $orderTable = Order::findOne(['id'=>$orderId]);
            if(empty($orderTable)){
                throw new UserException('Order not exist!',1002);
            }
            $orderStatus = (int)$orderTable->status;
            switch ($orderStatus){
                case 1:
                    return $orderTable->is_fake_success == 1? 101:0; //0订单开始
                case 2:
                    return $orderTable->service_type == 1?103:102;
                case 3:
                    return 201;
                case 4:
                    return 202;
                case 5:
                    return 203;
                case 6:
                    return 204;
                case 7:
                    $remainPrice =  OrderPayment::fetchFieldBy(['order_id'=>$orderId],'remain_price');
                    if(!empty($remainPrice)){
                        return 400;
                    }else{
                        return 502;
                    }
                case 8:
                    return $orderTable->is_evaluate == 1?501:502;
                case 9:
                    $orderCancelRecordTable = OrderCancelRecord::getOne(['order_id'=>$orderId],false);
                    $orderPaymentTable  = OrderPayment::findOne(['order_id'=>$orderId]);
                    if(!$orderCancelRecordTable ||  $orderCancelRecordTable->is_charge == 0){
                        return 301;
                    }else{
                        if(!empty($orderPaymentTable) && $orderPaymentTable->remain_price!=0){
                            return 303; //取消后取消费未付完
                        }else{
                            return 302; //支付完成
                        }

                    }
                default:return 0;
            }

            /**
             *
            /**
             * 1.司机抢单成功 ：
            叫车开始未被派车 返回 0
            假派单成功   101
            预约单司机抢单成功  102
            立即叫车成功    103

            2.接乘客
            司机去接乘客 201
            司机到达上车点 202
            乘客已上车 203
            已到达目的地 204

            3.取消
            取消成功 无取消费     301
            取消成功 有取消费     302
            取消成功 未支付有欠款  303   需要加的 9/7

            4.已到达
            支付订单-有尾款      400
            无尾款则支付完成进入  502 状态

            5.订单完成
            已评价 501
            未评价 502

            6.BOSS操作状态  (与发消息相关,与以上状态可能会同时存在 )
            订单调账 601
            订单取消 602
            订单改派 603
            人工派单成功 604
             */

        /**
         * @param $orderId
         * @return int|mixed
         */



    }
}
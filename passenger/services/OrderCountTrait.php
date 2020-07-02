<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/9/6
 * Time: 17:29
 */
namespace passenger\services;
use common\models\OrderPayment;
use common\models\OrderReassignmentRecord;
use common\services\CConstant;
use passenger\models\Order;
use yii\helpers\ArrayHelper;

trait OrderCountTrait
{
    /**
     * @param $userId
     * @param $serviceType
     * @return array
     */
    public function getOrderIdsByServiceTypeId($userId,$serviceType = CConstant::SERVICE_TYPE_REAL_TIME)
    {
        $realTimeOrderIds = Order::find()
            ->where([
                'and',
                ['=', 'service_type', $serviceType],
                ['or',
                    ['between', 'status', Order::STATUS_GRAB, Order::STATUS_ARRIVED],
                    ['and', ['=', 'status', Order::ORDER_START], ['=', 'is_fake_success', Order::IS_FAKE_SUCCESS]]
                ],
                ['=','passenger_info_id',$userId]
            ])
            ->select('id')
            ->column();

        return $realTimeOrderIds;
    }

    /**
     * @param $userId
     * @return array
     */

    public function getUnpaidOrderIdsBefore($userId)
    {
        $unpaidOrderIds = Order::find()->where([
            'and',
            //['or',['=', 'status', Order::STATUS_GATHERING],['=', 'status', Order::STATUS_CANCEL]],
            ['=', 'status', Order::STATUS_GATHERING],
            ['=', 'passenger_info_id', $userId],
            ['=', 'is_paid', Order::IS_PAID_NO],
        ])->select('id')->column();
        $unpaidCancelOrderIds = Order::find()->where([
            'and',
            //['or',['=', 'status', Order::STATUS_GATHERING],['=', 'status', Order::STATUS_CANCEL]],
            ['=', 'status', Order::STATUS_CANCEL],
            ['=', 'passenger_info_id', $userId],
            ['=', 'is_paid', Order::IS_PAID_NO],
        ])->select('id')->column();
        $trueUnpaidCancelOrderIds = [];
        foreach ($unpaidCancelOrderIds as $k=>$v){
            $orderPaymentTable = OrderPayment::findOne(['order_id'=>$v]);
            if($orderPaymentTable && $orderPaymentTable->remain_price!=0){
                array_push($trueUnpaidCancelOrderIds,$v);
            }
        }
        $unpaidOrderIds = ArrayHelper::merge($unpaidOrderIds,$trueUnpaidCancelOrderIds);
        return $unpaidOrderIds;
    }

    /**
     * @param $userId
     * @return array
     */

    public function getUnpaidOrderIds($userId)
    {
        $unpaidOrderIds = Order::find()->where([
            'and',
            //['or',['=', 'status', Order::STATUS_GATHERING],['=', 'status', Order::STATUS_CANCEL]],
            ['or',['=', 'status', Order::STATUS_GATHERING],['=', 'status', Order::STATUS_CANCEL]],
            ['=', 'passenger_info_id', $userId],
            ['=', 'is_paid', Order::IS_PAID_NO],
        ])->select('id')->column();
        $trueUnpaidCancelOrderIds = OrderPayment::find()
            ->where(['order_id'=>$unpaidOrderIds])
            ->andWhere(['<>','remain_price','0.00'])
            ->select('order_id')
            ->column();
        return $trueUnpaidCancelOrderIds;
    }


    /**
     * 获取某用户所有的未完成的有效订单
     *
     * @param $userId
     * @return array
     */

    public function getUserOrderIds($userId)
    {
        $allOrderIds = Order::find()
            ->where([
                'and',
                ['or',
                    ['between', 'status', Order::STATUS_GRAB, Order::STATUS_ARRIVED],
                    ['and', ['=', 'status', Order::ORDER_START], ['=', 'is_fake_success', Order::IS_FAKE_SUCCESS]]
                ],
                ['=','passenger_info_id',$userId]
            ])
            ->select('id')
            ->column();

        return $allOrderIds;
    }

    /**
     * 用户的小于下单限额未充值且正在服务中的订单数量
     *
     * @param $userId
     * @return int
     */

    public function getLessThanRiskOrder($userId)
    {
        $lessThenRiskOrders = Order::find()
            ->where([
                'and',
                ['or',
                    ['between', 'status', Order::STATUS_GRAB, Order::STATUS_ARRIVED],
                    ['and', ['=', 'status', Order::ORDER_START], ['=', 'is_fake_success', Order::IS_FAKE_SUCCESS]]
                ],
                ['=','passenger_info_id',$userId],
                ['=','is_use_risk',Order::IS_USE_RISK]
            ])
            ->select('id')
            ->asArray()
            ->all();

        return count($lessThenRiskOrders);
    }


}
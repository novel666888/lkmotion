<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/16
 * Time: 14:11
 */

namespace application\modules\order\models;

use application\modules\order\components\PhoneNumber;
use common\models\DriverInfo;
use common\models\OrderDoubt as OrderDoubtBoss;
use common\services\traits\ModelTrait;
use common\util\Common;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class OrderDoubt extends OrderDoubtBoss
{
    use ModelTrait;

    /**
     * get doubt orders
     *
     * @param $where
     * @return array
     */

    public static function getDoubtOrders($where)
    {
        /** cabRunnerPhone driverPhone	orderNum */
        $returnCols = [
            'id'            => 'id',
            'orderId'       => 'order_id',
            'appealNum'     => 'appeal_number',
            'submitTime'    => 'create_time',
            'reasonType'    => 'reason_type',
            'reasonText'    => 'reason_text',
            'adjustType'    => 'adjust_type',
            'adjustContent' => 'adjust_content',
            'oldCost'       => 'old_cost',
            'nowCost'       => 'now_cost',
            'status'        => 'status',
            'handleType'    => 'handle_type',
            'solution'      => 'solution',
            'operators'     => 'operators',
            'lastOperator'  => new Expression('space(-1)'),
            'operateTime'   => 'operate_time',
        ];

        $activeQuery = self::find()->select($returnCols);
        if(!empty($where['orderNum']) || !empty($where['cabRunnerPhone']) || !empty($where['carManPhone'])){
            $query_order_boss = OrderBoss::find()->select('id');
            if(!empty($where['orderNum'])){
                $query_order_boss->andWhere(['order_number'=>$where['orderNum']]);
            }
            if(!empty($where['cabRunnerPhone'])){
                $phone_encrypt = Common::phoneEncrypt($where['cabRunnerPhone']);
                $query_order_boss->andWhere(['other_phone'=>$phone_encrypt]);
            }
            if(!empty($where['carManPhone'])){
                $phone_encrypt = Common::phoneEncrypt($where['carManPhone']);
                $query_order_boss->andWhere(['driver_phone'=>$phone_encrypt]);
            }
            $order_ids = $query_order_boss->asArray()->column();
            $activeQuery->andWhere(['order_id'=>$order_ids]);
        }

        $result      = self::getPagingData($activeQuery, ['create_time' => SORT_DESC]);
        if ($result['data']['list']) {
            $orderIds  = ArrayHelper::getColumn($result['data']['list'], 'orderId', false);
            $orderIds  = array_unique($orderIds);
            $orderData = OrderBoss::find()->where(['id' => $orderIds])->select([
                'orderId'        => 'id',
                'orderType'      => 'order_type',
                'orderNum'       => 'order_number',
                'cabRunner'      => 'other_name',
                'cabRunnerPhone' => 'other_phone',
                'driverId'       => 'driver_id',
                'driverPhone'    => 'driver_phone',
                'driver'         => new Expression('space(-1)'),
            ])->indexBy('orderId')->asArray()->all();

            $operatorIds = ArrayHelper::getColumn($result['data']['list'], 'operators', false);
            $operatorIds = Common::getUniqueAndNotEmptyValueFromArray($operatorIds);
            $operatorNames = SysUser::find()
                ->where(['id'=>$operatorIds])
                ->select(['id','username'])
                ->indexBy('id')
                ->asArray()
                ->all();
            if ($orderData) { //query driver name
                $driverIds  = ArrayHelper::getColumn($orderData, 'driverId', false);
                $driverIds  = array_unique($driverIds);
                $driverData = DriverInfo::find()->where(['id' => $driverIds])->select([
                    'driverId'   => 'id',
                    'driverName' => 'driver_name'
                ])->indexBy('driverId')->asArray()->all();
                if ($driverData) {
                    foreach ($orderData as $k => $v) {
                        if (!empty($v['driverId'])) {
                            $orderData[$k]['driver'] = $driverData[$v['driverId']]['driverName'];
                        }

                    }
                }
            }

            foreach ($result['data']['list'] as $k => $v) {
                $result['data']['list'][$k] = ArrayHelper::merge($result['data']['list'][$k], (array)$orderData[$v['orderId']]);
                if(empty($result['data']['list'][$k]['operators'])){
                    $result['data']['list'][$k]['lastOperator'] = '乘客';
                }else{
                    $result['data']['list'][$k]['lastOperator'] = $operatorNames[$result['data']['list'][$k]['operators']]['username'];
                }
            }
            if(!empty($result['data']['list'])) {
                $lists =$result['data']['list'];
                $newList = PhoneNumber::mappingCipherToPhoneNumber($lists,['driverPhone','cabRunnerPhone']);
                $result['data']['list'] = $newList;
            }

        }
        return $result;

        /**
         *  "id":1,
         * "orderId":34,
         * "orderType":1,//订单类型 1自已叫车，2为他人叫车
         * "appealNum":13,//申诉单号
         * "orderNum":"201808101447324",//订单号
         * "cabRunner":"张三",
         * "cabRunnerPhone":"1308324134",
         * "drive":"李四",
         * "drivePhone":"司机电话",
         * "submitTime":"2018-08-10 12:31:43",//提交时间
         * "reasonType":1,//疑义原因id
         * "reasonText":"其他原因",//
         * "adjustType":1,//调账类型,1充值 2扣款 3发券
         * "adjustContent":"1341834",//充值|扣款单号 |券名称
         * "oldCost":100.05,//调账前金额
         * "nowCost":123.00,//调账后金额
         * "status":1, //当前状态 1待处理，2，3，4，5
         * "handleType":1,//1充值，2扣款，3，发券
         * "solution":"处理方案",///处理方案
         * "lastOperator":"李四",
         * "operateTime":"2018-08-10 13:23:34"//操作时间
         */
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/29
 * Time: 12:10
 */
namespace common\logic\order;

use common\models\Order;
use yii\base\UserException;
use yii\helpers\ArrayHelper;

/**
 * get passenger trail
 *
 * Trait OrderPassengerTrailTrait
 * @package common\logic\order
 * @author  zzr
 * @since  2018/8/29
 */
trait OrderOutputServiceTrait
{
    public static function getPassengerTrail($orderId)
    {
        $orderTable = Order::findOne((int)$orderId);
        if(!$orderTable){
            throw new UserException('Not exist order');
        }
        return [
            'driverlng'=>$orderTable->pick_up_passenger_longitude,//车的经度 接到乘客的经度
            'driverLat'=>$orderTable->pick_up_passenger_latitude,//车的纬度 接到乘客的纬度
            'startLng'=>$orderTable->user_longitude,//乘客下单起点经度
            'startLat'=>$orderTable->user_latitude,//乘客下单起点纬度
            'endLng'=>$orderTable->passenger_getoff_longitude,//乘客下单终点经度
            'endLat'=>$orderTable->passenger_getoff_latitude,//乘客下单终点纬度
            'endAddress'=>$orderTable->end_address,//乘客终点名称
        ];
    }

    /**
     * @param $passengerId
     * @return array|bool
     * @throws UserException
     */

    public static function getUnderWayOrderCarId($passengerId)
    {
        if(!is_numeric($passengerId) || empty($passengerId)){
            throw new UserException('Parameter error!');
        }
        $orders = Order::find()
            ->where(['passenger_info_id'=>(int)$passengerId])
            ->andWhere(['between','status',Order::STATUS_GET_ON,Order::STATUS_GATHERING])
            ->select('id,car_id,passenger_phone')
            ->asArray()
            ->all();
        $carIds = ArrayHelper::getColumn($orders,'car_id',[]);

        return empty($carIds)?(bool)$carIds:$carIds;
    }
}
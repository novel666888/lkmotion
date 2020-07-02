<?php

namespace common\listeners;

//use common\jobs\PushCoupon;
use common\models\Order;
use common\logic\Passenger;
use yii\base\UserException;

class ShareTrip extends \yii\base\Component
{
    public static function sendSms($event)
    {
        try {
            $orderId = $event->orderId;
            if(empty($orderId)){
                return;
            }
            $orderInfo = Order::findOne(['id' => $orderId]);
            if(!isset($orderInfo->passenger_info_id)){
                return;
            }
            $run = new Passenger($orderInfo->passenger_info_id);
            $run->emergencyContact();
        } catch (UserException $e) {
            \Yii::debug($e, 'ShareTrip_sendSms');
            return;
        }
    }
}
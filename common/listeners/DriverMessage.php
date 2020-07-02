<?php

namespace common\listeners;

use common\models\Order;
use common\util\Common;
use common\util\SmsTpl;
use yii\base\UserException;

class DriverMessage extends \yii\base\Component
{
    // 发送app消息
    public static function sendAppMessage($event)
    {
        $driverId = $event->identity;
        $action = $event->extInfo;

        \Yii::debug(['driverId' => $driverId, 'action' => $action], 'event_send_app_message');

    }

    // 发送短消息
    public static function sendSmsMessage($event)
    {
        $driverId = $event->identity;
        $action = $event->extInfo;

        \Yii::debug(['driverId' => $driverId, 'action' => $action], 'event_send_sms_message');
    }

    /**
     * @param $event
     * @return array|bool|mixed
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */
    public static function sendArrivedSms($event)
    {
        $orderInfo = Order::findOne(['id' => $event->orderId]);
        if (!$orderInfo || $orderInfo->order_type != 2 || !$orderInfo->other_phone) {
            return false;
        }
        $tpl = 'HX_0006';
        try {
            $phone = Common::decryptCipherText($orderInfo->other_phone);
            $sendPhone = array_values($phone);
        } catch (UserException $e) {
            \Yii::debug($e, 'decryptPhone');
            return false;
        }
        $params = (new SmsTpl())->fillSmsParams($orderInfo, $tpl);
        if (is_string($params)) {
            $message = $params . ':' . $tpl;
            if (PHP_SAPI == 'cli') {
                echo $message;
            } else {
                \Yii::debug($message, 'fill_sms_tpl');
            }
            return false;
        }
        // 发送短信
        return Common::sendMessageNew($sendPhone[0], $tpl, $params);
    }

}
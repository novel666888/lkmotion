<?php

namespace common\listeners;

use common\models\PassengerInfo;
use common\util\Common;
use yii\base\UserException;

class PassengerMessage extends \yii\base\Component
{
    /**
     * @param $event
     * @return array|bool|mixed
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */
    public static function sendRegCouponSms($event)
    {
        $userInfo = PassengerInfo::findOne(['id' => $event->identity]);
        if (!$userInfo) {
            return false;
        }
        $tpl = 'HX_0021';
        try {
            $phone = Common::decryptCipherText($userInfo->phone);
            $sendPhone = array_values($phone);
        } catch (UserException $e) {
            \Yii::debug($e, 'decryptPhone');
            return false;
        }
        // 发送短信
        return Common::sendMessageNew($sendPhone[0], $tpl, ['coupon_name' => '新用户']);
    }

}
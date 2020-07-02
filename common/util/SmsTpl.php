<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/15
 * Time: 15:37
 */

namespace common\util;


use common\models\CarInfo;
use common\models\Order;
use passenger\models\PassengerInfo;
use yii\base\UserException;

class SmsTpl
{
    public function fillSmsParams($orderInfo, $tpl)
    {
        // 基础信息检测
        if ($orderInfo->status < 3 || !$orderInfo->car_id || !$orderInfo->passenger_info_id) {
            return '订单信息异常';
        }
        if ($tpl == 'HX_0006') {
            return $this->fillArrived($orderInfo);
        }
        return '未知处理器';
    }

    private function fillArrived($orderInfo)
    {
        $carInfo = CarInfo::findOne(['id' => $orderInfo->car_id]);
        $passengerInfo = PassengerInfo::findOne(['id' => $orderInfo->passenger_info_id]);
        try {
            $phone = Common::decryptCipherText($passengerInfo->phone);
            $userPhone = array_values($phone);
        } catch (UserException $e) {
            return '用户手机号解析失败';
        }
        $params = [
            'passenger_name' => $passengerInfo ? $passengerInfo->passenger_name : '好友',
            'phone' => $userPhone[0] ?? '',
            'plate_number' => $carInfo ? $carInfo->plate_number : '未知',
            'color' => $carInfo ? $carInfo->color : '其他',
            'start_address' => $orderInfo->start_address,
        ];
        return $params;
    }

}
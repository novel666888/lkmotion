<?php
/**
 * Created by PhpStorm.
 * User: xujie
 * Date: 18-8-24
 * Time: 下午2:26
 */

namespace common\events;

use yii\base\Component;

class OrderEvent extends Component
{
    const EVENT_GRAB_RESULT = 'grabResult'; // 抢单结果
    const EVENT_GO_PICKUP = 'goPickup';  // 去接乘客
    const EVENT_ARRIVED = 'arrived';  // 到达上车点
    const EVENT_START_ORDER = 'startOrder';  // 开始行程
    const EVENT_END_ORDER = 'endOrder';  // 结束行程
    const EVENT_DRIVER_START_PAY = 'driverStartPay';  // 司机发起收款
    const EVENT_PAY_SUCCESS = 'paySuccess';  // 乘客支付成功
    const EVENT_ORDER_CANCEL = 'orderCancel'; // 订单取消
    const EVENT_FORCE_PUSH_ORDER = 'forcePushOrder'; // 强派单
    const EVENT_ADJUST_ORDER = 'adjustOrder'; // 调账结果


    public function grabResult($data)
    {
        $event = $this->packData($data);
        $this->trigger(self::EVENT_GRAB_RESULT, $event);
    }

    public function goPickup($data)
    {
        $event = $this->packData($data);
        $this->trigger(self::EVENT_GO_PICKUP, $event);
    }

    public function arrived($data)
    {
        $event = $this->packData($data);
        $this->trigger(self::EVENT_ARRIVED, $event);
    }

    public function startOrder($data)
    {
        $event = $this->packData($data);
        $this->trigger(self::EVENT_START_ORDER, $event);
    }

    public function endOrder($data)
    {
        $event = $this->packData($data);
        $this->trigger(self::EVENT_END_ORDER, $event);
    }

    public function driverStartPay($data)
    {
        $event = $this->packData($data);
        $this->trigger(self::EVENT_DRIVER_START_PAY, $event);
    }

    public function paySuccess($data)
    {
        $event = $this->packData($data);
        $this->trigger(self::EVENT_PAY_SUCCESS, $event);
    }

    public function orderCancel($data)
    {
        $event = $this->packData($data);
        $this->trigger(self::EVENT_ORDER_CANCEL, $event);
    }

    public function forcePushOrder($data)
    {
        $event = $this->packData($data);
        $this->trigger(self::EVENT_FORCE_PUSH_ORDER, $event);
    }

    public function adjustOrder($data)
    {
        $event = $this->packData($data);
        $this->trigger(self::EVENT_ADJUST_ORDER, $event);
    }

    private function packData($data)
    {
        $dataPack = new DataPack();
        $dataPack->identity = isset($data['identity']) ? $data['identity'] : '';
        $dataPack->orderId = isset($data['orderId']) ? $data['orderId'] : '';
        $dataPack->extInfo = isset($data['extInfo']) ? $data['extInfo'] : '';

        return $dataPack;
    }
}
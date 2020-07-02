<?php
/**
 * Created by PhpStorm.
 * User: xujie
 * Date: 18-8-24
 * Time: 下午2:26
 */

namespace common\events;

use yii\base\Component;

class PassengerEvent extends Component
{
    const EVENT_REGISTER = 'register'; // 注册
    const EVENT_CONSUMPTION = 'consumption';  // 消费
    const EVENT_CHARGE = 'charge';  // 充值

    public function register($passengerId)
    {
        $event = $this->packData($passengerId, self::EVENT_REGISTER);
        $this->trigger(self::EVENT_REGISTER, $event);
    }

    public function consumption($passengerId)
    {
        $event = $this->packData($passengerId, self::EVENT_CONSUMPTION);
        $this->trigger(self::EVENT_CONSUMPTION, $event);
    }

    public function charge($passengerId)
    {
        $event = $this->packData($passengerId, self::EVENT_CHARGE);
        $this->trigger(self::EVENT_CHARGE, $event);
    }

    private function packData($passengerId, $evt)
    {
        $dataPack = new DataPack();
        $dataPack->identity = $passengerId;
        $dataPack->extInfo = $evt;

        return $dataPack;
    }
}
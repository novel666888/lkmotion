<?php

namespace common\logic;

use common\models\Order;
use common\models\PassengerInfo;
use common\models\PassengerWalletRecord;

/**
 * Trait PeopleTagTrait
 * @package common\logic
 */
trait PeopleTagTrait
{

    /**
     * 根据条件获取司机ID
     * @param bool $conditions
     * @return array
     */
    public function filterPassengerIds($conditions = false)
    {
        $query = PassengerInfo::find()->select('id');
        if (isset($conditions->regStart)) {
            $query->where(['>', 'register_time', date('Y-m-d', strtotime($conditions->regStart))]);
        }
        if (isset($conditions->regEnd)) {
            $query->andWhere(['<', 'register_time', date('Y-m-d', strtotime($conditions->regEnd) + 86400)]);
        }
        $result = $query->asArray()->all();
        $passengerIds = array_column($result, 'id');
        if (!$passengerIds) {
            return $passengerIds;
        }
        // 消费金额过滤
        if (isset($conditions->minConsumption) && $conditions->minConsumption > 0) {
            $this->amountFilter($passengerIds, 2, $conditions->minConsumption); // 2消费
        }
        if (!$passengerIds) {
            return $passengerIds;
        }
        // 充值金额过滤
        if (isset($conditions->minCharge) && $conditions->minCharge > 0) {
            $this->amountFilter($passengerIds, 1, $conditions->minCharge); // 1充值
        }
        if (!$passengerIds) {
            return $passengerIds;
        }
        // 预约单数量过滤
        if (isset($conditions->minOppointments) && $conditions->minOppointments > 0) {
            $this->orderFilter($passengerIds, 2, $conditions->minOppointments); // 2预约单
        }
        if (!$passengerIds) {
            return $passengerIds;
        }
        // 实时单数量过滤
        if (isset($conditions->minRealTime) && $conditions->minRealTime > 0) {
            $this->orderFilter($passengerIds, 1, $conditions->minRealTime); // 1实时单
        }
        return $passengerIds;
    }

    /**
     * 金额限制
     * @param $passengerIds
     * @param $tradeType
     * @param $amount
     */
    private function amountFilter(&$passengerIds, $tradeType, $amount)
    {
        $query = PassengerWalletRecord::find()->select('passenger_info_id,trade_type,sum(pay_capital) as amount');
        $passengerGroups = $query->where(['in', 'passenger_info_id', $passengerIds])
            ->andWhere(['trade_type' => $tradeType])
            ->andWhere(['>=', 'total', $amount])
            ->groupBy('passenger_info_id')
            ->asArray()->all();
        $passengerIds = array_column($passengerGroups, 'passenger_info_id');
    }

    /**
     * @param $passengerIds
     * @param $serviceType
     * @param $number
     */
    private function orderFilter(&$passengerIds, $serviceType, $number)
    {
        $query = Order::find()->select('passenger_info_id,service_type,count(id) as number');
        $query->where(['status' => 8]); // 已经完成订单
        $passengerGroups = $query->andWhere(['in', 'passenger_info_id', $passengerIds])
            ->andWhere(['service_type' => $serviceType])
            ->andWhere(['>=', 'number', $number])
            ->groupBy('passenger_info_id')
            ->asArray()->all();
        $passengerIds = array_column($passengerGroups, 'passenger_info_id');
    }
}
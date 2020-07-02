<?php

namespace common\logic;

use common\models\Activities;
use common\models\ActivitiesLogic;

/**
 * Trait CouponTrait
 * @package common\logic
 */
trait FeeTrait
{
    protected $feeActivity = null;
    protected $feeCounter = null;

    /**
     * 根据乘客ID和金额, 获取充增金额
     * @param $passengerId
     * @param int $amount
     * @return float
     */
    public function getGiveFee($passengerId, $amount = 0)
    {
        // 检查充赠活动
        $feeBack = $this->getActiveFeeActivity();
        if (!$feeBack) {
            return 0.0;
        }
        // 检查是否赠费次数
        $counter = $this->getSetCounter($passengerId);
        if (!$counter) {
            return 0.0;
        }
        // 计算赠费
        $chargeSet = json_decode($feeBack->bonuses_rule);
        $max = 0.0;
        foreach ($chargeSet as $item) {
            if ($amount >= $item->recharge && $item->give > $max) {
                $max = round(floatval($item->give), 2);
            }
        }
        return $max;
    }

    /**
     * 设置充增标记
     * @param $passengerId
     * @param $outTradeNo
     * @return bool
     */
    public function markGiveFee($passengerId, $outTradeNo)
    {
        if (!$passengerId || !$outTradeNo || !is_string($outTradeNo)) {
            return false;
        }
        $activity = $this->getActiveFeeActivity();
        if (!$activity) {
            return false;
        }
        $logic = new ActivitiesLogic();
        $logic->activity_id = $activity->id;
        $logic->passenger_id = $passengerId;
        $logic->ext_bonuses = $outTradeNo;

        return $logic->save();
    }

    /**
     * 根据tradeNo清楚标记(当充值失败的时候)
     * @param $passengerId
     * @param $outTradeNo
     * @return bool|int
     */
    public function clearGiveMark($passengerId, $outTradeNo)
    {
        if (!$outTradeNo || !is_string($outTradeNo)) {
            return false;
        }
        return ActivitiesLogic::deleteAll(['passenger_id' => $passengerId, 'ext_bonuses' => $outTradeNo]);
    }

    /**
     * 获取最新的充增活动
     * @return array|null|\yii\db\ActiveRecord
     */
    private function getActiveFeeActivity()
    {
        if ($this->feeActivity !== null) {
            return $this->feeActivity;
        }
        $feeBack = Activities::find()
            ->where(['<=', 'enable_time', date('Y-m-d H:i')])
            ->andWhere(['>', 'expire_time', date('Y-m-d H:i:s')])
            ->andWhere(['activity_type' => 2])// 充赠
            ->andWhere(['status' => 1])
            ->orderBy('create_time DESC')
            ->limit(1)
            ->one();
        $this->feeActivity = $feeBack;
        return $feeBack;
    }

    /**
     * 获取是否有充赠
     * @param $passengerId
     * @param $activity
     * @return bool
     */
    private function getCounter($passengerId)
    {
        $activity = $this->getActiveFeeActivity();
        if (!$activity) {
            return false;
        }
        // 不限制次数, 则返回有次数
        if ($activity->join_cycle == 'nil') {
            return true;
        }
        // 获取本活动最后一次
        $lastGiveTime = ActivitiesLogic::find()
            ->select('create_time')
            ->where(['passenger_id' => $passengerId])
            ->andWhere(['activity_id' => $activity->id])
            ->orderBy('create_time DESC')
            ->limit(1)->scalar();
        if (!$lastGiveTime) {
            return true;
        }
        // 自然日算法
        if ($activity->join_cycle == 'day') { // 每天
            return date('Y-m-d') > substr($lastGiveTime, 0, 10);
        } elseif ($activity->join_cycle == 'month') { // 每月
            return date('Y-m') > substr($lastGiveTime, 0, 7);
        } elseif ($activity->join_cycle == 'week') { // 每周
            return (date('W') > date('W', strtotime($lastGiveTime))) ? true :
                date('Y') != substr($lastGiveTime, 0, 4);
        } elseif ($activity->join_cycle == 'once') { // 一次
            return false;
        }
        // 间隔算法
//        if (in_array($activity->join_cycle, ['day', 'week', 'month'])) {
//            return time() > strtotime('+1 ' . $activity->join_cycle, strtotime($lastGiveTime));
//        }
        return false;
    }

    /**
     * 获取并设置费用标识(性能优化)
     * @param $passengerId
     * @return bool|null
     */
    private function getSetCounter($passengerId)
    {
        if ($this->feeCounter !== null) {
            return $this->feeCounter;
        }
        $result = $this->getCounter($passengerId);
        $this->feeCounter = $result ? 1 : 0;
        return $this->feeCounter;
    }
}
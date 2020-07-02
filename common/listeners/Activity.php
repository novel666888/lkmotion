<?php

namespace common\listeners;

use common\jobs\PushCoupon;
use common\models\Activities;
use common\models\InviteRecord;
use common\models\PassengerInfo;

class Activity extends \yii\base\Component
{
    protected static $debug = false;
    protected static $activity = null; // 选中活动
    protected static $inviteRecord = null; // 邀请记录

    /**
     * 处理 拉新-注册
     * @param $event
     */
    public static function passengerRegister($event)
    {
        // 返券触发检测
        self::checkReward($event);
        // 拉新触发检测
        self::checkInvite($event);
    }

    /**
     * 处理 拉新-消费
     * @param $event
     */
    public static function passengerConsumption($event)
    {
        $inviteInfo = self::getInviteRecord($event->identity);
        if (!$inviteInfo || $inviteInfo->consumption_time > '2000') { // 消费过了, 不再重复发送
            return;
        }
        $activities = self::getActivities(3);
        if (!$activities) {
            return;
        }
        // 发送优惠券
        $result = false;
        foreach ($activities as $activity) {
            $couponList = self::getCouponList($activity, 2); // 2 消费
            if ($couponList) {
                $result = self::pushCoupon($couponList, $inviteInfo->invite_passenger_id);
            }
        }
        if ($result) {
            $inviteInfo->consumption_time = date('Y-m-d H:i:s');
            $inviteInfo->save();
        }
    }

    /**
     * 用户充值标记
     * @param $event
     * @return bool
     */
    public static function passengerCharge($event)
    {
        $inviteInfo = self::getInviteRecord($event->identity);
        if(!$inviteInfo || $inviteInfo->charge_time > '2000') {
            return true;
        }
        $inviteInfo->charge_time = date('Y-m-d H:i:s');
        return $inviteInfo->save();
    }

    /**
     * @param $event
     */
    private static function checkInvite($event)
    {
        $inviteInfo = self::getInviteRecord($event->identity);

        if (!$inviteInfo || $inviteInfo->active_time > '2000') { // 已经激活过了, 不再发送优惠券
            return;
        }
        $activities = self::getActivities(3);
        if (!$activities) {
            return;
        }
        // 发送优惠券
        $result = false;
        foreach ($activities as $activity) {
            $couponList = self::getCouponList($activity, 1); // 1注册
            if ($couponList) {
                $result = self::pushCoupon($couponList, $inviteInfo->invite_passenger_id);
            }
        }
        if ($result) {
            $inviteInfo->active_time = date('Y-m-d H:i:s');
            $inviteInfo->save();
        }
    }

    /**
     * @param $event
     */
    private static function checkReward($event)
    {
        $activities = self::getActivities(1);
        if (!$activities) {
            return;
        }
        foreach ($activities as $activity) {
            $couponList = self::getCouponList($activity);
            if ($couponList) {
                self::pushCoupon($couponList, $event->identity);
            }
        }
    }

    /**
     * @param $couponList
     * @param $userId
     * @return bool
     */
    public static function pushCoupon($couponList, $userId)
    {
        self::$debug && \Yii::debug(json_encode(['couponList' => $couponList, 'userId' => $userId]), 'attach_coupon');

        if (!$couponList || !$userId) {
            return false;
        }
        $userInfo = PassengerInfo::findOne(['id' => $userId]);
        if (!$userInfo) {
            return false;
        }
        $taskData = [
            'userId' => $userId,
            'couponList' => $couponList,
            'activityInfo' => [
                'activity_tag' => self::$activity->activity_no,
                'activity_id' => self::$activity->id,
            ],
        ];
        // 给乘客发送优惠券
        \Yii::$app->queue->push(new PushCoupon($taskData));
        return true;
    }

    /**
     * @param $activity
     * @param int $node
     * @return array
     */
    private static function getCouponList($activity, $node = 0)
    {
        self::$activity = $activity;
        // 检测优惠券设置
        $couponRule = json_decode($activity->bonuses_rule);
        if (!$couponRule) {
            $info = new \stdClass();
            $info->activity_type = $activity->activity_type;
            $info->bonuses_rule = $activity->bonuses_rule;
            if (PHP_SAPI == 'cli') {
                echo json_encode($info, 256);
            } else {
                \Yii::debug($info, 'activity_config_error');
            }
            return [];
        }
        if ($activity->activity_type == 3) { // 拉新
            $coupons = [];
            foreach ($couponRule as $group) {
                if ($group->rewardType == 1 && $group->newType == $node) {
                    $coupons = $group->coupons;
                }
            }
            return $coupons;
        } elseif ($activity->activity_type == 1) { // 返券
            return $couponRule->coupons;
        }
        return [];
    }

    /**
     * 获取正在进行的活动
     * @param $type
     * @return array|\yii\db\ActiveRecord[]
     */
    private static function getActivities($type)
    {
        $now = date('Y-m-d H:i:s');
        $activities = Activities::find()
            ->where(['activity_type' => $type])// 返券
            ->andWhere(['<=', 'enable_time', $now])// 起效时间
            ->andWhere(['>', 'expire_time', $now])// 过期时间
            ->all();
        self::$debug && \Yii::debug($activities, 'attach_activity');
        return $activities;
    }

    /**
     * 获取活动记录(优化)
     * @param $passengerId
     * @return InviteRecord|null
     */
    private static function getInviteRecord($passengerId)
    {
        if (self::$inviteRecord !== null) {
            return self::$inviteRecord;
        }
        $inviteRecord = InviteRecord::findOne(['passenger_id' => $passengerId]);
        self::$inviteRecord = $inviteRecord;
        return $inviteRecord;
    }

}
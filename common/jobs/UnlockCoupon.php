<?php
/**
 * 解锁优惠券任务
 * 锁定的时候, 被延迟推送入队列, 代替crontabe任务, 提高优惠券解锁时间实时性
 */

namespace common\jobs;

use common\models\Order;
use common\models\UserCoupon;
use yii\base\BaseObject;

class UnlockCoupon extends BaseObject implements \yii\queue\JobInterface
{
    public $userCouponId;  //用户优惠券ID

    public function execute($queue)
    {
        $userCoupon = UserCoupon::findOne(['id' => $this->userCouponId]);
        // 检测优惠券状态
        if (!$userCoupon || !$userCoupon->order_id) {
            return true;
        }
        // 检测订单状态 如果订单存在 并且 (派单/假派单)成功, 则不解锁
        $order = Order::findOne(['id' => intval($userCoupon->order_id)]);
        if ($order && ($order->driver_id > 0 || $order->is_fake_success > 0)) {
            return true;
        }
        // 解锁优惠券
        $userCoupon->order_id = '';
        return $userCoupon->save();
    }
}
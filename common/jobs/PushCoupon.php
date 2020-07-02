<?php
/**
 * 推送优惠券任务
 */

namespace common\jobs;

use common\models\Coupon;
use yii\base\BaseObject;

class PushCoupon extends BaseObject implements \yii\queue\JobInterface
{
    public $userId;  //用户优惠券ID
    public $couponList; // 优惠券配置列表
    public $activityInfo; // 活动信息

    public function execute($queue)
    {
        // 检查数据
        if (!is_array($this->couponList) || !$this->userId) {
            return;
        }
        // 发送优惠券
        foreach ($this->couponList as $coupon) {
            for ($i = 0, $l = $coupon->number ?? 0; $i < $l; $i++) {
                Coupon::pushOneCoupon($this->userId, $coupon->id, $this->activityInfo);
            }
        }
        return;
    }
}
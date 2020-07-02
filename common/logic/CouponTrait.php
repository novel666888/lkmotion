<?php

namespace common\logic;

use common\jobs\UnlockCoupon;
use common\models\Coupon;
use common\models\CouponConditions;
use common\models\Order;
use common\models\OrderRulePrice;
use common\models\OrderUseCoupon;
use common\models\UserCoupon;
use common\util\Common;

/**
 * Trait CouponTrait
 * @package common\logic
 */
trait CouponTrait
{
    protected $delay = 121; // 优惠券自动解绑时间

    /**
     * 获取用户当前可用的优惠券
     * @param $userId
     * @param int $price
     * @param bool $otherInfo
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getCanUseCoupons($userId, $price = 0, $otherInfo = false)
    {
        $query = UserCoupon::find()
            ->select('id,coupon_id,coupon_name,coupon_type,reduction_amount,minimum_amount,discount,expire_time')
            ->where(['passenger_id' => $userId])
            ->andWhere(['order_id' => ''])// 未锁定
            ->andWhere(['is_use' => 0]);
        $expire = $otherInfo['orderStartTime'] ?? date('Y-m-d H:i:s');
        $query->andWhere(['>', 'expire_time', $expire]);
        if ($price) {
            $query->andWhere(['or', ['<=', 'minimum_amount', $price], ['minimum_amount' => 0]]);
        }
        $coupons = $query->orderBy('expire_time ASC')->asArray()->all();
        if (!$coupons) {
            return $coupons;
        }
        $couponIds = array_column($coupons, 'coupon_id');
        $conditions = CouponConditions::find()->where(['coupon_id' => $couponIds])->indexBy('coupon_id')->all();
        foreach ($coupons as $key => $item) {
            if (!isset($conditions[$item['coupon_id']])) { // 无使用规则,不受限
                continue;
            }
            $result = $this->checkCondition($conditions[$item['coupon_id']], $otherInfo);
            if (!$result) {
                unset($coupons[$key]);
            }
        }
        return $coupons;
    }

    /**
     * 获取用户最大的优惠券ID和金额
     * @param $userId
     * @param $orderId
     * @return \stdClass
     */
    public function getOrderMaxCoupon($userId, $orderId)
    {
        $result = $this->getCouponObj();

        $query = OrderRulePrice::find()->where(['order_id' => $orderId])->limit(1);
        $useConditions = $query->select('total_price,city_code,car_level_id,service_type_id')
            ->orderBy('create_time desc')->asArray()->one(); // 获取最新的
        if (!$useConditions) {
            return $result;
        }
        $otherInfo = Common::key2lowerCamel($useConditions);
        $price = $otherInfo['totalPrice'];
        // 获取订单开始时间
        $orderStartTime = Order::find()->where(['id' => $orderId])->select('order_start_time')->limit(1)->scalar();
        if ($orderStartTime > '1') {
            $otherInfo['orderStartTime'] = $orderStartTime;
        }
        $coupons = $this->getCanUseCoupons($userId, $price, $otherInfo);
        if (!$coupons) {
            return $result;
        }
        // 根据过期时间分组优惠券,并返回第一组
        $useGroup = $this->getFirstGroup($coupons);
        // 获取一组优惠券中抵扣金额最大的优惠券
        $result = $this->getMaxCoupon($useGroup, $price);
        return $result;
    }

    /**
     * @param Order $order
     * @param $price
     * @return \stdClass
     */
    public function useOrderCoupon(Order $order, $price)
    {
        $result = $this->getCouponObj();
        // 获取锁定优惠券
        $userCoupon = UserCoupon::findOne(['order_id' => strval($order->id), 'is_use' => 0]);
        if (!$userCoupon) {
            return $result;
        }
        // 检测金额是否符合
        if ($userCoupon->minimum_amount > $price) {
            $this->unlockOrderCoupon($order->id);
            $userCoupon = null;
        }
        // 尝试获取新的优惠券
        if (!$userCoupon) {
            $userCoupon = $this->getOrderMaxCoupon($order->passenger_info_id, $order->id);
        }
        if (!$userCoupon) {
            return $result;
        }
        $result->userCouponId = $userCoupon->id;
        $result->maxAmount = $userCoupon->reduction_amount;

        return $result;
    }

    /**
     * 锁定订单优惠券
     * @param $orderId
     * @param int $userCouponId
     * @return bool
     */
    public function lockOrderCoupon($orderId, $userCouponId = 0)
    {
        $userCouponId = intval($userCouponId);
        if (!$userCouponId) {
            $orderCoupon = OrderUseCoupon::findOne(['order_id' => intval($orderId)]);
            if (!$orderCoupon) {
                return false;
            }
            $userCouponId = $orderCoupon->coupon_id;
        }
        $userCoupon = UserCoupon::findOne(['id' => $userCouponId]);
        if (!$userCoupon) {
            return false;
        }
        // 锁定优惠券
        $userCoupon->order_id = strval($orderId);
        $result = $userCoupon->save();
        if (!$result) {
            return false;
        }
        // 延迟后自动解锁
        \Yii::$app->queue->delay($this->delay)->push(new UnlockCoupon(compact('userCouponId')));
        return true;
    }

    /**
     * 解锁订单优惠券
     * @param $orderId
     * @return bool|int
     */
    public function unlockOrderCoupon($orderId)
    {
        $orderId = strval($orderId);
        if (!$orderId) {
            return false;
        }
        return UserCoupon::updateAll(['order_id' => ''], ['order_id' => $orderId]);
    }

    /**
     * @param $userCouponId
     * @param $orderTotalPrice [订单最终支付金额]
     * @param string $orderId
     * @return bool|float|string
     */
    public function useUserCoupon($userCouponId, $orderTotalPrice, $orderId = '')
    {
        $userCoupon = UserCoupon::findOne(['id' => $userCouponId, 'is_use' => 0]);
        if (!$userCoupon) {
            return false;
        }
        $userCoupon->is_use = 1;
        $userCoupon->use_time = date('Y-m-d H:i:s');
        if ($orderId) {
            $userCoupon->order_id = strval($orderId);
        }
        // 计算实际减免金额
        $coupon = Coupon::findOne(['id' => $userCoupon->coupon_id]);
        $newAmt = $this->getCouponReductionAmount($coupon, $orderTotalPrice);
        if ($newAmt >= $orderTotalPrice) {
            $userCoupon->reduction_amount = $orderTotalPrice;
        } else {
            $userCoupon->reduction_amount = $newAmt;
        }
        $userCoupon->validate();
        $result = $userCoupon->save();
        if (!$result) {
            return false;
        }
        $this->incUseNumber($userCoupon->coupon_id);
        // 返回最终抵扣金额
        return $userCoupon->reduction_amount;
    }

    /**
     * 更新单个优惠券统计信息
     * @param $couponId
     * @return string
     */
    public function updateSum($couponId)
    {
        if (!intval($couponId)) {
            return '未找到优惠券';
        }
        $couponInfo = Coupon::findOne(['id' => $couponId]);
        if (!$couponInfo) {
            return '未找到优惠券';
        }
        $getNumber = UserCoupon::find()->where(['coupon_id' => $couponId])->count();
        $couponInfo->total_number = $getNumber;
        $couponInfo->get_number = $getNumber;
        if ($getNumber) {
            $couponInfo->used_number = UserCoupon::find()->where(['coupon_id' => $couponId, 'is_use' => 1])->count();
        }
        $couponInfo->save();
        return '优惠券ID' . $couponId . '统计数据更新完成';
    }

    /**
     * 累加单个优惠券的使用个数
     * @param $couponId
     * @return int
     */
    public function incUseNumber($couponId)
    {
        return Coupon::updateAllCounters(['used_number' => 1], ['id' => $couponId]);
    }

    /**
     * 获取优惠券可抵扣金额
     * @param $coupon
     * @param int $price
     * @param array $maxMap
     * @return float
     */
    private function getCouponReductionAmount($coupon, $price = 0)
    {
        $amount = 0;
        if ($coupon->coupon_type == 1) {
            $amount = $coupon->reduction_amount;
        } elseif ($coupon->coupon_type == 2) {
            $amount = (1 - $coupon->discount) * $price;
            if ($coupon->maximum_amount > 0 && $amount > $coupon->maximum_amount) {
                $amount = $coupon->maximum_amount;
            }
        }
        return round($amount, 2);
    }

    /**
     * 使用条件检测
     * @param $condition
     * @param mixed $other
     * @return bool
     */
    private function checkCondition($condition, $other = false)
    {
        $w = date('w');
        $d = date('Y-m-d');
        $tm = date('H:i:s');
        if ($condition->week_set) { // 如果设置了星期参数
            $weeks = explode(',', $condition->week_set);
            if (!in_array($w, $weeks)) { // 并且当前星期数不在列表中
                return false;
            }
        }
        if ($condition->date_set) { // 如果设置了特定日期参数
            $days = explode(',', $condition->date_set);
            if (!in_array($d, $days)) { // 并且当前日期不在列表中
                return false;
            }
        }
        if ($condition->hour_raw) { // 如果设置了星期参数
            $ranges = explode(',', $condition->hour_raw);
            $hit = false;
            foreach ($ranges as $range) {
                $one = explode('|', $range);
                if (count($one) < 2) continue;
                if ($one[0] < $tm && $tm < $one[1]) {
                    $hit = true;
                    break;
                }
            }
            if (!$hit) { // 未命中时间段
                return false;
            }
        }
        if (!$other) {
            return true;
        }
        if (isset($other['city_code']) && $condition->city_set) {
            $collection = explode(',', $condition->city_set);
            if (!in_array($other['city_code'], $collection)) {
                return false;
            }
        }
        if (isset($other['serviceTypeId']) && $condition->service_set) {
            $collection = explode(',', $condition->service_set);
            if (!in_array($other['serviceTypeId'], $collection)) {
                return false;
            }
        }
        if (isset($other['carLevelId']) && $condition->level_set) {
            $collection = explode(',', $condition->level_set);
            if (!in_array($other['carLevelId'], $collection)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 根据过期时间分组, 获取第一组优惠券
     * @param $coupons
     * @return mixed
     */
    private function getFirstGroup($coupons)
    {
        $useGroups = [];
        foreach ($coupons as $item) {
            if (count($useGroups) > 1) break;
            $useGroups[$item['expire_time']][] = $item; // 秒级分组******
        }
        return array_shift($useGroups);
    }

    /**
     * @param $coupons
     * @param $price
     * @return \stdClass
     */
    private function getMaxCoupon($coupons, $price)
    {
        $result = $this->getCouponObj();
        // 打包最大金額
        $couponsIds = array_column($coupons, 'coupon_id');
        $couponList = Coupon::find()->where(['id' => $couponsIds])->select('id,maximum_amount')->asArray()->all();
        $maxMap = array_column($couponList, 'maximum_amount', 'id');
        $max = 0;
        foreach ($coupons as $coupon) {
            $coupon = (object)$coupon;
            $coupon->maximum_amount = $maxMap[$coupon->coupon_id] ?? 0;
            $amount = $this->getCouponReductionAmount($coupon, $price);
            if ($amount > $max) {
                $result->userCouponId = $coupon->id;
                $result->maxAmount = $amount;
                $max = $amount;
            }
            if ($max >= $price) { // 优惠券金额大于订单金额, 改为订单金额并停止检索
                $result->maxAmount = $price;
                break;
            }
        }
        return $result;
    }

    /**
     * 获取优惠券输出对象
     * @return \stdClass
     */
    private function getCouponObj()
    {
        $coupon = new \stdClass();
        $coupon->userCouponId = 0;
        $coupon->maxAmount = 0;
        return $coupon;
    }

}
<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "{{%coupon}}".
 *
 * @property int $id 优惠券id
 * @property int $status 优惠券状态 0:禁用, 1:启用
 * @property string $coupon_name 优惠券名称
 * @property string $coupon_desc 优惠券描述
 * @property int $coupon_type 优惠券类型 1:现金券, 2:专项券-免费送车券, 3:专项券-免费还车券 4:折扣券
 * @property int $coupon_class_id 优惠券类ID
 * @property string $coupon_class_name 优惠券类型名称
 * @property int $get_method 1,主动发放. 2,用户获取
 * @property string $minimum_amount 订单最低金额
 * @property string $reduction_amount 减免金额 仅现金券有此项
 * @property string $maximum_amount 最高抵扣金额
 * @property string $discount 折扣比例
 * @property int $effective_type 有效期类型 1：天数 ，2：时间段
 * @property int $effective_day 有效期天数
 * @property string $enable_time 有效期开始时间
 * @property string $expire_time 有效期结束时间
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 * @property string $create_user 创建用户
 * @property int $function_type 功能类型 1:市场活动, 2:订单赔付
 * @property int $total_number 总数量
 * @property int $get_number 已领取数量
 * @property int $used_number 已使用数量
 * @property int $operator_id 已领取数量
 */
class Coupon extends \common\models\BaseModel
{
    use ModelTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%coupon}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'coupon_type', 'coupon_class_id', 'get_method', 'effective_type', 'effective_day', 'function_type', 'total_number', 'get_number', 'used_number', 'operator_id'], 'integer'],
            [['coupon_name'], 'required'],
            [['minimum_amount', 'reduction_amount', 'maximum_amount', 'discount'], 'number'],
            [['enable_time', 'expire_time', 'create_time', 'update_time'], 'safe'],
            [['coupon_name', 'coupon_class_name'], 'string', 'max' => 60],
            [['coupon_desc'], 'string', 'max' => 160],
            [['create_user'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'status' => 'Status',
            'coupon_name' => 'Coupon Name',
            'coupon_desc' => 'Coupon Desc',
            'coupon_type' => 'Coupon Type',
            'coupon_class_id' => 'Coupon Class ID',
            'coupon_class_name' => 'Coupon Class Name',
            'get_method' => 'Get Method',
            'minimum_amount' => 'Minimum Amount',
            'reduction_amount' => 'Reduction Amount',
            'maximum_amount' => 'Maximum Amount',
            'discount' => 'Discount',
            'effective_type' => 'Effective Type',
            'effective_day' => 'Effective Day',
            'enable_time' => 'Enable Time',
            'expire_time' => 'Expire Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'create_user' => 'Create User',
            'function_type' => 'Function Type',
            'total_number' => 'Total Number',
            'get_number' => 'Get Number',
            'used_number' => 'Used Number',
            'operator_id' => 'Operator Id'
        ];
    }


    /**
     * 给用户推送单个优惠券
     * @param $passengerId
     * @param $couponId
     * @param bool $activityInfo
     * @return array|bool
     */
    public static function pushOneCoupon($passengerId, $couponId, $activityInfo = false)
    {
        $couponInfo = self::findOne(['id' => $couponId]);
        if (!$couponInfo) {
            return false;
        }
        // 加入优惠券信息
        $storeData = self::trans2UserCoupon($couponInfo);
        $userCoupon = new UserCoupon();
        $userCoupon->setAttributes($storeData);
        // 加入用户和活动信息
        $userCoupon->passenger_id = $passengerId;
        if($activityInfo && is_array($activityInfo)) {
            $userCoupon->setAttributes($activityInfo);
        }
        // 存储优惠券
        if($userCoupon->save()) {
            $storeData['id'] = $userCoupon->id;
            return $storeData;
        }
        return false;
    }

    // 转换为用户优惠券存储信息
    public static function trans2UserCoupon($coupon)
    {
        if (is_array($coupon)) {
            $coupon = (object)$coupon;
        }
        if (!is_object($coupon)) {
            return false;
        }
        $storeData = [
            'coupon_id' => $coupon->id,
            'coupon_name' => $coupon->coupon_name,
            'coupon_type' => $coupon->coupon_type,
            'get_method' => $coupon->get_method,
            'enable_time' => $coupon->enable_time,
            'expire_time' => $coupon->expire_time,
            'minimum_amount' => $coupon->minimum_amount,
            'reduction_amount' => $coupon->reduction_amount,
            'discount' => $coupon->discount,
            'function_type' => $coupon->function_type,
        ];
        if ($coupon->effective_type == 1) {
            $storeData['available_text'] = $coupon->enable_time . '至' . $coupon->expire_time;
        } else {
            $d = $coupon->effective_day;
            $storeData['enable_time'] = date('Y-m-d H:i:s');
            $storeData['expire_time'] = date('Y-m-d H:i:s', strtotime("+ $d day"));
            $storeData['available_text'] = "获取后{$d}天";
        }
        return $storeData;
    }
}

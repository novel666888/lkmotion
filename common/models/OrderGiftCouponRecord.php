<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%order_gift_coupon_record}}".
 *
 * @property int $id
 * @property int $order_id 订单id
 * @property int $doubt_id 疑义定单id //order_doubt表中的id
 * @property int $passenger_info_id 接收用户优惠券的passenger id
 * @property int $coupon_id 优惠券类型id
 * @property string $user_phone 用户电话
 * @property string $coupon_name 优惠券类型名
 * @property int $user_coupon_id 用户优惠券id
 * @property double $coupon_amount 优惠券金额
 * @property string $coupon_expired_date 优惠券有效期 文案
 * @property int $operator_id 操作人Id
 * @property string $operator_time 操作时间
 * @property string $solution 处理方案
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class OrderGiftCouponRecord extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_gift_coupon_record}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'coupon_id', 'user_phone', 'operator_id', 'operator_time'], 'required'],
            [['order_id', 'doubt_id', 'passenger_info_id', 'coupon_id', 'user_coupon_id', 'operator_id'], 'integer'],
            [['coupon_amount'], 'number'],
            [['operator_time', 'create_time', 'update_time'], 'safe'],
            [['user_phone'], 'string', 'max' => 64],
            [['coupon_name', 'coupon_expired_date'], 'string', 'max' => 200],
            [['solution'], 'string', 'max' => 300],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'doubt_id' => 'Doubt ID',
            'passenger_info_id' => 'Passenger Info ID',
            'coupon_id' => 'Coupon ID',
            'user_phone' => 'User Phone',
            'coupon_name' => 'Coupon Name',
            'user_coupon_id' => 'User Coupon ID',
            'coupon_amount' => 'Coupon Amount',
            'coupon_expired_date' => 'Coupon Expired Date',
            'operator_id' => 'Operator ID',
            'operator_time' => 'Operator Time',
            'solution' => 'Solution',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

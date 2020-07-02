<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%order_payment}}".
 *
 * @property int $id
 * @property int $order_id 订单id
 * @property int $driver_id 司机ID
 * @property int $pay_type 支付类型：1微信，2账户余额，4支付宝
 * @property double $total_price 总金额
 * @property double $final_price 最终订单金额（除去优惠券)
 * @property double $balance_price 余额支付金额
 * @property double $wechat_price 微信支付金额
 * @property double $alipay_price 支付宝金额
 * @property int $user_coupon_id 用户获得优惠券id,此时不是优惠券id，是用户优惠券关联id
 * @property double $coupon_reduce_price 优惠券减免金额
 * @property double $paid_price 已支付金额(最终订单金额中已付的钱)
 * @property double $remain_price 剩余支付的金额
 * @property double $tail_price 尾款价格
 * @property double $replenish_price 补扣价格
 * @property string $pay_time 支付时间
 * @property string $create_time
 * @property string $update_time
 */
class OrderPayment extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_payment}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'final_price'], 'required'],
            [['order_id', 'driver_id', 'pay_type', 'user_coupon_id'], 'integer'],
            [['total_price', 'final_price', 'balance_price', 'wechat_price', 'alipay_price', 'coupon_reduce_price', 'paid_price', 'remain_price', 'tail_price', 'replenish_price'], 'number'],
            [['pay_time', 'create_time', 'update_time'], 'safe'],
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
            'driver_id' => 'Driver ID',
            'pay_type' => 'Pay Type',
            'total_price' => 'Total Price',
            'final_price' => 'Final Price',
            'balance_price' => 'Balance Price',
            'wechat_price' => 'Wechat Price',
            'alipay_price' => 'Alipay Price',
            'user_coupon_id' => 'User Coupon ID',
            'coupon_reduce_price' => 'Coupon Reduce Price',
            'paid_price' => 'Paid Price',
            'remain_price' => 'Remain Price',
            'tail_price' => 'Tail Price',
            'replenish_price' => 'Replenish Price',
            'pay_time' => 'Pay Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

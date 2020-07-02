<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%order_use_coupon}}".
 *
 * @property int $id
 * @property int $order_id 订单id
 * @property int $coupon_id 优惠券Id
 * @property string $category 1:预估 2，实际
 * @property double $coupon_money 优惠券金额
 * @property double $after_use_coupon_moeny 优惠后金额
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class OrderUseCoupon extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_use_coupon}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'coupon_id'], 'required'],
            [['order_id', 'coupon_id'], 'integer'],
            [['coupon_money','after_use_coupon_moeny'], 'number'],
            [['create_time', 'update_time'], 'safe'],
            [['category'], 'string', 'max' => 1],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'coupon_id' => 'Coupon ID',
            'category' => 'Category',
            'coupon_money' => 'Coupon Money',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

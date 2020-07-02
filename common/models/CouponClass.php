<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%coupon_class}}".
 *
 * @property int $id
 * @property int $coupon_type 优惠券类型:1现金券,2折扣券
 * @property string $coupon_name 优惠券名称
 * @property string $reduction_amount 抵扣金额
 * @property string $discount 折扣比例(8, 7.5)
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 * @property string $operator_id 最后修改人id
 * @property int $is_pause 暂停:0否1是
 */
class CouponClass extends BaseModel
{
    public $couponTypes = [
        '1' => '现金优惠',
        '2' => '折扣优惠',
    ];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%coupon_class}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['coupon_name'],'required',
                'message' => '优惠券名称不能为空'
            ],
            [['coupon_type', 'is_pause', 'operator_id'], 'integer'],
            [['reduction_amount', 'discount'], 'number'],
            [['create_time', 'update_time'], 'safe'],
            [['coupon_name'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'coupon_type' => 'Coupon Type',
            'coupon_name' => 'Coupon Name',
            'reduction_amount' => 'Reduction Amount',
            'discount' => 'Discount',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'modify_by' => 'Modify By',
            'is_pause' => 'Is Pause',
        ];
    }
}

<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%order_adjust_record}}".
 *
 * @property int $id ID
 * @property int $order_id 订单id
 * @property int $doubt_id 疑义订单id
 * @property int $adjust_account_type 调账类型1,充值 2.扣款
 * @property string $charge_number 充值|扣款 单号
 * @property string $old_cost 调账前金额
 * @property string $new_cost 调账后金额
 * @property int $reason_type 调账原因id
 * @property string $reason_text 自定义调账原因
 * @property string $solution 处理方案
 * @property int $operator 操作人
 * @property string $create_time create_time
 * @property string $update_time update_time
 */
class OrderAdjustRecord extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_adjust_record}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'adjust_account_type', 'old_cost', 'new_cost', 'operator'], 'required'],
            [['order_id', 'doubt_id', 'adjust_account_type', 'reason_type', 'operator'], 'integer'],
            [['old_cost', 'new_cost'], 'number'],
            [['create_time', 'update_time'], 'safe'],
            [['charge_number'], 'string', 'max' => 32],
            [['reason_text', 'solution'], 'string', 'max' => 500],
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
            'adjust_account_type' => 'Adjust Account Type',
            'charge_number' => 'Charge Number',
            'old_cost' => 'Old Cost',
            'new_cost' => 'New Cost',
            'reason_type' => 'Reason Type',
            'reason_text' => 'Reason Text',
            'solution' => 'Solution',
            'operator' => 'Operator',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

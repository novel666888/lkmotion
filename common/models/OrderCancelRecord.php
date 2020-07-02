<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "{{%order_cancel_record}}".
 *
 * @property int $id
 * @property int $order_id 订单id
 * @property int $is_charge 乘客是否有责0无责，1有责
 * @property double $cancel_cost 取消费
 * @property int $reason_type 取消原因类型 
 1
 2
 3
 4
 * @property string $reason_text 其他原因
 * @property int $operator 操作人
 * @property string $operator_type 操作人类型1:客户自行取消 2:客服取消
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class OrderCancelRecord extends BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    const OPERATOR_TYPE_USER = 1;
    const OPERATOR_TYPE_SERVICE = 2;
    const USER_NO_CHARGE = 0;
    const USER_HAS_CHARGE  = 1;
    public static function tableName()
    {
        return '{{%order_cancel_record}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'is_charge', 'cancel_cost', 'operator', 'operator_type'], 'required'],
            [['order_id', 'is_charge', 'reason_type', 'operator','operator_type'], 'integer'],
            [['cancel_cost'], 'number'],
            [['create_time', 'update_time'], 'safe'],
            [['reason_text'], 'string', 'max' => 128],
            //[['operator_type'], 'string', 'max' => 255],
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
            'is_charge' => 'Is Charge',
            'cancel_cost' => 'Cancel Cost',
            'reason_type' => 'Reason Type',
            'reason_text' => 'Reason Text',
            'operator' => 'Operator',
            'operator_type' => 'Operator Type',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%order_reassignment_record}}".
 *
 * @property int $id
 * @property int $order_id
 * @property int $driver_id_before
 * @property string $driver_name_before
 * @property int $driver_id_now
 * @property string $driver_name_now
 * @property string $operator
 * @property int $reason_type
 * @property string $reason_text
 * @property string $create_time
 * @property string $update_time
 */
class OrderReassignmentRecord extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_reassignment_record}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id','reason_type'], 'required'],
            [['order_id', 'driver_id_before', 'driver_id_now', 'reason_type'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['driver_name_before', 'driver_name_now'], 'string', 'max' => 100],
            [['operator'], 'string', 'max' => 32],
            [['reason_text'], 'string', 'max' => 200],
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
            'driver_id_before' => 'Driver Id Before',
            'driver_name_before' => 'Driver Name Before',
            'driver_id_now' => 'Driver Id Now',
            'driver_name_now' => 'Driver Name Now',
            'operator' => 'Operator',
            'reason_type' => 'Reason Type',
            'reason_text' => 'Reason Text',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

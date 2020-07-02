<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%evaluate_driver}}".
 *
 * @property int $id
 * @property int $grade 分数
 * @property string $content 评价内容
 * @property int $order_id 订单ID
 * @property int $passenger_id
 * @property int $driver_id
 * @property string $create_time
 * @property string $update_time
 */
class EvaluateDriver extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%evaluate_driver}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['grade', 'order_id', 'passenger_id', 'driver_id'], 'integer'],
            [['order_id'], 'required'],
            [['create_time', 'update_time'], 'safe'],
            [['content'], 'string', 'max' => 128],
            [['label'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'grade' => 'Grade',
            'label' => 'Label',
            'content' => 'Content',
            'order_id' => 'Order ID',
            'passenger_id' => 'Passenger ID',
            'driver_id' => 'Driver ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

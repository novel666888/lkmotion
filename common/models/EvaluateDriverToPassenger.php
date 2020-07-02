<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "tbl_evaluate_driver_to_passenger".
 *
 * @property string $id 自增主键
 * @property string $grade 分数
 * @property string $label 评价标签
 * @property string $content 评价内容
 * @property string $order_id 订单ID
 * @property string $passenger_id 乘客ID
 * @property string $driver_id 司机ID
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class EvaluateDriverToPassenger extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_evaluate_driver_to_passenger';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['grade', 'order_id', 'passenger_id', 'driver_id'], 'integer'],
            [['order_id'], 'required'],
            [['create_time', 'update_time'], 'safe'],
            [['label'], 'string', 'max' => 64],
            [['content'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
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

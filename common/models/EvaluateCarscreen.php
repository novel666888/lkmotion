<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "tbl_evaluate_carscreen".
 *
 * @property string $id 自增主键
 * @property string $grade 星评级别
 * @property string $content 评价内容
 * @property string $order_id 订单ID
 * @property string $passenger_id 乘客ID
 * @property string $car_id 车id
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class EvaluateCarscreen extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_evaluate_carscreen';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['grade', 'order_id', 'passenger_id', 'car_id'], 'integer'],
            [['order_id'], 'required'],
            [['create_time', 'update_time'], 'safe'],
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
            'content' => 'Content',
            'order_id' => 'Order ID',
            'passenger_id' => 'Passenger ID',
            'car_id' => 'Car ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

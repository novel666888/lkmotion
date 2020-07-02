<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%driver_income_detail}}".
 *
 * @property int $id
 * @property int $driver_info_id
 * @property string $order_no
 * @property string $order_money
 * @property string $create_time
 * @property string $money
 * @property string $driver_ratio
 */
class DriverIncomeDetail extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%driver_income_detail}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['driver_info_id'], 'integer'],
            [['order_money', 'money', 'driver_ratio'], 'number'],
            [['create_time'], 'safe'],
            [['order_no'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'driver_info_id' => 'Driver Info ID',
            'order_no' => 'Order No',
            'order_money' => 'Order Money',
            'create_time' => 'Create Time',
            'money' => 'Money',
            'driver_ratio' => 'Driver Ratio',
        ];
    }
}

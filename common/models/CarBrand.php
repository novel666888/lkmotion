<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%car_brand}}".
 *
 * @property int $id
 * @property int $pid 车辆品牌ID
 * @property string $brand 车辆品牌
 * @property string $model 车辆型号
 * @property int $seats 标准座位数量
 * @property int $is_delete 0：未删除，1：已删除
 */
class CarBrand extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%car_brand}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid', 'seats', 'is_delete'], 'integer'],
            [['brand'], 'string', 'max' => 16],
            [['model'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pid' => 'Pid',
            'brand' => 'Brand',
            'model' => 'Model',
            'seats' => 'Seats',
            'is_delete' => 'Is Delete',
        ];
    }
}

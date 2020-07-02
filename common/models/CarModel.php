<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "tbl_car_model".
 *
 * @property string $id
 * @property string $pid 车辆品牌ID
 * @property string $model 车辆型号
 * @property int $seats 标准座位数量
 * @property int $is_delete 0：未删除，1：已删除
 */
class CarModel extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_car_model';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid', 'seats', 'is_delete'], 'integer'],
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
            'model' => 'Model',
            'seats' => 'Seats',
            'is_delete' => 'Is Delete',
        ];
    }
}

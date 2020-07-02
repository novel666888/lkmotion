<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "tbl_car_driver_bind_record".
 *
 * @property int $id
 * @property int $driver_info_id 司机ID
 * @property int $car_info_id 车辆ID
 * @property string $operator_user
 * @property int $type 1绑定0解绑
 * @property string $create_time
 * @property string $bind_time 绑定时间
 * @property string $unbind_time 解绑时间
 * @property int $total_mile 车辆总里程
 */
class CarDriverBindRecord extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_car_driver_bind_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['driver_info_id', 'car_info_id', 'type', 'total_mile'], 'integer'],
            [['type'], 'required'],
            [['create_time', 'bind_time', 'unbind_time'], 'safe'],
            [['operator_user'], 'string', 'max' => 64],
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
            'car_info_id' => 'Car Info ID',
            'operator_user' => 'Operator User',
            'type' => 'Type',
            'create_time' => 'Create Time',
            'bind_time' => 'Bind Time',
            'unbind_time' => 'Unbind Time',
            'total_mile' => 'Total Mile',
        ];
    }
}

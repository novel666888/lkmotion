<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%driver_address}}".
 *
 * @property int $id
 * @property int $driver_id
 * @property string $address 详细地址
 * @property string $address_longitude 地址经度
 * @property string $address_latitude 地址纬度
 * @property string $create_time 创建时间
 * @property string $update_time 编辑时间
 * @property string $tag 标签
 * @property int $is_default 是否默认地址
 */
class DriverAddress extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%driver_address}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['driver_id', 'is_default'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['address'], 'string', 'max' => 120],
            [['address_longitude', 'address_latitude'], 'string', 'max' => 32],
            [['tag'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'driver_id' => 'Driver ID',
            'address' => 'Address',
            'address_longitude' => 'Address Longitude',
            'address_latitude' => 'Address Latitude',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'tag' => 'Tag',
            'is_default' => 'Is Default',
        ];
    }
}

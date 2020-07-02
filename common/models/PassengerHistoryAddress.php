<?php

namespace common\models;

use Yii;
use yii\base\BaseObject;

/**
 * This is the model class for table "{{%passenger_history_address}}".
 *
 * @property int $id
 * @property int $passenger_info_id 乘客id
 * @property string $passenger_phone 乘客电话
 * @property int $type 1上车地点
 2下车地点
 * @property string $address 详细地址
 * @property string $city_code 城市码
 * @property string $city 城市
 * @property string $detail_name 地址短名
 * @property string $ad_code 地址码
 * @property string $longitude 经度
 * @property string $latitude 纬度
 * @property string $is_del 是否被删除0未删除 1删除
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class PassengerHistoryAddress extends BaseModel
{
    const DEL_YES = 1;
    const DEL_NO = 0;

    const TYPE_GET_ON = 1;
    const TYPE_GET_OFF = 2;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%passenger_history_address}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['passenger_info_id', 'type'], 'required'],
            [['passenger_info_id', 'type'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['passenger_phone'], 'string', 'max' => 11],
            [['address'], 'string', 'max' => 500],
            [['city_code', 'city', 'ad_code', 'longitude', 'latitude'], 'string', 'max' => 32],
            [['detail_name'], 'string', 'max' => 100],
            [['is_del'], 'string', 'max' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'passenger_info_id' => 'Passenger Info ID',
            'passenger_phone' => 'Passenger Phone',
            'type' => 'Type',
            'address' => 'Address',
            'city_code' => 'City Code',
            'city' => 'City',
            'detail_name' => 'Detail Name',
            'ad_code' => 'Ad Code',
            'longitude' => 'Longitude',
            'latitude' => 'Latitude',
            'is_del' => 'Is Del',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

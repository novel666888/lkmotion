<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%coupon_conditions}}".
 *
 * @property int $id
 * @property int $coupon_id 优惠券ID
 * @property string $hour_set 小时集合
 * @property string $week_set 周集合
 * @property string $date_set 日期集合
 * @property string $city_set 城市集合
 * @property string $car_set 车辆类型集合
 * @property string $service_set 服务集合
 * @property string $level_set
 * @property string $hour_raw 时段原始数据
 * @property string $date_raw 日期原始数据
 * @property string $create_time
 */
class CouponConditions extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%coupon_conditions}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['coupon_id'], 'integer'],
            [['create_time'], 'safe'],
            [['hour_set', 'date_set'], 'string', 'max' => 80],
            [['week_set'], 'string', 'max' => 15],
            [['city_set'], 'string', 'max' => 1023],
            [['car_set'], 'string', 'max' => 60],
            [['service_set', 'level_set'], 'string', 'max' => 30],
            [['hour_raw', 'date_raw'], 'string', 'max' => 120],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'coupon_id' => 'Coupon ID',
            'hour_set' => 'Hour Set',
            'week_set' => 'Week Set',
            'date_set' => 'Date Set',
            'city_set' => 'City Set',
            'car_set' => 'Car Set',
            'service_set' => 'Service Set',
            'level_set' => 'Level Set',
            'hour_raw' => 'Hour Raw',
            'date_raw' => 'Date Raw',
            'create_time' => 'Create Time',
        ];
    }
}

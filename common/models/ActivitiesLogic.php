<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%activities_logic}}".
 *
 * @property int $id
 * @property int $activity_id 活动ID
 * @property string $create_time 创建时间
 * @property int $driver_id 司机ID
 * @property int $passenger_id 乘客ID
 * @property string $coupon_id 优惠券ID
 * @property string $ext_bonuses 其他奖励,存储其他奖励表名和主键json
 */
class ActivitiesLogic extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%activities_logic}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['activity_id', 'driver_id', 'passenger_id', 'coupon_id'], 'integer'],
            [['create_time'], 'safe'],
            [['ext_bonuses'], 'string', 'max' => 60],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'activity_id' => 'Activity ID',
            'create_time' => 'Create Time',
            'driver_id' => 'Driver ID',
            'passenger_id' => 'Passenger ID',
            'coupon_id' => 'Coupon ID',
            'ext_bonuses' => 'Ext Bonuses',
        ];
    }
}

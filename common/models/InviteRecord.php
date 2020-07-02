<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%invite_record}}".
 *
 * @property int $id
 * @property int $invite_driver_id 邀请司机ID
 * @property int $driver_id 被邀司机ID
 * @property int $invite_passenger_id 邀请乘客ID
 * @property int $passenger_id 被邀乘客ID
 * @property string $active_time 第一次登陆时间
 * @property string $charge_time
 * @property string $consumption_time 第一次消费时间
 * @property string $work_time 第一次行程服务时间
 * @property int $is_sure 是否确认,1是,0否
 * @property string $create_time 创建时间
 * @property string $activity_no 活动编号
 * @property string $invitee_info 被邀请人详情
 * @property int $is_delete 是否被删除
 * @property int $operator_id 操作人ID
 */
class InviteRecord extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%invite_record}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['invite_driver_id', 'driver_id', 'invite_passenger_id', 'passenger_id', 'is_sure', 'is_delete', 'operator_id'], 'integer'],
            [['active_time', 'charge_time', 'consumption_time', 'work_time', 'create_time'], 'safe'],
            [['activity_no'], 'string', 'max' => 15],
            [['invitee_info'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'invite_driver_id' => 'Invite Driver ID',
            'driver_id' => 'Driver ID',
            'invite_passenger_id' => 'Invite Passenger ID',
            'passenger_id' => 'Passenger ID',
            'active_time' => 'Active Time',
            'charge_time' => 'Charge Time',
            'consumption_time' => 'Consumption Time',
            'work_time' => 'Work Time',
            'is_sure' => 'Is Sure',
            'create_time' => 'Create Time',
            'activity_no' => 'Activity No',
            'invitee_info' => 'Invitee Info',
            'is_delete' => 'Is Delete',
            'operator_id' => 'Operator ID',
        ];
    }
}

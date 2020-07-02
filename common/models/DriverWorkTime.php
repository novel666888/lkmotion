<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%driver_work_time}}".
 *
 * @property int $id
 * @property int $driver_id 司机id
 * @property string $work_start 工作开始时间
 * @property string $work_end 工作结束时间
 * @property string $work_duration 此次工作时长（单位：分钟）
 * @property string $create_time 创建时间
 * @property string $update_time
 */
class DriverWorkTime extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%driver_work_time}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['driver_id'], 'required'],
            [['driver_id'], 'integer'],
            [['work_start', 'work_end', 'create_time', 'update_time'], 'safe'],
            [['work_duration'], 'number'],
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
            'work_start' => 'Work Start',
            'work_end' => 'Work End',
            'work_duration' => 'Work Duration',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

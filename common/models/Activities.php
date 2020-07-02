<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%activities}}".
 *
 * @property int $id
 * @property string $activity_no 活动方案编号
 * @property string $activity_name 活动方案名称
 * @property int $activity_type 优惠形势:1返券,2充增,3拉新
 * @property string $activity_desc 活动说明
 * @property string $enable_time 活动开始时间
 * @property string $expire_time 活动结束时间
 * @property string $join_cycle 参与机制 年,月,日,时,单次,不限
 * @property string $bonuses_rule 领取机制详情json
 * @property int $people_tag 参与人群
 * @property string $create_time
 * @property string $update_time
 * @property int $operator_id 操作人ID
 */
class Activities extends \common\models\BaseModel
{
    public $types = [
        '1' => ['text' => '返券', 'short' => 'FQ'],
        '2' => ['text' => '充赠', 'short' => 'CZ'],
        '3' => ['text' => '拉新', 'short' => 'LX'],
    ];

    public $cycle = [
        'nil' => '不限次数',
        'once' => '一次',
        'day' => '每天一次',
        'month' => '每月一次',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%activities}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['activity_type', 'people_tag', 'operator_id'], 'integer'],
            [['enable_time', 'expire_time', 'create_time', 'update_time'], 'safe'],
            [['join_cycle'], 'string'],
            [['activity_no'], 'string', 'max' => 15],
            [['activity_name'], 'string', 'max' => 60],
            [['activity_desc'], 'string', 'max' => 255],
            [['bonuses_rule'], 'string', 'max' => 1000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'activity_no' => 'Activity No',
            'activity_name' => 'Activity Name',
            'activity_type' => 'Activity Type',
            'activity_desc' => 'Activity Desc',
            'enable_time' => 'Enable Time',
            'expire_time' => 'Expire Time',
            'join_cycle' => 'Join Cycle',
            'bonuses_rule' => 'Bonuses Rule',
            'people_tag' => 'People Tag',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'operator_id' => 'Operator ID',
        ];
    }
}

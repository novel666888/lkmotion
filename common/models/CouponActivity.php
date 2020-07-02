<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "{{%coupon_activity}}".
 *
 * @property int $id
 * @property string $activity_no 方案编号
 * @property string $activity_name 方案名称
 * @property string $enable_time 活动开始时间
 * @property string $expire_time 过期时间
 * @property int $activity_type 优惠形式
 * @property string $coupon_rule 领券规则
 * @property string $activity_desc 活动描述
 * @property int $get_times 领券次数
 * @property int $send_times 发送次数
 * @property string $create_time
 * @property string $update_time
 * @property string $operator_id 最后变更人id
 * @property int $status 是否停用:0是1否
 */
class CouponActivity extends BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%coupon_activity}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['enable_time', 'expire_time', 'create_time', 'update_time'], 'safe'],
            [['activity_type', 'get_times', 'send_times', 'status','operator_id'], 'integer'],
            [['activity_no', 'activity_name'], 'string', 'max' => 30],
            [['coupon_rule', 'activity_desc'], 'string', 'max' => 250],
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
            'enable_time' => 'Enable Time',
            'expire_time' => 'Expire Time',
            'activity_type' => 'Activity Type',
            'coupon_rule' => 'Coupon Rule',
            'activity_desc' => 'Activity Desc',
            'get_times' => 'Get Times',
            'send_times' => 'Send Times',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'operator_id' => 'Operator Id',
            'status' => 'Status',
        ];
    }
}

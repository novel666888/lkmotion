<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "{{%coupon_task}}".
 *
 * @property int $id
 * @property int $coupon_id 优惠券ID
 * @property string $coupon_name 优惠券名称
 * @property int $number 发放数量
 * @property string $task_tag 任务标签,用于区分不同批次
 * @property int $people_tag_id 人群模板ID
 * @property string $task_target 任务目标,最多100个手机号
 * @property int $app_tpl_id app文案模板ID
 * @property int $sms_tpl_id 短信文案模板ID
 * @property string $create_time
 * @property string $update_time
 * @property int $operator_id 操作人id
 * @property int $task_status 任务状态:0未开始1进行中2完成
 * @property string $start_time
 * @property int $is_cancel 是否终止:0否,1是
 * @property string $plan_time 计划发送时间
 */
class CouponTask extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%coupon_task}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['coupon_id', 'number', 'people_tag_id', 'app_tpl_id', 'sms_tpl_id', 'operator_id', 'task_status', 'is_cancel'], 'integer'],
            [['create_time', 'update_time', 'start_time', 'plan_time'], 'safe'],
            [['coupon_name', 'task_tag'], 'string', 'max' => 30],
            [['task_target'], 'string', 'max' => 1280],
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
            'coupon_name' => 'Coupon Name',
            'number' => 'Number',
            'task_tag' => 'Task Tag',
            'people_tag_id' => 'People Tag ID',
            'task_target' => 'Task Target',
            'app_tpl_id' => 'App Tpl ID',
            'sms_tpl_id' => 'Sms Tpl ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'operator_id' => 'Operator ID',
            'task_status' => 'Task Status',
            'start_time' => 'Start Time',
            'is_cancel' => 'Is Cancel',
            'plan_time' => 'Plan Time',
        ];
    }
}

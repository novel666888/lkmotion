<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;

/**
 * This is the model class for table "{{%sms_send_app}}".
 *
 * @property int $id
 * @property string $send_number 消息推送编号
 * @property int $show_type 展示端（1：大屏端；2：乘客端；3：司机端）
 * @property int $sms_type 消息类型（1：营销；2：通知）
 * @property int $people_tag_id 人群id
 * @property int $app_template_id 消息文案id
 * @property int $sms_level 消息级别
 * @property string $start_time 生效时间
 * @property int $send_status 推送状态（0：未推送；1：已推送）
 * @property int $status 启用状态
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class SmsSendApp extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%sms_send_app}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['show_type', 'sms_type', 'people_tag_id', 'app_template_id', 'send_status', 'status','operator_user','sms_level'], 'integer'],
            [['start_time'], 'required'],
            [['send_number'], 'string', 'max' => 32],
            [['start_time', 'create_time', 'update_time'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'send_number' => 'Send Number',
            'show_type' => 'Show Type',
            'sms_type' => 'Sms Type',
            'people_tag_id' => 'People Tag ID',
            'app_template_id' => 'App Template ID',
            'sms_level' => 'Sms Level',
            'start_time' => 'Start Time',
            'send_status' => 'Send Status',
            'status' => 'Status',
            'operator_user' => 'Operator User',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

}

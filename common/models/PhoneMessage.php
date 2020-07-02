<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%phone_message}}".
 *
 * @property int $id
 * @property string $phone_number 电话
 * @property string $sms_content 消息体
 * @property string $send_time 发送时间
 * @property int $push_type 推送类型 （1：营销,2:通知）
 * @property int $status 发送状态（0：失败；1：成功）
 * @property string $operator 操作者
 * @property string $create_time 创建时间
 */
class PhoneMessage extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%phone_message}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phone_number', 'sms_content', 'send_time', 'status','push_type'], 'required'],
            [['send_time', 'create_time'], 'safe'],
            [['phone_number'], 'string', 'max' => 16],
            [['sms_content'], 'string', 'max' => 512],
            [['operator'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'phone_number' => 'Phone Number',
            'sms_content' => 'Sms Content',
            'send_time' => 'Send Time',
            'push_type' => 'Push Type',
            'status'   => 'Status',
            'operator' => 'Operator',
            'create_time' => 'Create Time',
        ];
    }
}

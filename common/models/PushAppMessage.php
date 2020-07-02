<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%push_app_message}}".
 *
 * @property int $id
 * @property int $yid 乘客、司机id、大屏设备号
 * @property string $title 标题
 * @property string $content 消息内容
 * @property int $accept_identity 接收端（1：乘客；2：司机）
 * @property string $send_time 发送时间
 * @property int $sms_send_app_id 消息推送任务id
 * @property int $push_type 推送类型（1：营销通知，2：系统通知，3：订单通知，4：支付通知）
 * @property int $status 是否显示（0：不显示，1：显示）
 * @property string $create_time 创建时间
 */
class PushAppMessage extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%push_app_message}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['accept_identity','push_type','status','sms_send_app_id'],'integer'],
            [['send_time'], 'required'],
            [['content', 'send_time', 'create_time'], 'safe'],
            [['title'], 'string', 'max' => 120],
            [['yid'], 'string', 'max' => 32],
            [['content'], 'string', 'max' => 1024],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'yid' => 'Yid',
            'title' => 'Title',
            'content' => 'Content',
            'accept_identity' => 'Accept Identity',
            'push_type'  => 'Push Type',
            'status'  => 'Status',
            'send_time' => 'Send Time',
            'sms_send_app_id' => 'Sms Send ID',
            'create_time' => 'Create Time',
        ];
    }
}

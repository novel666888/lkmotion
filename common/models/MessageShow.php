<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%message_show}}".
 *
 * @property int $id
 * @property string $title 标题
 * @property string $content 消息体
 * @property int $yid 乘客、司机id
 * @property int $order_id 订单id
 * @property int $accept_identity 接收端（1：乘客；2：司机；4：大屏）
 * @property string $send_time 发送时间
 * @property int $push_type 推送类型 （1：营销通知，2：系统通知，3：订单通知，4：支付通知）
 * @property int $status 是否显示（0：不显示，1：显示）
 * @property int $is_read 是否已读（0：未读，1：已读）
 * @property int $sms_send_app_id 推送任务id
 * @property string $create_time
 */
class MessageShow extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%message_show}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['accept_identity', 'send_time'], 'required'],
            [['push_type', 'status', 'sms_send_app_id','order_id','is_read'], 'integer'],
            [['send_time', 'create_time'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['yid'], 'string', 'max' => 32],
            [['content'], 'string', 'max' => 512],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'content' => 'Content',
            'yid' => 'Yid',
            'accept_identity' => 'Accept Identity',
            'order_id' => 'Order ID',
            'send_time' => 'Send Time',
            'push_type' => 'Push Type',
            'status' => 'Status',
            'is_read' => 'Is Read',
            'sms_send_app_id' => 'Sms Send App ID',
            'create_time' => 'Create Time',
        ];
    }
}

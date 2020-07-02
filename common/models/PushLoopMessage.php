<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%push_loop_message}}".
 *
 * @property int $id
 * @property int $accept_identity 1:乘客，2：司机，3：车机，4：大屏
 * @property string $accept_id 接受方id
 * @property int $message_type 消息类型，看枚举定义
 * @property string $message_body 消息体
 * @property int $read_flag 0:未读，1：已读
 * @property string $send_id 发送方id
 * @property int $send_identity 发送者身份类别
 * @property string $create_time 消息创建时间
 * @property string $expire_time 消息失效时间
 */
class PushLoopMessage extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%push_loop_message}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['accept_identity', 'message_type', 'read_flag', 'send_identity'], 'integer'],
            [['send_identity'], 'required'],
            [['create_time', 'expire_time'], 'safe'],
            [['accept_id', 'send_id'], 'string', 'max' => 128],
            [['message_body'], 'string', 'max' => 512],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'accept_identity' => 'Accept Identity',
            'accept_id' => 'Accept ID',
            'message_type' => 'Message Type',
            'message_body' => 'Message Body',
            'read_flag' => 'Read Flag',
            'send_id' => 'Send ID',
            'send_identity' => 'Send Identity',
            'create_time' => 'Create Time',
            'expire_time' => 'Expire Time',
        ];
    }
}

<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;

/**
 * This is the model class for table "{{%sms}}".
 *
 * @property int $id
 * @property string $passenger_phone_number 乘客手机号
 * @property string $sms_content 短信内容
 * @property string $send_time 发送时间
 * @property string $operator 操作人
 * @property int $send_flag 发送状态 0:失败  1: 成功
 * @property int $send_number 发送失败次数
 */
class Sms extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%sms}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['send_time', 'send_flag'], 'required'],
            [['send_time'], 'safe'],
            [['send_flag', 'send_number'], 'integer'],
            [['passenger_phone_number'], 'string', 'max' => 16],
            [['sms_content'], 'string', 'max' => 512],
            [['operator'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'passenger_phone_number' => 'Passenger Phone Number',
            'sms_content' => 'Sms Content',
            'send_time' => 'Send Time',
            'operator' => 'Operator',
            'send_flag' => 'Send Flag',
            'send_number' => 'Send Number',
        ];
    }
}

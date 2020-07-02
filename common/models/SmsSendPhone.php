<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%sms_send_phone}}".
 *
 * @property int $id
 * @property int $sms_template_id 文案模板id
 * @property int $sms_type 消息类型（1：营销；2：通知）
 * @property int $send_type 发送类型（1：单人发送；2：批量发送）
 * @property string $phone_number 单人发送手机号
 * @property int $send_people 发送人群 1:乘客；2：司机
 * @property string $phone_file 批量发送文件名
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class SmsSendPhone extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%sms_send_phone}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sms_type', 'send_type','send_people'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['phone_number'], 'string', 'max' => 255],
            [['phone_file'], 'string', 'max' => 120],
            [['sms_template_id'], 'string', 'max' => 30],
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sms_template_id' => 'Sms Template ID',
            'sms_type' => 'Sms Type',
            'send_type' => 'Send Type',
            'send_people' => 'Send People',
            'phone_number' => 'Phone Number',
            'phone_file' => 'Phone File',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

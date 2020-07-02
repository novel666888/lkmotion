<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;

/**
 * This is the model class for table "{{%sms_app_template}}".
 *
 * @property int $id
 * @property string $template_name 模板名称
 * @property int $template_type 模板类型（1：营销；2：通知）
 * @property string $send_type 发送类型（1：文案；2：图片；3：语音）
 * @property string $sms_image 消息图片
 * @property string $sms_url 链接地址
 * @property string $content 模板内容
 * @property int $operator_user 创建者
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class SmsAppTemplate extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public $label = [
        'passenger_name',
        'passenger_phone',
        'passenger_birthday',
        'passenger_register_time',
        'passenger_gender',
        'passenger_balance',
        'driver_name',
        'driver_phone',
        'car_level_id',
        'plate_number',
        'driver_birthday',
        'driver_gender',
        'driver_manage',
        'driver_manage_phone',
    ];
    
    
    public $keyLabel = [
        '乘客姓名'=>'passenger_name',
        '乘客电话'=>'passenger_phone',
        '乘客生日'=>'passenger_birthday',
        '乘客注册时间'=>'passenger_register_time',
        '乘客性别'=>'passenger_gender',
        '乘客余额'=>'passenger_balance',
        '司机姓名'=>'driver_name',
        '司机电话'=>'driver_phone',
        '车辆级别'=>'car_level_id',
        '车牌号'=>'plate_number',
        '司机生日'=>'driver_birthday',
        '司机性别'=>'driver_gender',
        '司机主管'=>'driver_manage',
        '司机主管电话'=>'driver_manage_phone',
    ];
    public static function tableName()
    {
        return '{{%sms_app_template}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['template_type','operator_user'], 'integer'],
            [['content'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['template_name', 'sms_image', 'sms_url'], 'string', 'max' => 255],
            [['send_type'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'template_name' => 'Template Name',
            'template_type' => 'Template Type',
            'sms_image' => 'Sms Image',
            'sms_url' => 'Sms Url',
            'send_type' => 'Send Type',
            'content' => 'Content',
            'operator_user' => 'Operator User',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    
    
    
    
    
    
    
    
    
    
    
    
    
}

<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%driver_bind_change_record}}".
 *
 * @property int $id
 * @property int $driver_info_id 司机ID
 * @property string $bind_tag 绑定类型
 * @property string $create_time 记录时间
 * @property string $bind_value 绑定内容
 * @property int $bind_type 0绑定,1解绑
 * @property int $operator_id 操作人ID
 */
class DriverBindChangeRecord extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%driver_bind_change_record}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['driver_info_id', 'bind_tag', 'bind_value', 'operator_id'], 'required'],
            [['driver_info_id', 'bind_type', 'operator_id'], 'integer'],
            [['create_time'], 'safe'],
            [['bind_tag'], 'string', 'max' => 30],
            [['bind_value'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'driver_info_id' => 'Driver Info ID',
            'bind_tag' => 'Bind Tag',
            'create_time' => 'Create Time',
            'bind_value' => 'Bind Value',
            'bind_type' => 'Bind Type',
            'operator_id' => 'Operator ID',
        ];
    }
}

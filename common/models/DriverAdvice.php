<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%driver_advice}}".
 *
 * @property int $id
 * @property int $driver_id 司机id
 * @property varchar $advice_type 反馈类型
 * @property string $advice_desc 反馈内容
 * @property string $phone 司机电话
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class DriverAdvice extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%driver_advice}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['driver_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['advice_type'], 'string', 'max' => 64],
            [['advice_desc'], 'string', 'max' => 255],
            [['advice_image'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'driver_id'   => 'Driver ID',
            'advice_type' => 'Advice Type',
            'advice_desc' => 'Advice Desc',
            'advice_image' => 'Advice Image',
            'phone' => 'Phone',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

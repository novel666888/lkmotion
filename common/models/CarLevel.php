<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "{{%car_level}}".
 *
 * @property int $id
 * @property string $label 车辆级别标签
 * @property string $create_time
 * @property string $update_time
 * @property int $operator_id 操作人ID
 * @property int $enable 状态:0未启用1启用
 */
class CarLevel extends \common\models\BaseModel
{
    use ModelTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%car_level}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['create_time', 'update_time'], 'safe'],
            [['operator_id', 'enable'], 'integer'],
            [['label'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'label' => 'Label',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'operator_id' => 'Operator ID',
            'enable' => 'Enable',
        ];
    }
}

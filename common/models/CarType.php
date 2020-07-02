<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "{{%car_type}}".
 *
 * @property int $id
 * @property string $brand 品牌
 * @property string $model 型号
 * @property int $seats 座位数量
 * @property string $city 城市
 * @property string $type_desc
 * @property string $img_url 车型图片地址
 * @property int $status 0：未启用，1：已启用
 * @property string $create_time 录入时间
 * @property string $update_time 修改时间
 * @property int $operator_id 操作人
 */
class CarType extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%car_type}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['seats', 'status', 'operator_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['brand', 'model', 'city'], 'string', 'max' => 30],
            [['type_desc'], 'string', 'max' => 60],
            [['img_url'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'brand' => 'Brand',
            'model' => 'Model',
            'seats' => 'Seats',
            'city' => 'City',
            'type_desc' => 'Type Desc',
            'img_url' => 'Img Url',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'operator_id' => 'Operator ID',
        ];
    }
}

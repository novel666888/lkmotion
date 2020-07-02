<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "tbl_passenger_contact".
 *
 * @property string $id
 * @property string $passenger_info_id
 * @property string $name 紧急联系人姓名
 * @property string $phone
 * @property int $is_del 0未删除1已删除
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class PassengerContact extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_passenger_contact';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['passenger_info_id'], 'required'],
            [['passenger_info_id', 'is_del'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['name', 'phone'], 'string', 'max' => 16],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'passenger_info_id' => 'Passenger Info ID',
            'name' => 'Name',
            'phone' => 'Phone',
            'is_del' => 'Is Del',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

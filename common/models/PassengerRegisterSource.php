<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "tbl_passenger_register_source".
 *
 * @property int $id
 * @property int $passenger_info_id
 * @property string $register_source
 * @property string $create_time
 * @property string $update_time
 */
class PassengerRegisterSource extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_passenger_register_source';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['passenger_info_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['register_source'], 'string', 'max' => 32],
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
            'register_source' => 'Register Source',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

<?php

namespace common\models;

/**
 * This is the model class for table "{{%passenger_history_car_person}}".
 *
 * @property int $id
 * @property int $passenger_info_id 订车人id
 * @property string $passenger_phone 订车人用户phone
 * @property string $use_car_person_name 用车人姓名
 * @property string $use_car_person_phone 用车人电话
 * @property string $create_time
 * @property string $update_time
 */
class PassengerHistoryCarPerson extends BaseModel
{
    const DEL_YES = 1;
    const DEL_NO = 0;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%passenger_history_car_person}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['passenger_info_id', 'passenger_phone'], 'required'],
            [['id', 'passenger_info_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['passenger_phone', 'use_car_person_phone'], 'string', 'max' => 100],
            [['use_car_person_name'], 'string', 'max' => 100],
            [['id'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'passenger_info_id' => 'Passenger Info ID',
            'passenger_phone' => 'Passenger Phone',
            'use_car_person_name' => 'Use Car Person Name',
            'use_car_person_phone' => 'Use Car Person Phone',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "tbl_passenger_wallet".
 *
 * @property int $id
 * @property int $passenger_info_id
 * @property double $capital 本金
 * @property double $give_fee
 * @property double $freeze_capital
 * @property double $freeze_give_fee
 * @property string $create_time
 * @property string $update_time
 */
class PassengerWallet extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_passenger_wallet';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['passenger_info_id'], 'integer'],
            [['capital', 'give_fee', 'freeze_capital', 'freeze_give_fee'], 'number'],
            [['create_time', 'update_time'], 'safe'],
            [['passenger_info_id'], 'unique'],
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
            'capital' => 'Capital',
            'give_fee' => 'Give Fee',
            'freeze_capital' => 'Freeze Capital',
            'freeze_give_fee' => 'Freeze Give Fee',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

}

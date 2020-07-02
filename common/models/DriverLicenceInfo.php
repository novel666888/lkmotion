<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%driver_licence_info}}".
 *
 * @property int $id 审核记录id
 * @property int $driver_id 司机id
 * @property string $main_driving_license 驾照主页照片地址
 * @property string $vice_driving_license 驾照副页照片地址
 * @property string $all_driving_license 驾照正副页照片地址
 * @property string $group_driving_license 手持驾照照片地址
 * @property string $identity_card_id 身份证
 * @property string $main_idcard_license 身份证正面照
 * @property string $vice_idcard_license 身份证副页照片地址
 * @property string $group_idcard_license 手持身份证照片
 * @property string $plate_number 车牌号
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class DriverLicenceInfo extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%driver_licence_info}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['driver_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['main_driving_license', 'vice_driving_license', 'all_driving_license', 'group_driving_license', 'main_idcard_license', 'vice_idcard_license', 'group_idcard_license'], 'string', 'max' => 256],
            [['identity_card_id'], 'string', 'max' => 32],
            [['plate_number'], 'string', 'max' => 16],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'driver_id' => 'Driver ID',
            'main_driving_license' => 'Main Driving License',
            'vice_driving_license' => 'Vice Driving License',
            'all_driving_license' => 'All Driving License',
            'group_driving_license' => 'Group Driving License',
            'identity_card_id' => 'Identity Card ID',
            'main_idcard_license' => 'Main Idcard License',
            'vice_idcard_license' => 'Vice Idcard License',
            'group_idcard_license' => 'Group Idcard License',
            'plate_number' => 'Plate Number',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

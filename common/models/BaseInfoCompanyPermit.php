<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;
/**
 * This is the model class for table "{{%base_info_company_permit}}".
 *
 * @property int $id
 * @property string $certificate 网络预约出租汽车经营许可证号
 * @property string $operation_area 经营区域
 * @property string $owner_name 公司名称
 * @property string $organization 发证机构名称
 * @property string $start_date 有效期起
 * @property string $stop_date 有效期止
 * @property string $certify_date 初次发证日期
 * @property string $state 证照状态
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class BaseInfoCompanyPermit extends \yii\db\ActiveRecord
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%base_info_company_permit}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['certificate', 'operation_area', 'owner_name', 'organization', 'state'], 'required'],
            [['certificate', 'operation_area', 'owner_name', 'organization', 'state'], 'trim'],
            [['start_date', 'stop_date', 'certify_date', 'create_time', 'update_time'], 'safe'],
            [['certificate'], 'string', 'max' => 64],
            [['operation_area'], 'string', 'max' => 128],
            [['owner_name', 'organization'], 'string', 'max' => 256],
            [['state'], 'string', 'max' => 8],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'certificate' => 'Certificate',
            'operation_area' => 'Operation Area',
            'owner_name' => 'Owner Name',
            'organization' => 'Organization',
            'start_date' => 'Start Date',
            'stop_date' => 'Stop Date',
            'certify_date' => 'Certify Date',
            'state' => 'State',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

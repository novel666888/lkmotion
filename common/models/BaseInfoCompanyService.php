<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;
/**
 * This is the model class for table "{{%base_info_company_service}}".
 *
 * @property int $id
 * @property string $service_name 服务机构名称
 * @property string $service_no 服务机构代码
 * @property string $detail_address 服务机构地址
 * @property string $responsible_name 服务机构负责人姓名
 * @property string $responsible_phone 负责人联系电话
 * @property string $manager_name 服务机构管理人姓名
 * @property string $manager_phone 管理人联系电话
 * @property string $contact_phone 服务机构紧急联系电话
 * @property string $mail_address 行政文书送达邮寄地址
 * @property string $create_date 服务机构设立日期
 * @property int $state 状态
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class BaseInfoCompanyService extends \yii\db\ActiveRecord
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%base_info_company_service}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['service_name', 'service_no', 'detail_address', 'responsible_name', 'responsible_phone', 'manager_name', 'manager_phone', 'contact_phone', 'mail_address'], 'required'],
            [['create_date', 'create_time', 'update_time'], 'safe'],
            [['state'], 'integer'],
            [['service_name', 'detail_address', 'mail_address'], 'string', 'max' => 128],
            [['service_no', 'responsible_name', 'manager_name'], 'string', 'max' => 64],
            [['responsible_phone', 'manager_phone', 'contact_phone'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'service_name' => 'Service Name',
            'service_no' => 'Service No',
            'detail_address' => 'Detail Address',
            'responsible_name' => 'Responsible Name',
            'responsible_phone' => 'Responsible Phone',
            'manager_name' => 'Manager Name',
            'manager_phone' => 'Manager Phone',
            'contact_phone' => 'Contact Phone',
            'mail_address' => 'Mail Address',
            'create_date' => 'Create Date',
            'state' => 'State',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

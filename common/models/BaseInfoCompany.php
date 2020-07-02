<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;
/**
 * This is the model class for table "{{%base_info_company}}".
 *
 * @property int $id
 * @property string $company_id 公司标识
 * @property string $company_name 公司名称
 * @property string $identifier 统一社会信用代码
 * @property string $address 注册地行政区划代码
 * @property string $business_scope 经营范围
 * @property string $contact_address 通信地址
 * @property string $economic_type 经营业户经济类型
 * @property string $reg_capital 注册资本
 * @property string $legal_name 法人代表姓名
 * @property string $legal_id 法人代表身份证号
 * @property string $legal_phone 法人代表电话
 * @property string $legal_photo 法人代表身份证扫描件文件编号
 * @property int $state 状态0有效，1失效
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class BaseInfoCompany extends \yii\db\ActiveRecord
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%base_info_company}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id', 'company_name', 'identifier', 'business_scope', 'address','contact_address', 'economic_type', 'reg_capital', 'legal_name', 'legal_id', 'legal_phone', 'state'], 'required'],
            [['company_id', 'company_name', 'identifier', 'business_scope', 'address','contact_address', 'economic_type', 'reg_capital', 'legal_name', 'legal_id', 'legal_phone', 'state'], 'trim'],
            [['state'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['company_name', 'business_scope', 'contact_address', 'legal_name'], 'string', 'max' => 256],
            [['identifier', 'legal_id', 'legal_phone','company_id'], 'string', 'max' => 32],
            [['economic_type', 'reg_capital', 'legal_photo'], 'string', 'max' => 128],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_id' => 'Company Id',
            'company_name' => 'Company Name',
            'identifier' => 'Identifier',
            'address' => 'Address',
            'business_scope' => 'Business Scope',
            'contact_address' => 'Contact Address',
            'economic_type' => 'Economic Type',
            'reg_capital' => 'Reg Capital',
            'legal_name' => 'Legal Name',
            'legal_id' => 'Legal ID',
            'legal_phone' => 'Legal Phone',
            'legal_photo' => 'Legal Photo',
            'state' => 'State',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%driver_base_info}}".
 *
 * @property int $id
 * @property string $mobile_operator 手机运营商
 * @property string $company_logo 公司标识
 * @property string $administrative_code 注册行政地区代码
 * @property string $birthday 驾驶员出生日期
 * @property string $country 国籍
 * @property string $national 驾驶员民族
 * @property string $driving_licence_number 驾驶证编号
 * @property string $marital_status 婚姻状况
 * @property string $foreign_language_ability 外语能力
 * @property string $app_version 使用APP版本号
 * @property string $map_type 使用地图类型
 * @property string $education_background 驾驶员学历
 * @property string $household_registration_authority 户口   登记机关名称
 * @property string $registered_permanent_residence_address 户口   住址
 * @property string $address 现居地址
 * @property string $address_longitude 现居地址经度
 * @property string $address_latitude 现居地址纬度
 * @property string $driver_img_file_number 驾驶员照片文件编号
 * @property string $driver_license 机动车驾驶员证
 * @property string $driver_license_scan_copy_number 机动车驾驶证扫描件文件编号
 * @property string $driving_type 准驾车型
 * @property string $first_get_driver_license_date 初次领取驾驶证日期
 * @property string $driver_license_validity_start 驾驶证有效期起
 * @property string $driver_license_validity_end 驾驶证有效期止
 * @property int $is_taxi_driver 是否巡游出租车驾驶员
 * @property string $network_reservation_taxi_driver_license_number 网络预约出租汽车驾驶员证号
 * @property string $network_reservation_taxi_driver_license_issuing_agencies 网络预约出租汽车驾驶员证发证机构
 * @property string $certificate_issuing_date 资格证发证日期
 * @property string $first_qualification_date 初次领取资格证日期
 * @property string $qualification_certificate_validity_start 资格证有效期起
 * @property string $qualification_certificate_validity_end 资格证有效期止
 * @property string $reported_date 报备日期
 * @property int $is_full_time_driver 是否专职驾驶员
 * @property int $is_in_driver_blacklist 是否在黑名单
 * @property int $service_type 服务类型
 * @property string $company 驾驶员合同（或协议）签署公司
 * @property string $contract_start_date 合同开始时间
 * @property string $contract_end_date 合同结束时间
 * @property string $emergency_contact 紧急联系人
 * @property string $emergency_contact_phone_number 紧急联系人电话
 * @property string $emergency_contact_address 紧急联系人地址
 * @property string $training_courses 培训课名称
 * @property string $training_courses_date 培训课程日期
 * @property string $training_courses_start_date 培训开始日期
 * @property string $training_courses_end_date 培训结束日期
 * @property int $training_courses_time 培训时长
 * @property string $bank_name 开户行
 * @property string $bank_card_number 银行卡号
 * @property string $note 备注
 * @property string $qualification_certificate_img 从业资格证照片
 * @property string $other_img1 其他一
 * @property string $other_img2 其他二
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class DriverBaseInfo extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%driver_base_info}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['id', 'is_taxi_driver', 'is_full_time_driver', 'is_in_driver_blacklist', 'service_type', 'training_courses_time'], 'integer'],
            [['birthday', 'first_get_driver_license_date', 'driver_license_validity_start', 'driver_license_validity_end', 'certificate_issuing_date', 'first_qualification_date', 'qualification_certificate_validity_start', 'qualification_certificate_validity_end', 'reported_date', 'contract_start_date', 'contract_end_date', 'training_courses_date', 'training_courses_start_date', 'training_courses_end_date', 'create_time', 'update_time'], 'safe'],
            [['mobile_operator'], 'string', 'max' => 255],
            [['company_logo', 'administrative_code', 'country', 'national', 'driving_licence_number', 'marital_status', 'foreign_language_ability', 'app_version', 'map_type', 'education_background', 'household_registration_authority', 'registered_permanent_residence_address', 'driver_img_file_number', 'driver_license', 'driver_license_scan_copy_number', 'driving_type', 'network_reservation_taxi_driver_license_number', 'network_reservation_taxi_driver_license_issuing_agencies', 'company', 'emergency_contact', 'emergency_contact_phone_number', 'emergency_contact_address', 'training_courses', 'bank_name', 'bank_card_number', 'note'], 'string', 'max' => 64],
            [['address'], 'string', 'max' => 736],
            [['address_longitude', 'address_latitude'], 'string', 'max' => 32],
            [['qualification_certificate_img'], 'string', 'max' => 256],
            [['other_img1', 'other_img2'], 'string', 'max' => 128],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mobile_operator' => 'Mobile Operator',
            'company_logo' => 'Company Logo',
            'administrative_code' => 'Administrative Code',
            'birthday' => 'Birthday',
            'country' => 'Country',
            'national' => 'National',
            'driving_licence_number' => 'Driving Licence Number',
            'marital_status' => 'Marital Status',
            'foreign_language_ability' => 'Foreign Language Ability',
            'app_version' => 'App Version',
            'map_type' => 'Map Type',
            'education_background' => 'Education Background',
            'household_registration_authority' => 'Household Registration Authority',
            'registered_permanent_residence_address' => 'Registered Permanent Residence Address',
            'address' => 'Address',
            'address_longitude' => 'Address Longitude',
            'address_latitude' => 'Address Latitude',
            'driver_img_file_number' => 'Driver Img File Number',
            'driver_license' => 'Driver License',
            'driver_license_scan_copy_number' => 'Driver License Scan Copy Number',
            'driving_type' => 'Driving Type',
            'first_get_driver_license_date' => 'First Get Driver License Date',
            'driver_license_validity_start' => 'Driver License Validity Start',
            'driver_license_validity_end' => 'Driver License Validity End',
            'is_taxi_driver' => 'Is Taxi Driver',
            'network_reservation_taxi_driver_license_number' => 'Network Reservation Taxi Driver License Number',
            'network_reservation_taxi_driver_license_issuing_agencies' => 'Network Reservation Taxi Driver License Issuing Agencies',
            'certificate_issuing_date' => 'Certificate Issuing Date',
            'first_qualification_date' => 'First Qualification Date',
            'qualification_certificate_validity_start' => 'Qualification Certificate Validity Start',
            'qualification_certificate_validity_end' => 'Qualification Certificate Validity End',
            'reported_date' => 'Reported Date',
            'is_full_time_driver' => 'Is Full Time Driver',
            'is_in_driver_blacklist' => 'Is In Driver Blacklist',
            'service_type' => 'Service Type',
            'company' => 'Company',
            'contract_start_date' => 'Contract Start Date',
            'contract_end_date' => 'Contract End Date',
            'emergency_contact' => 'Emergency Contact',
            'emergency_contact_phone_number' => 'Emergency Contact Phone Number',
            'emergency_contact_address' => 'Emergency Contact Address',
            'training_courses' => 'Training Courses',
            'training_courses_date' => 'Training Courses Date',
            'training_courses_start_date' => 'Training Courses Start Date',
            'training_courses_end_date' => 'Training Courses End Date',
            'training_courses_time' => 'Training Courses Time',
            'bank_name' => 'Bank Name',
            'bank_card_number' => 'Bank Card Number',
            'note' => 'Note',
            'qualification_certificate_img' => 'Qualification Certificate Img',
            'other_img1' => 'Other Img1',
            'other_img2' => 'Other Img2',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}

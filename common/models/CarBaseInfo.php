<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%car_base_info}}".
 *
 * @property int $id 车辆id
 * @property string $company_logo 公司标识
 * @property string $car_label 车辆厂牌
 * @property string $car_base_type 车辆类型
 * @property string $car_owner 车辆所有人
 * @property string $plate_color 车牌颜色
 * @property string $engine_number 发动机号电动机号
 * @property string $vin_number vin码
 * @property string $register_time 注册日期
 * @property string $fuel_type 燃料类型
 * @property string $engine_capacity 发动机排量
 * @property string $car_img_file_number 车辆照片文件编号
 * @property string $transport_number 运输证字号
 * @property string $transport_issuing_authority 车辆运输证发证机构
 * @property string $business_area 经营区域
 * @property string $transport_certificate_validity_start 车辆运输证有效期起
 * @property string $transport_certificate_validity_end 车辆运输证有效期止
 * @property string $first_register_time 车辆初次登记日期
 * @property string $state_of_repair 车辆检修状态
 * @property string $next_annual_inspection_time 下次年检时间
 * @property string $annual_audit_status 年度审核状态
 * @property string $invoice_printing_equipment_number 发票打印设备序列号
 * @property string $gps_brand 卫星定位装置品牌
 * @property string $gps_model 型号
 * @property string $gps_imei imei
 * @property string $gps_install_time 安装日期
 * @property string $report_time 报备日期
 * @property string $service_type 服务类型
 * @property string $charge_type_code 运价类型编码
 * @property string $car_invoice_img 车辆发票
 * @property string $quality_certificate_img 合格证
 * @property string $vehicle_license_img 行驶证
 * @property string $registration_certificate_img 登记证书
 * @property string $tax_payment_certificate_img 完税证明
 * @property string $transport_certificate_img 汽车运输证
 * @property string $other_img1
 * @property string $other_img2
 */
class CarBaseInfo extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%car_base_info}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'register_time', 'transport_certificate_validity_start', 'transport_certificate_validity_end', 'first_register_time', 'next_annual_inspection_time', 'gps_install_time', 'report_time'], 'required'],
            [['id'], 'integer'],
            [['register_time', 'transport_certificate_validity_start', 'transport_certificate_validity_end', 'first_register_time', 'next_annual_inspection_time', 'gps_install_time', 'report_time'], 'safe'],
            [['company_logo', 'car_label', 'car_base_type', 'car_owner', 'plate_color', 'engine_number', 'vin_number', 'fuel_type', 'engine_capacity', 'car_img_file_number', 'transport_number', 'transport_issuing_authority', 'business_area', 'state_of_repair', 'annual_audit_status', 'invoice_printing_equipment_number', 'gps_brand', 'gps_model', 'gps_imei', 'service_type', 'charge_type_code'], 'string', 'max' => 64],
            [['car_invoice_img', 'quality_certificate_img', 'vehicle_license_img', 'registration_certificate_img', 'tax_payment_certificate_img', 'transport_certificate_img', 'other_img1', 'other_img2'], 'string', 'max' => 128],
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
            'company_logo' => 'Company Logo',
            'car_label' => 'Car Label',
            'car_base_type' => 'Car Base Type',
            'car_owner' => 'Car Owner',
            'plate_color' => 'Plate Color',
            'engine_number' => 'Engine Number',
            'vin_number' => 'Vin Number',
            'register_time' => 'Register Time',
            'fuel_type' => 'Fuel Type',
            'engine_capacity' => 'Engine Capacity',
            'car_img_file_number' => 'Car Img File Number',
            'transport_number' => 'Transport Number',
            'transport_issuing_authority' => 'Transport Issuing Authority',
            'business_area' => 'Business Area',
            'transport_certificate_validity_start' => 'Transport Certificate Validity Start',
            'transport_certificate_validity_end' => 'Transport Certificate Validity End',
            'first_register_time' => 'First Register Time',
            'state_of_repair' => 'State Of Repair',
            'next_annual_inspection_time' => 'Next Annual Inspection Time',
            'annual_audit_status' => 'Annual Audit Status',
            'invoice_printing_equipment_number' => 'Invoice Printing Equipment Number',
            'gps_brand' => 'Gps Brand',
            'gps_model' => 'Gps Model',
            'gps_imei' => 'Gps Imei',
            'gps_install_time' => 'Gps Install Time',
            'report_time' => 'Report Time',
            'service_type' => 'Service Type',
            'charge_type_code' => 'Charge Type Code',
            'car_invoice_img' => 'Car Invoice Img',
            'quality_certificate_img' => 'Quality Certificate Img',
            'vehicle_license_img' => 'Vehicle License Img',
            'registration_certificate_img' => 'Registration Certificate Img',
            'tax_payment_certificate_img' => 'Tax Payment Certificate Img',
            'transport_certificate_img' => 'Transport Certificate Img',
            'other_img1' => 'Other Img1',
            'other_img2' => 'Other Img2',
        ];
    }
}

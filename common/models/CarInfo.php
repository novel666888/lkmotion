<?php

namespace common\models;

use common\services\traits\ModelTrait;

/**
 * This is the model class for table "{{%car_info}}".
 *
 * @property int $id 车辆id
 * @property string $plate_number 车牌号
 * @property int $operation_status 运营状态：-1，删除 0，上架 1，下架 2，待整备
 * @property string $publish_time 上架时间
 * @property string $full_name 车辆全名
 * @property string $color 车身颜色
 * @property string $car_img 汽车图片
 * @property string $city 城市
 * @property int $car_type 车辆类型
 * @property int $car_level 车辆级别
 * @property string $regist_date 上牌日期
 * @property string $insurance_start_date 保险生效日期
 * @property string $insurance_end_date 保险失效日期
 * @property string $annual_end_date 年检到期日期
 * @property string $car_license_img 行驶本图片地址
 * @property string $remark 备注
 * @property int $car_config 车型配置
 * @property int $use_status 启用停用状态，0：停用，1：启用
 * @property string $large_screen_device_code
 * @property string $large_screen_device_brand 大屏品牌名称
 * @property string $car_screen_device_code 车机设备号
 * @property string $car_screen_device_brand 车机品牌名称
 * @property string $create_time 创建时间
 * @property string $update_time
 * @property int $operator_id 操作人ID
 */
class CarInfo extends \common\models\BaseModel
{
    use ModelTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%car_info}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['operation_status'], 'required'],
            [['operation_status', 'car_type', 'car_level', 'car_config', 'use_status', 'operator_id'], 'integer'],
            [['publish_time', 'regist_date', 'insurance_start_date', 'insurance_end_date', 'annual_end_date', 'create_time', 'update_time'], 'safe'],
            [['plate_number'], 'string', 'max' => 16],
            [['full_name', 'large_screen_device_brand', 'car_screen_device_brand'], 'string', 'max' => 64],
            [['color'], 'string', 'max' => 30],
            [['car_img', 'car_license_img', 'remark', 'large_screen_device_code', 'car_screen_device_code'], 'string', 'max' => 256],
            [['city'], 'string', 'max' => 8],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'plate_number' => 'Plate Number',
            'operation_status' => 'Operation Status',
            'publish_time' => 'Publish Time',
            'full_name' => 'Full Name',
            'color' => 'Color',
            'car_img' => 'Car Img',
            'city' => 'City',
            'car_type' => 'Car Type',
            'car_level' => 'Car Level',
            'regist_date' => 'Regist Date',
            'insurance_start_date' => 'Insurance Start Date',
            'insurance_end_date' => 'Insurance End Date',
            'annual_end_date' => 'Annual End Date',
            'car_license_img' => 'Car License Img',
            'remark' => 'Remark',
            'car_config' => 'Car Config',
            'use_status' => 'Use Status',
            'large_screen_device_code' => 'Large Screen Device Code',
            'large_screen_device_brand' => 'Large Screen Device Brand',
            'car_screen_device_code' => 'Car Screen Device Code',
            'car_screen_device_brand' => 'Car Screen Device Brand',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'operator_id' => 'Operator ID',
        ];
    }

    /**
     * 获取车辆详情
     *
     * @param int $driverId
     * @return array
     */
    public static function getCarDetail($carId)
    {
        $query = self::find();
        $carDetail = $query->select(['*'])->where(['id' => $carId])->asArray()->one();
        return $carDetail;
    }

    /**
     * 检查司机是否注册
     *
     * @param int $driverId
     * @return boolean
     */
    public static function checkCar($carId)
    {
        $isHave = self::fetchOne(['id' => $carId]);
        if (empty($isHave)) {
            return false;
        }
        return true;
    }

    /**
     * 检测乘客大屏设备是否绑定
     * @param $deviceCode
     * @return bool|mixed
     */
    public static function checkPassengerScreen($deviceCode)
    {
        \Yii::info($deviceCode, 'devicecode_carinfo');
        $carInfo = self::find()->where(['large_screen_device_code' => $deviceCode])->limit(1)->one();
        if (!$carInfo) {
            return false;
        }
        \Yii::info($carInfo, 'check_carinfo');
        return $carInfo->id;
    }

    /**
     * 检测车机设备是否绑定
     * @param $deviceCode
     * @return bool|mixed
     */
    public static function checkDriverScreen($deviceCode)
    {
        $carInfo = self::find()->where(['car_screen_device_code' => $deviceCode])->limit(1)->one();
        if (!$carInfo) {
            return false;
        }
        return $carInfo->id;
    }

    /**
     * 根据车辆ID获取司机和乘客deID
     * @param $carId
     * @return object
     */
    public static function getScreenDeviceCodesById($carId)
    {
        $carInfo = self::find()
            ->where(['id' => intval($carId)])
            ->select('large_screen_device_code,car_screen_device_code')
            ->limit(1)
            ->one();
        $result = new \stdClass();
        $result->driverDevice = $carInfo ? $carInfo->car_screen_device_code : '';
        $result->passengerDevice = $carInfo ? $carInfo->large_screen_device_code : '';
        return $result;
    }
}

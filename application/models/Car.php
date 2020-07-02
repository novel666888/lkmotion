<?php
/**
 * Created by PhpStorm.
 * User: xujie
 * Date: 18-8-31
 * Time: 上午10:47
 */

namespace application\models;


use common\models\CarBaseInfo;
use common\models\CarBindChangeRecord;
use common\models\CarInfo;
use common\services\YesinCarHttpClient;
use common\util\Common;
use yii\base\UserException;
use yii\helpers\ArrayHelper;

class Car
{
    public static function storePhotoInfo($photoInfo)
    {
        $urlMap = self::getImgKeys();
        $success = $field = 0;
        $fieldVin = [];
        foreach ($photoInfo as $plate => $photos) {
            $tmp = [];
            foreach ($photos as $pos => $photo) {
                $key = $urlMap[$pos] ?? false;
                if ($key) {
                    $tmp[$key] = $photo->path;
                }
            }
            if (!$tmp) continue;
            $result = self::storeUrl($plate, $tmp);
            if ($result) $success++;
            else {
                $field++;
                $fieldVin[] = $plate;
            }
        }
        $message = "成功{$success}条,失败{$field}条";
        if (count($fieldVin)) {
            $message .= '失败的车辆车牌码:' . implode($fieldVin);
        }
        return $message;
    }

    public static function getRunTimeInfo($carIds)
    {

    }

    public static function storeCar($reqParams)
    {
        return self::makeCarRequest($reqParams);
    }

    public static function updateCar($reqParams)
    {
        return self::makeCarRequest($reqParams, 'carUpdate');
    }

    public static function changeStatus($reqParams)
    {
        return self::makeCarRequest($reqParams, 'carStatus');
    }

    private static function makeCarRequest(&$reqParams, $action = 'carStore')
    {
        $apiConf = \Yii::$app->params['api'];
        $server = ArrayHelper::getValue($apiConf, 'account.serverName');
        $methodPath = ArrayHelper::getValue($apiConf, 'account.method.' . $action);
        try {
            $httpClient = new YesinCarHttpClient(['serverURI' => $server]);
            $responseData = $httpClient->post($methodPath, $reqParams);
            if (!isset($responseData['code'])) {
                return 'JAVA服务器返回数据异常';
            }
        } catch (UserException $exception) {
            return 'JAVA服务器连接异常';
        }
        return $responseData;
    }

    /**
     * @param $id
     * @param $oldParams
     * @param $newParams
     * @return bool
     */
    public static function recordChange($id, $oldParams, $newParams)
    {
        if (!$id) {
            return false;
        }
        foreach ($newParams as $key => $new) {
            $old = $oldParams[$key] ?? '';
            if ($old == $new) {
                continue;
            }
            if ($key == 'plate_number') {
                self::triggerPlateNumber($id, $old, $new);
            } elseif ($key == 'large_screen_device_code') {
                self::triggerDriverDevice($id, $oldParams, $newParams);
            } elseif ($key == 'car_screen_device_code') {
                self::triggerPassengerDevice($id, $oldParams, $newParams);
            }
        }
        return true;
    }

    private static function triggerPlateNumber($id, $old, $new)
    {
        $bindInfo = self::getBindInfo($id);
        $data = ['car_info_id' => $id, 'bind_tag' => 'plate'];
        if ($old) {
            $bindInfo['plateNumber'] = $old;
            $data['bind_type'] = 1;
            $data['bind_value'] = json_encode($bindInfo, 256);
            self::storeBindInfo($data);
        }
        if ($new) {
            $bindInfo['plateNumber'] = $new;
            $data['bind_type'] = 0;
            $data['bind_value'] = json_encode($bindInfo, 256);
            self::storeBindInfo($data);
        }
    }

    private static function triggerDriverDevice($id, $oldParams, $newParams)
    {
        $bindInfo = self::getBindInfo($id);
        $data = ['car_info_id' => $id, 'bind_tag' => 'driverDevice'];
        if ($oldParams['car_screen_device_code']) {
            $bindInfo['car_screen_device_code'] = $oldParams['car_screen_device_code'];
            $bindInfo['car_screen_device_brand'] = $oldParams['car_screen_device_brand'];
            $data['bind_type'] = 1;
            $data['bind_value'] = json_encode(Common::key2lowerCamel($bindInfo), 256);
            self::storeBindInfo($data);
        }
        if ($newParams['car_screen_device_code']) {
            $bindInfo['car_screen_device_code'] = $newParams['car_screen_device_code'];
            $bindInfo['car_screen_device_brand'] = $newParams['car_screen_device_brand'];
            $data['bind_type'] = 0;
            $data['bind_value'] = json_encode(Common::key2lowerCamel($bindInfo), 256);
            self::storeBindInfo($data);
        }
    }

    private static function triggerPassengerDevice($id, $oldParams, $newParams)
    {
        $bindInfo = self::getBindInfo($id);
        $data = ['car_info_id' => $id, 'bind_tag' => 'passengerDevice'];
        if ($oldParams['large_screen_device_code']) {
            $bindInfo['large_screen_device_code'] = $oldParams['large_screen_device_code'];
            $bindInfo['large_screen_device_brand'] = $oldParams['large_screen_device_brand'];
            $data['bind_type'] = 1;
            $data['bind_value'] = json_encode(Common::key2lowerCamel($bindInfo), 256);
            self::storeBindInfo($data);
        }
        if ($newParams['large_screen_device_code']) {
            $bindInfo['large_screen_device_code'] = $newParams['large_screen_device_code'];
            $bindInfo['large_screen_device_brand'] = $newParams['large_screen_device_brand'];
            $data['bind_type'] = 0;
            $data['bind_value'] = json_encode(Common::key2lowerCamel($bindInfo), 256);
            self::storeBindInfo($data);
        }
    }

    private static function getBindInfo($id)
    {
        $vinNumber = CarBaseInfo::find()->where(['id' => $id])->select('vin_number')->limit(1)->scalar();
        $bindInfo = ['vinNumber' => $vinNumber];
        return $bindInfo;
    }

    private static function storeBindInfo($data)
    {
        $record = new CarBindChangeRecord();
        $record->attributes = $data;
        $record->create_time = date('Y-m-d H:i:s');
        $record->operator_id = 0;  //!!!!!!!!
        $record->save();
        if (!$record->id) {
            \Yii::debug('保存变更信息保存失败', 'car.change');
            return false;
        }
        return true;
    }

    private static function storeUrl($plate, $data)
    {
        $carInfo = CarInfo::findOne(['plate_number' => $plate]);
        if (!$carInfo) {
            return false;
        }
        $car = CarBaseInfo::findOne(['id' => $carInfo->id]);
        if (!$car) {
            return false;
        }
        $car->setAttributes($data);
        if(!$car->save(false)){
            \Yii::info([$car->getErrors(),$data], 'car_storePhotoInfo');
            return false;
        }else{
            return true;
        }
    }


    /** changeBindChangeRecord
     * @param  array $resCarData
     * @return  bool
     * @author liurn
     */
    public static function changeBindChangeRecord($resCarData)
    {
        \Yii::info($resCarData, 'getData');
        if (!$resCarData) return false;
        if (!$resCarData['carScreenDeviceBrand'] && !$resCarData['carScreenDeviceCode'] && !$resCarData['largeScreenDeviceBrand'] && !$resCarData['largeScreenDeviceCode']
            && !$resCarData['plateNumber'] && !$resCarData['vinNumber']) return false;

        $carInfoId = $resCarData['carInfoId'];
        $operatorId = $resCarData['operator_id'];

        if (!$resCarData['carScreenDeviceBrand'] && !$resCarData['carScreenDeviceCode']) $screen = array();
        else
            $screen['driverDevice'] = $resCarData['carScreenDeviceBrand'];
        $screen['deviceCode'] = $resCarData['carScreenDeviceCode'];
        if (!$resCarData['largeScreenDeviceBrand'] && !$resCarData['largeScreenDeviceCode']) $large = array();
        else
            $large['passengerDevice'] = $resCarData['largeScreenDeviceBrand'];
        $large['deviceCode'] = $resCarData['largeScreenDeviceCode'];
        if (!$resCarData['plateNumber'] && !$resCarData['vinNumber']) $plate = array();
        else
            $plate['plate'] = $resCarData['plateNumber'];
        $plate['vinNumber'] = $resCarData['vinNumber'];

        $carData[0] = $screen ? json_encode($screen) : null;
        $carData[1] = $large ? json_encode($large) : null;
        $carData[2] = $plate ? json_encode($plate) : null;
        $carDataRes = array_filter($carData);
        \Yii::info($carDataRes, 'carDataRes');
        $bindRecord = CarBindChangeRecord::getBindRecord($carInfoId);
        \Yii::info($bindRecord, 'bindRecord');
        if (!$bindRecord)
            CarBindChangeRecord::addBindRecord($carDataRes, $carInfoId, $operatorId);
        else
            foreach ($carDataRes as $key => $value) {
                if (!in_array($value, $bindRecord))
                    CarBindChangeRecord::updateBindRecord($value, $carInfoId, $operatorId);
            }
    }

    public static function compactCarInfo($data)
    {
        $carBaseInfo = [
            "annualAuditStatus", "businessArea", "carBaseType", "carImgFileNumber", "carInvoiceImg", 'carBrainPlate',
            "carLabel", "carOwner", "chargeTypeCode", "companyLogo", "engineCapacity", "engineNumber", 'carBrainNumber',
            "firstRegisterTime", "fuelType", "gpsBrand", "gpsImei", "gpsInstallTime",
            "gpsModel", "invoicePrintingEquipmentNumber", "nextAnnualInspectionTime", "otherImg1",
            "otherImg2", "plateColor", "qualityCertificateImg", "registerTime", "registrationCertificateImg",
            "reportTime", "serviceType", "stateOfRepair", "taxPaymentCertificateImg", "transportCertificateImg",
            "transportCertificateValidityEnd", "transportCertificateValidityStart",
            "transportIssuingAuthority", "transportNumber", "vehicleLicenseImg", "vinNumber"
        ];
        $carInfo = [
            'annualEndDate', 'carConfig', 'carImg', 'carLevelId', 'carLicenseImg', 'carScreenDeviceBrand',
            'carScreenDeviceCode', 'carType', 'cityCode', 'color', 'fullName', 'carTypeId',
            'insuranceEndDate', 'insuranceStartDate', 'largeScreenDeviceBrand', 'largeScreenDeviceCode',
            'operationStatus', 'plateNumber', 'publishTime', 'registDate', 'remark', 'useStatus', 'totalMile', 'assetCoding'
        ];
        $reqParams = ['data' => [
            'carInfo' => self::getGroupParams($carInfo, $data),
            'carBaseInfo' => self::getGroupParams($carBaseInfo, $data),
        ]];
        return $reqParams;
    }

    /**
     * @param $group
     * @param $data
     * @return array
     */
    private static function getGroupParams($group, $data)
    {
        // 需要转换的分组
        $transParams = [
            'firstRegisterTime', 'gpsInstallTime',
            'nextAnnualInspectionTime', 'registerTime',
            'reportTime', 'publishTime', 'registDate',
            'transportCertificateValidityStart', 'transportCertificateValidityEnd',
            'annualEndDate', 'insuranceEndDate', 'insuranceStartDate',
        ];
        $request = \Yii::$app->request;
        $tmp = ['id' => intval($request->post('id'))];
        foreach ($group as $item) {
            $val = $data[$item] ?? null;
            if ($val === null) { // 过滤空值
                continue;
            }
            if (in_array($item, $transParams)) {
                $tmp[$item] = strtotime($val) * 1000;
            } else {
                $tmp[$item] = $val;
            }
        }
        return $tmp;
    }

    public static function getImgKeys()
    {
        return ['_0', // 编号占位
            'car_invoice_img',
            'quality_certificate_img',
            'vehicle_license_img',
            'registration_certificate_img',
            'tax_payment_certificate_img',
            'transport_certificate_img',
            'other_img1',
            'other_img2',
        ];
    }

}
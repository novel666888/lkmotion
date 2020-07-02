<?php
/**
 * Created by PhpStorm.
 * User: xujie
 * Date: 18-8-31
 * Time: 上午10:47
 */

namespace application\models;


use application\modules\order\models\SysUser;
use common\logic\BaseInfoLogic;
use common\models\CarBaseInfo;
use common\models\CarBindChangeRecord;
use common\models\CarInfo;
use common\models\DriverBaseInfo;
use common\models\DriverBindChangeRecord;
use common\models\DriverInfo;
use common\models\DriverLicenceInfo;
use common\models\ListArray;
use common\services\YesinCarHttpClient;
use common\util\Common;
use yii\base\UserException;
use yii\helpers\ArrayHelper;

class Driver
{
    public static function storePhotoInfo($photoInfo)
    {
        $urlMap = self::getImgKeys();
        $success = $field = 0;
        $fieldIdNo = [];
        foreach ($photoInfo as $idNo => $photos) {
            $tmp = [];
            foreach ($photos as $pos => $photo) {
                $key = $urlMap[$pos] ?? false;
                if ($key) {
                    $tmp[$key] = $photo->path;
                }
            }
            if (!$tmp) continue;
            $result = self::storeUrl($idNo, $tmp);
            if ($result) $success++;
            else {
                $field++;
                $fieldIdNo[] = $idNo;
            }
        }
        $message = "成功{$success}条,失败{$field}条";
        if (count($fieldIdNo)) {
            $message .= '失败的司机身份证号:' . implode($fieldIdNo);
        }
        return $message;
    }

    public static function patchDriverInfo(&$drivers)
    {
        // 获取主管姓名 !!!!! 当前只有账号
        $leader = SysUser::find()->asArray()->all();
        $leaderInfo = array_column($leader, 'username', 'id');


        $deriverIds = array_column($drivers, 'id');
        // 获取司机手机号
        $listArray = new ListArray();
        $phoneMap = $listArray->getDriverPhoneNumberByIds($deriverIds);
        //var_dump($phoneMap);exit;
        // 获取司机身份证号码
        $result = DriverLicenceInfo::find()->where(['driver_id' => $deriverIds])->select('driver_id,identity_card_id')->asArray()->all();
        $idCardMap = array_column($result, 'identity_card_id', 'driver_id');
        // 获取绑定车辆信息
        $carIds = array_column($drivers, 'car_id');
        $carList = $listArray->getCarInfoListByIds($carIds);
        // 组装数据
        foreach ($drivers as $key => $driver) {
            $drivers[$key]['identity_card_id'] = $idCardMap[$driver['id']] ?? '';
            $drivers[$key]['phone_number'] = $phoneMap[$driver['id']] ?? '';
            $drivers[$key]['leader_name'] = $leaderInfo[$driver['driver_leader']] ?? '';
            $drivers[$key]['car_info'] = $carList[$driver['car_id']] ?? null;
        }
    }

    /**
     * @param $driverId
     * @return array|bool|null|\yii\db\ActiveRecord
     * @throws \yii\base\InvalidConfigException
     */
    public static function driverDetails($driverId)
    {
        $reqParams = ['id' => $driverId];
        $result = self::makeDriverRequest($reqParams, 'driverDetails');
        if (is_string($result)) {
            return $result;
        }
        $driverInfo = ['id' => $driverId];
        foreach ($result['data'] as $item) {
            unset($item['id']);
            $driverInfo = array_merge($driverInfo, $item);
        }
        foreach ($driverInfo as &$item) {
            $item = strval($item);
        }
        // 获取车牌号
        if (($driverInfo['carId'])) {
            $plate_number = CarInfo::find()->where(['id' => $driverInfo['carId']])->select('plate_number')->scalar();
            $driverInfo['plateNumber'] = $plate_number;
        }
        return $driverInfo;
    }

    public static function recordChange($id, $oldParams, $trigger)
    {
        if (!$trigger) {
            return true;
        }
        $newParams = self::driverDetails($id);
        foreach ($trigger as $item) {
            if ($item == 'carId') {
                self::triggerCarId($oldParams, $newParams);
            } elseif ($item == 'bankCardNumber') {
                self::triggerBankCardNumber($oldParams, $newParams);
            } elseif ($item == 'phoneNumber') {
                self::triggerPhoneNumber($oldParams, $newParams);
            }
        }
        return true;
    }

    public static function storeDriver($reqParams)
    {
        return self::makeDriverRequest($reqParams);
    }

    public static function updateDriver($reqParams)
    {
        return self::makeDriverRequest($reqParams, 'driverUpdate');
    }

    public static function changeStatus($reqParams)
    {
        return self::makeDriverRequest($reqParams, 'driverStatus');
    }

    private static function makeDriverRequest(&$reqParams, $action = 'driverStore')
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
            return 'JAVA服务器异常';
        }
        return $responseData;
    }

    public static function triggerCarId($oldParams, $newParams)
    {
        $oldId = $oldParams['carId'] ?? '';
        $newId = $newParams['carId'] ?? '';
        if ($oldId == $newId) {
            return false;
        }
        $levelList = (new ListArray())->getCarLevel(0);
        $levelMap = array_column($levelList, 'label', 'id');
        if ($oldId) {
            $carInfo = CarInfo::find()->where(['id' => $oldId])
                ->select('id,car_level_id,full_name,color,plate_number,total_mile')
                ->limit(1)->asArray()->one();
            $carBaseInfo = CarBaseInfo::findOne(['id' => $oldId]);
            if ($carInfo) {
                $carInfo['level_text'] = $levelMap[$carInfo['car_level_id']] ?? '';
                $carInfo['driver_name'] = $oldParams['driverName'] ?? '';
                $data = [
                    'driver_info_id' => $oldParams['id'],
                    'bind_tag' => 'car',
                    'bind_type' => 1, // 解绑
                    'bind_value' => json_encode(Common::key2lowerCamel($carInfo), 256)
                ];
                self::storeBindInfo($data);
                // 存储司机信息
                $driverInfo = [
                    'vinNumber' => $carBaseInfo ? $carBaseInfo->vin_number : '',
                    'plateNumber' => $carInfo['plate_number'],
                    'totalMile' => $carInfo['total_mile'],
                    'driverName' => $oldParams['driverName'] ?? '',
                    'phoneNumber' => $oldParams['phoneNumber'] ?? '',

                ];
                $data = [
                    'car_info_id' => $oldId,
                    'bind_tag' => 'driver',
                    'bind_type' => 1, // 解绑
                    'bind_value' => json_encode(Common::key2lowerCamel($driverInfo), 256)
                ];
                self::storeCarBindInfo($data);
            }
        }
        if ($newId) {
            $carInfo = CarInfo::find()->where(['id' => $newId])
                ->select('id,car_level_id,full_name,color,plate_number,total_mile')
                ->limit(1)->asArray()->one();
            $carBaseInfo = CarBaseInfo::findOne(['id' => $newId]);
            if ($carInfo) {
                $carInfo['level_text'] = $levelMap[$carInfo['car_level_id']] ?? '';
                $carInfo['driver_name'] = $newParams['driverName'] ?? '';
                $data = [
                    'driver_info_id' => $oldParams['id'],
                    'bind_tag' => 'car',
                    'bind_value' => json_encode(Common::key2lowerCamel($carInfo), 256)
                ];
                self::storeBindInfo($data);
            }
            // 存储司机信息
            $driverInfo = [
                'vinNumber' => $carBaseInfo ? $carBaseInfo->vin_number : '',
                'plateNumber' => $carInfo['plate_number'],
                'totalMile' => $carInfo['total_mile'],
                'driverName' => $oldParams['driverName'] ?? '',
                'phoneNumber' => $newParams['phoneNumber'] ?? '',

            ];
            $data = [
                'car_info_id' => $newId,
                'bind_tag' => 'driver',
                'bind_value' => json_encode(Common::key2lowerCamel($driverInfo), 256)
            ];
            self::storeCarBindInfo($data);
        }
        return true;
    }

    private static function triggerBankCardNumber($oldParams, $newParams)
    {
        $oldNumber = $oldParams['bankCardNumber'] ?? '';
        $newNumber = $newParams['bankCardNumber'] ?? '';
        if ($oldNumber == $newNumber) {
            return false;
        }
        if ($oldNumber) {
            $accountInfo = self::getBankAccountInfo($oldParams);
            $data = [
                'driver_info_id' => $oldParams['id'],
                'bind_tag' => 'bankAccount',
                'bind_type' => 1, // 解绑
                'bind_value' => json_encode(Common::key2lowerCamel($accountInfo), 256)
            ];
            self::storeBindInfo($data);

        }
        if ($newNumber) {
            $accountInfo = self::getBankAccountInfo($newParams);
            $data = [
                'driver_info_id' => $newParams['id'],
                'bind_tag' => 'bankAccount',
                'bind_value' => json_encode(Common::key2lowerCamel($accountInfo), 256)
            ];
            self::storeBindInfo($data);
        }
        return true;
    }

    private static function triggerPhoneNumber($oldParams, $newParams)
    {
        $oldNumber = $oldParams['phoneNumber'] ?? '';
        $newNumber = $newParams['phoneNumber'] ?? '';
        if ($oldNumber == $newNumber) {
            return false;
        }
        if ($oldNumber) {
            $phoneInfo = self::getPhoneInfo($oldParams);
            $data = [
                'driver_info_id' => $oldParams['id'],
                'bind_tag' => 'phone',
                'bind_type' => 1, // 解绑
                'bind_value' => json_encode(Common::key2lowerCamel($phoneInfo), 256)
            ];
            self::storeBindInfo($data);

        }
        if ($newNumber) {
            $phoneInfo = self::getPhoneInfo($newParams);
            $data = [
                'driver_info_id' => $newParams['id'],
                'bind_tag' => 'phone',
                'bind_value' => json_encode(Common::key2lowerCamel($phoneInfo), 256)
            ];
            self::storeBindInfo($data);
        }
        return true;
    }

    private static function getBankAccountInfo($data)
    {
        $accountInfo = [
            'driverName' => $data['driverName'] ?? '',
            'bankCardNumber' => $data['bankCardNumber'] ?? '',
            'bankName' => $data['bankName'] ?? '',
            'identityCardId' => $data['identityCardId'] ?? '',
        ];
        return $accountInfo;
    }

    private static function getPhoneInfo($data)
    {
        $accountInfo = [
            'driverName' => $data['driverName'] ?? '',
            'phoneNumber' => $data['phoneNumber'] ?? '',
            'identityCardId' => $data['identityCardId'] ?? '',
        ];
        return $accountInfo;
    }

    private static function storeBindInfo($data)
    {
        $record = new DriverBindChangeRecord();
        $record->attributes = $data;
        $record->create_time = date('Y-m-d H:i:s');
        $record->operator_id = self::getOperatorId();
        $record->save();
        if (!$record->id) {
            \Yii::debug('保存变更信息保存失败', 'driver.change');
            return false;
        }
        if (!isset($data['bind_tag']) || $data['bind_tag'] != 'car') {
            return true;
        }
        // 更新车辆缓存
        $driverId = $data['driver_info_id'] ?? 0;
        if ($data['bind_type'] == 1) { // 解绑
            $carId = 0;
        } else { // 绑定
            $bindValue = json_decode($data['bind_value']);
            $carId = is_object($bindValue) ? $bindValue->id : 0;
        }
        BaseInfoLogic::setDriverCarId($driverId, $carId);
        // 返回
        return true;
    }

    private static function storeCarBindInfo($data)
    {
        $record = new CarBindChangeRecord();
        $record->attributes = $data;
        $record->create_time = date('Y-m-d H:i:s');
        $record->operator_id = self::getOperatorId();
        $record->save();
        if (!$record->id) {
            \Yii::debug('保存变更信息保存失败', 'car.change');
            return false;
        }
        return true;
    }

    private static function storeUrl($idNo, $data)
    {
        $driverLicenceInfo = DriverLicenceInfo::findOne(['identity_card_id' => $idNo]);
        if (!$driverLicenceInfo) {
            return false;
        }
        $driverBaseInfo = DriverBaseInfo::findOne(['id' => $driverLicenceInfo->driver_id]);
        $driverInfo = DriverInfo::findOne(['id' => $driverLicenceInfo->driver_id]);
        if (!$driverBaseInfo || !$driverInfo) {
            return false;
        }
        $groups = [];
        foreach ($data as $key => $val) {
            $map = explode('.', $key);
            $groups[$map[0]][$map[1]] = $val;
        }
        foreach ($groups as $model => $attrs) {
            $$model->setAttributes($attrs);
            $$model->save();
        }
        return true;
    }

    /**
     * @param $data
     * @return array
     */
    public static function compactDriverInfo($data)
    {
        // 分组打包参数
        $driverBaseInfo = [
            'address', 'administrativeCode', 'appVersion', 'bankCardNumber', 'bankName', 'birthday',
            'certificateIssuingDate', 'company', 'companyLogo', 'contractEndDate', 'contractStartDate',
            'country', 'driverImgFileNumber', 'driverLeader', 'driverLicense', 'driverLicenseScanCopyNumber',
            'driverLicenseValidityEnd', 'driverLicenseValidityStart', 'drivingLicenceNumber', 'drivingType',
            'educationBackground', 'emergencyContact', 'emergencyContactAddress', 'emergencyContactPhoneNumber',
            'firstGetDriverLicenseDate', 'firstQualificationDate', 'foreignLanguageAbility',
            'householdRegistrationAuthority', 'id', 'idCardNumber', 'isFullTimeDriver', 'isInDriverBlacklist',
            'isTaxiDriver', 'mapType', 'maritalStatus', 'mobileOperator', 'national',
            'networkReservationTaxiDriverLicenseIssuingAgencies', 'networkReservationTaxiDriverLicenseNumber',
            'note', 'otherImg1', 'otherImg2', 'qualificationCertificateImg', 'qualificationCertificateValidityEnd',
            'qualificationCertificateValidityStart', 'registeredPermanentResidenceAddress', 'reportedDate',
            'serviceType', 'trainingCourses', 'trainingCoursesDate', 'trainingCoursesEndDate',
            'trainingCoursesStartDate', 'trainingCoursesTime',
        ];
        $driverLicenceInfo = [
            'allCarLicense', 'allDrivingLicense', 'driverName', 'groupCarLicense', 'groupDrivingLicense',
            'groupIdcardLicense', 'identityCardId', 'mainCarLicense', 'mainDrivingLicense', 'mainIdcardLicense',
            'phoneNumber', 'plateNumber', 'viceCarLicense', 'viceDrivingLicense', 'viceIdcardLicense',
        ];
        $driverInfo = [
            'bindTime', 'carId', 'cityCode', 'driverName', 'driverLeader', 'useStatus',
            'headImg', 'driverName', 'phoneNumber', 'gender', 'signStatus', 'tags',
        ];

        $reqParams = [
            'data' => [
                'driverInfo' => self::getGroupParams($driverInfo, $data),
                'driverBaseInfo' => self::getGroupParams($driverBaseInfo, $data),
                'driverLicenceInfo' => self::getGroupParams($driverLicenceInfo, $data),
            ]
        ];
        return $reqParams;
    }

    /**
     * @param $group
     * @return array
     */
    public static function getGroupParams($group, $data)
    {
        $transParams = [
            'firstGetDriverLicenseDate', 'reportedDate', 'contractStartDate',
            'driverLicenseValidityStart', 'driverLicenseValidityEnd',
            'certificateIssuingDate', 'firstQualificationDate', 'contractEndDate',
            'qualificationCertificateValidityStart', 'qualificationCertificateValidityEnd',
            'trainingCoursesDate', 'trainingCoursesStartDate', 'trainingCoursesEndDate', 'trainingCoursesTime',
        ];
        $request = \Yii::$app->request;
        $tmp = ['id' => intval($request->post('id'))];
        $tmp['driverId'] = $tmp['id'];
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

    public static function getImgKeys($filter = false)
    {
        $urlMap = [0,
            'driverInfo.head_img',
            'driverLicenceInfo.main_idcard_license',
            'driverLicenceInfo.vice_idcard_license',
            'driverLicenceInfo.group_idcard_license',
            'driverLicenceInfo.main_driving_license',
            'driverBaseInfo.qualification_certificate_img',
            'driverBaseInfo.other_img1',
            'driverBaseInfo.other_img2',
        ];
        if (!$filter) {
            return $urlMap;
        }
        $newMap = [];
        foreach ($urlMap as $key => $item) {
            if (!$item) continue;
            $tmp = explode('.', $item);
            $newMap[$tmp[1]] = $tmp[0];
        }
        return array_keys(Common::key2lowerCamel($newMap));
    }

    /**
     * @return int
     */
    private static function getOperatorId()
    {
        $userInfo = \Yii::$app->controller->userInfo ?? [];
        return ($userInfo && isset($userInfo['id'])) ? intval($userInfo['id']) : 0;
    }

}
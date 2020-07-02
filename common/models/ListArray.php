<?php
/**
 * Created by PhpStorm.
 * User: xujie
 * Date: 18-8-9
 * Time: 下午8:03
 */

namespace common\models;


use common\logic\HttpTrait;

class ListArray
{
    use HttpTrait;

    /**
     * getCityList --获取城市列表
     * @param bool $filter 是否过滤
     * @return array|BaseModel[]
     * @cache No
     */
    public function getCityList($filter = true)
    {
        $query = City::find()->select('city_code,city_name,city_longitude_latitude');
        $filter && $query->where(['city_status' => 1]);

        return $query->asArray()->all();
    }

    /**
     * 获取车牌号和车辆等级信息
     * @param $carIds
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getCarInfoListByIds($carIds)
    {
        $carLevel = $this->getCarLevel(0);
        $levelMap = array_column($carLevel, 'label', 'id');
        $carList = CarInfo::find()
            ->where(['id' => $carIds])
            ->select('id,plate_number,full_name,color,car_img,car_level_id')
            ->asArray()->indexBy('id')->all();
        foreach ($carList as $id => $item) {
            $carList[$id]['level_text'] = $levelMap[$item['car_level_id']] ?? '';
        }
        return $carList;
    }

    /**
     * getCarLevel --获取车辆级别列表
     * @param bool $filter 是否过滤
     * @return array|BaseModel[]
     * @cache No
     */
    public function getCarLevel($filter = true)
    {
        $query = CarLevel::find()->select('id,label');
        $filter && $query->where(['enable' => 1]);

        return $query->asArray()->all();
    }

    /**
     * getChannelList --获取渠道列表
     * @param bool $filter
     * @return array|BaseModel[]
     * @cache No
     */
    public function getChannelList($filter = true)
    {
        $query = Channel::find()->select(['id', 'channel_name']);
        $filter && $query->where(['channel_status' => 1]);

        return $query->asArray()->all();
    }

    /**
     * getServiceType --获取服务类型列表
     * @param bool $filter
     * @return array|BaseModel[]
     * @cache No
     */
    public function getServiceType($filter = true)
    {
        $query = ServiceType::find()->select(['id', 'service_type_name']);
        $filter && $query->where(['service_type_status' => 1]);

        return $query->asArray()->all();
    }

    public function getCouponClasses($displayPause = false, $classType = false)
    {
        $query = CouponClass::find()->select(['id', 'coupon_name', 'coupon_type', 'reduction_amount', 'discount']);
        if (!$displayPause) {
            $query->where(['is_pause' => 0]);
        }
        $classes = $query->indexBy('id')->asArray()->all();

        if ($classType) {
            return $classes;
        }
        $list = array_column($classes, 'coupon_name', 'id');
        return $list;
    }

    /**
     * 获取车型列表
     * @return array
     */
    public function getTypeList()
    {
        $list = CarType::find()->where(['status' => 1])->select('id,brand,model,seats,type_desc')->asArray()->all();
        return $list;
    }

    /**
     * @return array
     */
    public function getBrandMap()
    {
        $list = CarBrand::find()->select('id,pid,brand,model')->asArray()->all();
        $map = [];
        foreach ($list as $item) {
            if ($item['pid']) {
                $map[$item['id']] = $item['model'];
            } else {
                $map[$item['id']] = $item['brand'];
            }
        }
        return $map;
    }

    public function getPeopleTags($dimensions = 2)
    {
        $query = PeopleTag::find()->select(['id', 'tag_name', 'tag_type', 'tag_number']);
        $classes = $query->asArray()->all();

        if ($dimensions == 2) {
            return $classes;
        }
        $list = array_column($classes, 'tag_name', 'id');
        return $list;
    }

    public function pluckAdminNamesById($adminIds)
    {
        $info = SysUser::find()->where(['id' => $adminIds])->select('id,username')->asArray()->all();
        return array_column($info, 'username', 'id');
    }

    /**
     * 返回系统配置
     * @param string $key
     * @return mixed|string
     */
    public function getSysConfig($key = '')
    {
        $sysConf = SysConfig::find()->where(['conf_key' => $key])->limit(1)->one();
        if (!$sysConf) {
            return '';
        }
        return $sysConf->conf_val;
    }

    public function getDetails($model, $id)
    {
        // 获取类名
        $class = __NAMESPACE__ . "\\" . $model;
        if (!class_exists($class)) {
            return ('模块参数不正确');
        }
        $details = $class::find()->where(['id' => $id])->asArray()->limit(1)->one();
        if (!$details) {
            return ('ID参数不正确');
        }
        return $details;
    }

    /**
     * @param $driverIds
     * @return array|bool
     * @throws \yii\base\InvalidConfigException
     */
    public function getDriverPhoneNumberByIds($driverIds)
    {
        if (!is_array($driverIds) || !count($driverIds)) {
            return false;
        }
        return $this->getPhoneNumbers($driverIds, 2);
    }

    /**
     * @param $encryptPhone
     * @return mixed|string
     */
    public function getDecryptPhone($encryptPhone)
    {
        $result = $this->getDecryptPhones([$encryptPhone]);
        if(!$result || !isset($result[$encryptPhone])) {
            return '';
        }
        return $result[$encryptPhone];
    }

    /**
     * @param $encrypts
     * @return array
     */
    public function getDecryptPhones($encrypts)
    {
        $infoList = [];
        foreach ($encrypts as $item) $infoList[] = ['encrypt' => $item];
        $requestData = [
            'infoList' => $infoList,
        ];
        $responseData = self::httpPost('account.decryptPhone',$requestData);
        $phoneList = $responseData['data']['infoList'] ?? [];
        return array_column($phoneList, 'phone', 'encrypt');
    }

    /**
     * @param $driverId
     * @return mixed|string
     * @throws \yii\base\InvalidConfigException
     */
    public function getPhoneNumberByDriverId($driverId)
    {
        $result = $this->getPhoneNumbers([$driverId], 2);
        return $result[$driverId] ?? '';
    }

    /**
     * @param $passengerIds
     * @return array
     */
    public function getPassengerPhoneNumberByIds($passengerIds)
    {
        if (!is_array($passengerIds) || !count($passengerIds)) {
            return [];
        }
        return $this->getPhoneNumbers($passengerIds);
    }

    /**
     * @param $passengerId
     * @return mixed|string
     */
    public function getPhoneNumberByPassengerId($passengerId)
    {
        $result = $this->getPhoneNumbers([$passengerId], 2);
        return $result[$passengerId] ?? '';
    }

    /**
     * @param $ids
     * @param int $idType
     * @return array
     */
    private function getPhoneNumbers($ids, $idType = 1)
    {
        $infoList = [];
        foreach ($ids as $id) $infoList[] = ['id' => $id];
        $requestData = [
            'idType' => $idType,
            'infoList' => $infoList,
        ];
        $responseData = self::httpPost('account.getPhoneList',$requestData);
        $phoneList = $responseData['data']['infoList'] ?? [];
        return array_column($phoneList, 'phone', 'id');
    }

    /**
     * @param string $cityCode
     * @param int $carId
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getUnbindCars($cityCode = '', $carId = 0)
    {
        $result = DriverInfo::find()->select('car_id')->where('car_id > 0')->asArray()->all();
        $bindCarId = (array_column($result, 'car_id', 'car_id'));
        if ($carId && isset($bindCarId[$carId])) {
            unset($bindCarId[$carId]);
        }
        $query = CarInfo::find()
            ->where(['not in', 'id', $bindCarId])
            ->andWhere(['use_status' => 1]); // 启用的
        if ($cityCode) {
            $query->andWhere(['city_code' => $cityCode]); // 城市
        }
        $unbindCarList = $query->select('id,plate_number')->asArray()->all();
        return $unbindCarList;
    }

    /**
     * 搜索对应人群
     *
     * @param int $peopleId
     * @return boolean|array|\yii\db\ActiveRecord[]
     */
    public function getPeopleTagList($peopleId)
    {
        $peopleList = PeopleTag::find()->select(['id', 'tag_name', 'tag_number', 'tag_conditions'])->where(['tag_type' => $peopleId])->orderBy('is_default desc')->addOrderBy('create_time desc')->asArray()->all();
        if (!empty($peopleList)) {
            foreach ($peopleList as $key => $value) {
                if ($value['tag_conditions'] != '*'){
                    $peopleList[$key]['tag_conditions'] = json_decode($value['tag_conditions']);
                }
            }
        }
        return $peopleList;
    }

    /**
     * 搜索app文案模板
     *
     * @param unknown $templateType
     * @return boolean|array|\yii\db\ActiveRecord[]
     */
    public function getAppTemplateList($templateType)
    {
        $appTemplateList = SmsAppTemplate::find()->select(['id', 'template_name', 'content'])->andFilterWhere(['template_type' => $templateType])->orderBy('create_time desc')->asArray()->all();
        return $appTemplateList;
    }

    /**
     * 取短信模板列表（华信）
     *
     * @param return array
     */
    public function getSmsTemplateList()
    {
        $smsList = SmsTemplate::find()->select(['id', 'template_name', 'template_type', 'template_id', 'content'])->where(['template_type' => [1, 2],'source'=>3])->orderBy('create_time desc')->asArray()->all();
        return $smsList;
    }

    /**
     * 取短信模板列表（阿里）
     *
     * @param return array
     */
    public function getAliSmsTemplateList()
    {
        $smsList = SmsTemplate::find()->select(['id', 'template_name', 'template_type', 'template_id', 'content'])->where(['template_type' => [1, 2],'source'=>1])->orderBy('create_time desc')->asArray()->all();
        return $smsList;
    }


    /**
     * 搜索广告位列表
     * @param int $positionType
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getAdPosition($positionType)
    {
        $adPositionList = AdPosition::find()->select(['position_id', 'position_name', 'content_type'])->where(['status' => 1, 'position_type' => $positionType])->orderBy('create_time desc')->asArray()->all();
        return $adPositionList;
    }

    public function getActiveCoupons()
    {
        $list = Coupon::find()
            ->where(['status' => 1])// 启用状态
            ->andWhere(['or',
                ['>', 'expire_time', date('Y-m-d H:i', time() + 86400)], // 一天内不过期
                ['<>', 'effective_type', 1] // 有效时间为天数的
            ])
            ->select('id,coupon_name,minimum_amount,reduction_amount,discount,maximum_amount,expire_time')
            ->orderBy('create_time desc')
            ->asArray()
            ->all();
        return $list;
    }


}

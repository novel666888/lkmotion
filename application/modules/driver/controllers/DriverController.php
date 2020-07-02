<?php

namespace application\modules\driver\controllers;

use application\controllers\BossBaseController;
use application\models\Car;
use application\models\Driver;
use application\modules\order\models\OrderPayment;
use common\events\DriverEvent;
use common\logic\FileUrlTrait;
use common\models\CarInfo;
use common\models\Decrypt;
use common\models\DriverAddress;
use common\models\DriverBindChangeRecord;
use common\models\DriverIncomeDetail;
use common\models\DriverInfo;
use common\models\ListArray;
use common\services\traits\ModelTrait;
use common\util\Common;
use common\util\Json;

/**
 * Site controller
 */
class DriverController extends BossBaseController
{
    use ModelTrait, FileUrlTrait;

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex()
    {
        $request = \Yii::$app->request;
        $driverName = trim($request->post('driverName', ''));
        $phoneNumber = trim($request->post('phoneNumber', ''));
        $plate = trim($request->post('plate', ''));
        $startTime = trim($request->post('startTime', ''));
        $endTime = trim($request->post('endTime', ''));
        $leaderId = intval($request->post('leaderId'));

        $query = DriverInfo::find();
        if ($driverName !== '') {
            $query->where(['driver_name' => ($driverName)]);
        }
        if ($phoneNumber !== '') {
            $encryptPhone = Decrypt::encryptPhone($phoneNumber);
            $query->andWhere(['phone_number' => $encryptPhone]);
        }
        if (($leaderId)) { // 司机主管ID
            $query->andWhere(['driver_leader' => intval($leaderId)]);
        }
        if ($plate !== '') {
            $carId = CarInfo::find()->where(['plate_number' => $plate])->select('id')->limit(1)->scalar();
            $query->andWhere(['car_id' => $carId]);
        }
        if ($startTime !== '') {
            $query->andWhere(['>=', 'create_time', date('Y-m-d', strtotime($startTime))]);
        }
        if ($endTime !== '') { // 筛选按照自然日
            $query->andWhere(['<', 'create_time', date('Y-m-d', strtotime($endTime) + 86400)]);
        }
        $drivers = DriverInfo::getPagingData($query, ['field' => 'create_time', 'type' => 'desc']);
        // 获取额外信息
        $list = $drivers['data']['list'];
        Driver::patchDriverInfo($list);
        $drivers['data']['list'] = $list;

        // 返回数据
        return Json::data(Common::key2lowerCamel($drivers));
    }

    /**
     * 司机录入
     * @return array|\yii\web\Response
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UserException
     */
    public function actionStore()
    {
        $id = $this->getId();
        $reqParams = Driver::compactDriverInfo(\Yii::$app->request->post());

        //return $this->asJson($reqParams);
        if ($id) {
            $oldPrams = Driver::driverDetails($id);
            $result = Driver::updateDriver($reqParams);
        } else {
            $oldPrams = false;
            $result = Driver::storeDriver($reqParams);
        }
        if (is_string($oldPrams)) {
            return Json::message($result);
        }

        // 根据结果来处理其他流程
        if ($result['code'] == 0) {
            if (!$id) {
                $id = $result['data']['driverInfo']['id'] ?? '';
            }
            $trigger = $this->getTrigger();
            if ($trigger) {
                Driver::recordChange($id, $oldPrams, $trigger);
            }
        }

        // 添加当前用户名!!!!!
        // $driverInfo->modify_by = 'admin';

        // 返回结果
        return $this->asJson($result);

    }

    /**
     * @return array|\yii\web\Response
     * @throws \yii\base\InvalidConfigException
     */
    public function actionBindCar()
    {
        $driverId = $this->getId('driverId');
        $carId = $this->getId('carId');
        $oldCarId = $this->getId('oldCarId');
        $totalMile = floatval(\Yii::$app->request->post('totalMile'));
        $driverInfo = DriverInfo::findOne(['id' => $driverId]);
        if (!$driverInfo) {
            return Json::message('driverId参数异常');
        }
        // 更新绑定信息
        $reqParams = [
            'id' => $driverId,
            'driverId' => $driverId,
            'carId' => $carId,
        ];
        $result = Driver::changeStatus($reqParams);
        if (is_string($result)) {
            return Json::message($result);
        }
        // 更新车辆里程信息
        if ($totalMile) {
            if (!$carId && $oldCarId) { // 解绑
                $reqParams = ['id' => $oldCarId, 'totalMile' => $totalMile];
                $mileResult = Car::changeStatus($reqParams);
            } elseif ($carId && !$oldCarId) {
                $reqParams = ['id' => $carId, 'totalMile' => $totalMile];
                $mileResult = Car::changeStatus($reqParams);
            }
        }
        // 车辆状态变更
        if ($result['code'] == 0) {
            $phoneNumber = (new ListArray())->getPhoneNumberByDriverId($driverId);
            \Yii::debug('bind car get driver phone: ' . $phoneNumber, 'bind.car');
            $newParams = $oldParams = [
                'id' => $driverId,
                'carId' => $oldCarId,
                'driverName' => $driverInfo->driver_name,
                'phoneNumber' => $phoneNumber,
            ];
            $newParams['carId'] = $carId;
            Driver::triggerCarId($oldParams, $newParams);
        }
        return $this->asJson($result);
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function actionDetails()
    {
        $driverId = $this->getId('id');
        if ($driverId < 1) {
            return Json::message('参数错误');
        }
        $driverDetails = Driver::driverDetails($driverId);
        if (is_string($driverDetails)) {
            return Json::message($driverDetails);
        }
        //$this->patchUrl($driverDetails, Driver::getImgKeys(true));
        $driverDetails['fileHost'] = $this->getOssHost();
        return Json::success($driverDetails);
    }

    /**
     * @return array
     */
    public function actionBindRecords()
    {
        $driverId = $this->getId('driverId');
        $listArray = new ListArray();
        $bindTags = $listArray->getSysConfig('driver_bind_type');
        if (!$bindTags) {
            return Json::message('配置异常!');
        }
        $config = json_decode($bindTags, 1);
        //var_dump($config);exit;
        $queryTags = array_column($config, 'key');

        $changeLogs = DriverBindChangeRecord::find()->where(['driver_info_id' => $driverId])->andWhere(['bind_tag' => $queryTags])->asArray()->all();
        // 分组数据
        $splitLogs = [];
        if ($changeLogs) {
            $adminIds = array_column($changeLogs, 'operator_id');
            $adminInfo = $listArray->pluckAdminNamesById($adminIds);
            foreach ($changeLogs as $log) {
                $log['adminName'] = $adminInfo[$log['operator_id']] ?? '';
                $log['bind_value'] = json_decode($log['bind_value']); // 参数解析
                $splitLogs[$log['bind_tag']][] = $log;
            }
        }
        //var_dump($splitLogs);exit;
        return Json::success(Common::key2lowerCamel($splitLogs));
    }

    /**
     * @return \yii\web\Response
     */
    public function actionIncomeRecords()
    {
        $driverId = $this->getId('driverId');
        $totalPrice = OrderPayment::find()->where(['driver_id' => $driverId])->sum('total_price');
        $query = OrderPayment::find()
            ->select(['sum(total_price) as day_income', 'LEFT(create_time,10) as date', 'count(id) as quantity'])
            ->where(['driver_id' => $driverId])
            ->groupBy('date');
        $list = self::getPagingData($query, ['field' => 'date', 'type' => 'desc']);
        $list['data']['totalMoney'] = $totalPrice;

        return $this->asJson(Common::key2lowerCamel($list));
    }

    /**
     * 司机地址
     * @return array
     */
    public function actionAddress()
    {
        $driverId = $this->getId('driverId');
        $list = DriverAddress::find()
            ->where(['driver_id' => $driverId])
            ->asArray()->all();
        return Json::success(['list' => Common::key2lowerCamel($list)]);
    }


    /**
     * 变更司机状态
     * @return array|\yii\web\Response
     */
    public function actionChangeStatus()
    {
        $request = \Yii::$app->request;
        $driverId = $this->getId('driverId');
        $driverInfo = DriverInfo::findOne(['id' => $driverId]);
        if (!$driverInfo) {
            return Json::message('参数异常');
        }
        $reqParams = [
            'id' => $driverId,
            'signStatus' => intval($request->post('signStatus')),
            'useStatus' => intval($request->post('useStatus'))
        ];
        // 司机解约冻结检测
        if ($driverInfo->work_status) {
            if (!$reqParams['signStatus']) {
                return Json::message('请司机先操作收车，方可解约');
            }
            if (!$reqParams['useStatus']) {
                return Json::message('请司机先操作收车，方可冻结');
            }
        }
        $result = Driver::changeStatus($reqParams);
        // 返回结果
        if (is_string($result)) {
            return Json::message($result);
        }
        if ($result['code'] == 0) {
            if ($reqParams['signStatus'] == 0) { // 解绑
                (new DriverEvent())->unsigned($driverId);
            }
            if ($reqParams['useStatus'] == 0) { // 冻结
                (new DriverEvent())->unused($driverId);
            }
        }
        return $this->asJson($result);
    }

    /**
     * @return array
     */
    private function getTrigger()
    {
        $request = \Yii::$app->request;
        $triggerParams = [
            'carId', // 车辆
            'bankCardNumber', // 银行卡号
            'phoneNumber', // 手机号
        ];
        $tmp = [];
        foreach ($triggerParams as $item) {
            $val = $request->post($item, null);
            if ($val === null) { // 过滤空值
                continue;
            }
            $tmp[] = $item;
        }
        return $tmp;
    }

    private function getId($key = 'id')
    {
        $request = \Yii::$app->request;
        $value = intval($request->get($key));
        if (!$value) {
            $value = intval($request->post($key));
        }
        return $value;
    }

}

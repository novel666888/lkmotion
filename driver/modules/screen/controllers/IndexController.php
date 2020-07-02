<?php

namespace driver\modules\screen\controllers;

use common\controllers\BaseController;
use common\models\CarInfo;
use common\models\DriverInfo;
use common\models\ListArray;
use common\util\Json;

/**
 * Site controller
 */
class IndexController extends BaseController
{

    /**
     * 车机检测更新
     *
     * @return array
     */
    public function actionCheckUpdate()
    {
        $request = \Yii::$app->request;
        $source = trim($request->post('source'));

        //!!!!!请求接口
        // some code
        $code = 1049;
        $message = '已经是最新版本';
        $data = [
            "appVersion" => "1.12.3",//版本号
            "noticeId" => 1,//更新规则ID
            "noticeType" => 1,//更新规则类型：1强制更新，2非强制更新
            "prompt" => "Please update YouCab to new version",//更新提示语
            "downloadUrl" => "",//软件包下载地址
        ];

        return $this->asJson(['code' => $code, 'message' => $message, 'data' => $data]);
    }

    /**
     * 车机状态检测
     * @return array
     */
    public function actionGetStat()
    {
        $deviceCode = $this->getDeviceCode();
        if (!$deviceCode) {
            return Json::message('车机未注册');
        }
        $carId = CarInfo::checkDriverScreen($deviceCode);
        $driverInfo = DriverInfo::find()->where(['car_id' => $carId])->limit(1)->one();
        if (!$driverInfo) {
            return Json::message('车机未绑定车辆');
        }
        $listModel = new ListArray();
        $sereenStatus = $listModel->getSysConfig('car_screen_status');
        $sereenStatus = json_decode($sereenStatus, 1);
        if (!is_array($sereenStatus)) {
            $sereenStatus = ['未登录', '已登录', '听单中', '暂停听单', '已收车', '听顺风单'];
        } else {
            $sereenStatus = array_column($sereenStatus, 'label', 'key');
        }
        if(!isset($driverInfo->cs_work_status) || !isset($sereenStatus[$driverInfo->cs_work_status])){
            return Json::message('车机状态异常,请重新登录', 0);
        }

        return Json::message($sereenStatus[$driverInfo->cs_work_status], $sereenStatus[$driverInfo->cs_work_status]);
    }


    /**
     * @return array
     */
    public function actionQrGen()
    {
        $csDeviceCode = trim(\Yii::$app->request->post('carDeviceCode'));
        $carId = CarInfo::checkDriverScreen($csDeviceCode);
        if(!$carId) {
            return Json::message('参数错误');
        }
        $csKey = uniqid('cs').rand(100000000,999999999);
        $downloadUrl = 'http://16f11p5264.51mypc.cn:28898/largeScreenServer/driverDownUrl.html';
        $data = [
            'url' => $downloadUrl.'?csKey='.$csKey.'&csDeviceCode='.$csDeviceCode,
            'csKey' => $csKey,
            'csDeviceCode' => $csDeviceCode,
        ];
        return Json::success($data);
    }

    private function getDeviceCode()
    {
        if(!$this->tokenInfo) {
            return false;
        }
        if(!isset($this->tokenInfo->sub) || !empty($this->tokenInfo->sub)){
            return false;
        }
        $info = explode("_", $this->tokenInfo->sub);

        return $info[1] ?? false;
    }


}

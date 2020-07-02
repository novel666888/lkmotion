<?php
namespace largescreen\modules\home\controllers;


use common\util\Common;
use common\util\Json;
use common\models\CarInfo;
use common\logic\LargeScreenHomeLogic;
use largescreen\controllers\LargeScreenClientBaseController;
/**
 * LargeScreenHome controller
 */
class LargeScreenFlightController extends LargeScreenClientBaseController
{
    /**
     * 航班查询&并订阅（取消上一个订阅的航班）
     * @param  int  $flightNo 航班号
     * @param  string  $flightDate 航班日期
     * @param  string  $mobile 乘客手机号
     * @param  string  $userId 乘客id
     * @param  string  $deviceCode 大屏设备号
     * @return array
     */
    public function actionGetFlightList()
    {
        $request = $this->getRequest();

        $flightNo = trim($request->post('flightNo'));
        $flightDate =trim($request->post('flightDate'));
        $mobile =trim($request->post('mobile'));
        $userId =trim($request->post('userId'));
        $deviceCode = trim($request->post('deviceCode'));
        $orderId = trim($request->post('orderId'));

        $getData['flightNo'] = $flightNo;
        $getData['flightDate'] = $flightDate;
        $getData['mobile'] = $mobile;
        $getData['userId'] = $userId;
        $getData['deviceCode'] = $deviceCode;
        $getData['orderId'] = $orderId;
        \Yii::info($getData, 'getData');

        if(!$flightNo || !$flightDate || !$deviceCode) {
            return Json::message('参数为空或不支持的数据类型');
        }
        $checkDeviceCode =CarInfo::checkPassengerScreen($deviceCode);
        \Yii::info($checkDeviceCode, 'checkDeviceCode');

        if(!$checkDeviceCode) {
            return Json::message('设备不存在！');
        }

        $flight = new LargeScreenHomeLogic();
        $flightList = $flight->getFlightList($flightNo,$flightDate,$mobile,$userId,$orderId);
        \Yii::info($flightList, 'flightList');
        if($flightList['flightStatusList']){
            $data['list']=$flightList['flightStatusList'];
        }else{
            return Json::message('没有查到相应的航班信息！');
        }
        \Yii::info($data, 'postData');
       return Json::success(Common::key2lowerCamel($data));
    }

    /**
     * 航班取消订阅
     * @param  int  $flightNo 航班号
     * @param  string  $flightDate 航班日期
     * @param  string  $userId 乘客id
     * @param  string  $deviceCode 大屏设备号
     * @return array
     */
    public function actionCancelFlight()
    {
        $request = $this->getRequest();
        $flightNo = trim($request->post('flightNo'));
        $flightDate =trim($request->post('flightDate'));
        $userId =trim($request->post('userId'));
        $deviceCode = trim($request->post('deviceCode'));

        $getData['flightNo'] = $flightNo;
        $getData['flightDate'] = $flightDate;
        $getData['userId'] = $userId;
        $getData['deviceCode'] = $deviceCode;
        \Yii::info($getData, 'getData');

        if(!$flightNo || !$flightDate || !$userId || !$deviceCode){
            return Json::message('参数为空或不支持的数据类型');
        }
        $checkDeviceCode =CarInfo::checkPassengerScreen($deviceCode);
        \Yii::info($checkDeviceCode, 'checkDeviceCode');

        if(!$checkDeviceCode){
            return Json::message('设备不存在！');
        }

        $flight = new LargeScreenHomeLogic();

        $flightData = $flight->getCanceFlight($flightNo,$flightDate,$userId);
        \Yii::info($flightDate, '$flightData');
        if ($flightData)
            return Json::message('航班取消成功', 0);
        else
            return Json::message('航班取消失败！');
    }

    /**
     * 航变-极光推送到大屏
     * @param  string  $deviceCode 大屏设备号
     * @return array
     */
    public function actionFlightChange()
    {
        $request = $this->getRequest();
        $flightNo = $request->post('flightNo');
        $deptFlightDate = $request->post('deptFlightDate');

        $flightPost = $request->post();
        \Yii::info($flightPost, 'postData');

        $getData['flightNo'] = $flightNo;
        $getData['deptFlightDate'] = $deptFlightDate;
        \Yii::info($getData, 'getData');

        if(!$flightNo || !$deptFlightDate){
            return Json::message('航变回调信息错误！');
        }

        $flight = new LargeScreenHomeLogic();
        $flight->flightChange($flightNo,$deptFlightDate);
        return Json::message('航变推送到大屏！',0);
    }

}

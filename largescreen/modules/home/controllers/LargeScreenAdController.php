<?php
namespace largescreen\modules\home\controllers;

use common\util\Common;
use common\util\Json;
use common\models\CarInfo;
use common\logic\AdLogic;
use largescreen\controllers\LargeScreenClientBaseController;
/**
 * LargeScreenHome controller
 */
class LargeScreenAdController extends LargeScreenClientBaseController
{
    /**
     * 广告管理
     * @param  int  $positionId 广告位id
     * @param  string  $cityCode 城市码
     * @param  string  $deviceCode 大屏设备号
     * @return array
    */
    public function actionGetAdManage()
    {
        $request = $this->getRequest();
        $positionId = json_decode(trim($request->post('positionId')));

        $cityCode =trim($request->post('cityCode'));
        $adCode =trim($request->post('adCode'));
        $deviceCode = trim($request->post('deviceCode'));

        $data['positionId']=$positionId;
        $data['cityCode']=$cityCode;
        $data['adCode']=$adCode;
        $data['deviceCode']=$deviceCode;
        \Yii::info($data, 'get_data');

        if(!$positionId || !$cityCode || !$deviceCode){
            return Json::message('参数为空或不支持的数据类型');
        }

        $checkDeviceCode=CarInfo::checkPassengerScreen($deviceCode);
        \Yii::info($checkDeviceCode, 'checkDeviceCode');
        if(!$checkDeviceCode){
            return Json::message('设备不存在！');
        }

        //查询广告管理
        //$ad = new AdLogic();
        $adList = AdLogic::getLargeScreenAdList($positionId,$deviceCode,$cityCode);

        \Yii::info($adList, 'postData');
        return Json::success(Common::key2lowerCamel($adList));
    }

}

<?php
namespace largescreen\modules\home\controllers;

use common\util\Common;
use common\util\Json;
use common\models\TvApps;
use common\models\CarInfo;
use common\models\PushAccount;
use common\models\TvVersionUpdate;
use common\logic\LargeScreenHomeLogic;
use common\logic\MessageLogic;
use yii\helpers\ArrayHelper;
use common\models\Jpush;
use common\logic\Sign;
use largescreen\controllers\LargeScreenClientBaseController;
/**
 * LargeScreenHome controller
 */
class LargeScreenHomeController extends LargeScreenClientBaseController
{
    /**
     * 天气查询
     * @param  int  $cityCode 城市编码
     * @param  string  $deviceCode 大屏设备号
     * @return array
     */
    public function actionGetWeather()
    {
        $request = $this->getRequest();
        $deviceCode = trim($request->post('deviceCode'));
        $cityCode =trim($request->post('cityCode'));
        $adCode =trim($request->post('adCode'));

        $getData['deviceCode'] = $deviceCode;
        $getData['cityCode'] = $cityCode;
        $getData['adCode'] = $adCode;
        \Yii::info($getData, 'getData');

        if(!$adCode || !$deviceCode){
            return Json::message('参数为空或不支持的数据类型');
        }
        $checkDeviceCode = CarInfo::checkPassengerScreen($deviceCode);
        \Yii::info($checkDeviceCode, 'checkDeviceCode');
        if(!$checkDeviceCode){
            return Json::message('设备不存在！');
        }

        $weather = new LargeScreenHomeLogic();
        $weatherData = $weather->weather($adCode);
        \Yii::info($weatherData, 'weatherData');

        return Json::success(Common::key2lowerCamel($weatherData));
    }

    /**
     * 服务器收集大屏设备的极光注册ID
     * @param  string  $source 设备来源 iOS,Android
     * @param  string  $yid 大屏唯一码
     * @param int $audience 听众类型：1：别名，2：注册Id
     * @return array
     */
    public function actionJpushRegist()
    {

        $request = $this->getRequest();
        $pushRecord['source'] =trim($request->post('source'));
        $pushRecord['yid'] =trim($request->post('yid'));
        $pushRecord['audience'] =trim($request->post('audience'));
        $pushRecord['identity_status'] = 4;
        \Yii::info($pushRecord, 'getData');

        if(!$pushRecord['source']  || !$pushRecord['yid'] || !$pushRecord['audience']) {
            return Json::message('参数为空或不支持的数据类型');
        }

        $signModel = new Sign();
        $secretKey = $signModel->largeScreenGetSecretByToken($pushRecord['yid']);
        if(!$secretKey){
            $secret = $signModel->largeScreenGenKey($pushRecord['yid']);
            $secretKey = $signModel->largeScreenGetSecretByToken($pushRecord['yid']);
        }
        \Yii::info($secretKey, 'secretKey');

        $pushData = PushAccount::getPushRecord ($pushRecord);
        if($pushData){
            \Yii::info($pushData['jpush_id'], 'alias');
            return Json::success(Common::key2lowerCamel(['secret'=>$secretKey,'alias'=>$pushData['jpush_id']]));
        }
        $pushRecord['jpush_id'] = Jpush::genAlias();
        \Yii::info($pushRecord, 'pushRecord');
        $resJpush =PushAccount::addGpush($pushRecord,1);
        \Yii::info($resJpush, 'resJpush');

        if($resJpush){
            return Json::success(Common::key2lowerCamel(['secret'=>$secretKey,'alias'=>$pushRecord['jpush_id']]));
        }else{
            return Json::message('别名绑定失败！');
        }
    }


    /**
     * 大屏app应用列表&app应用升级;;
     * @return array
     */
    public function actionAppManage()
    {
        $request = $this->getRequest();
        $deviceCode = trim($request->post('deviceCode'));
        \Yii::info($deviceCode, 'deviceCode');

        $checkDeviceCode = CarInfo::checkPassengerScreen($deviceCode);
        if(!$checkDeviceCode) {
            return Json::message('设备不存在！');
        }

        $TvAppsList = TvApps::getTvApps();
        \Yii::info($TvAppsList, 'TvAppsList');

        $ossFileUrl = ArrayHelper::getValue(\Yii::$app->params,'ossFileUrl');
        \Yii::info($ossFileUrl, 'ossFileUrl');

        foreach ($TvAppsList as $key =>$value){
            $value['down_load_url'] =$ossFileUrl.$value['down_load_url'];
            $value['ico_url'] =$ossFileUrl.$value['ico_url'];
            $data[]=$value;
        }

        \Yii::info($data, 'postData');
        return Json::success(Common::key2lowerCamel($data));
    }



    /**
     * 大屏升级
     *
     * @return array
     */
    public function actionTvUpdate()
    {
        $request = $this->getRequest();
        $deviceCode = trim($request->post('deviceCode'));
        \Yii::info($deviceCode, 'deviceCode');

        if(!$deviceCode){
            return Json::message('请求参数不能为空！');
        }

        $checkDeviceCode=CarInfo::checkPassengerScreen($deviceCode);
        \Yii::info($checkDeviceCode, 'checkDeviceCode');
        if(!$checkDeviceCode){
            return Json::message('设备不存在！');
        }
        $ossFileUrl = ArrayHelper::getValue(\Yii::$app->params,'ossFileUrl');
        \Yii::info($ossFileUrl, 'ossFileUrl');

        $TvAppsList = TvVersionUpdate::tvUpdate();
        if($TvAppsList){
            $TvAppsList['download_url'] =$ossFileUrl.$TvAppsList['download_url'];
        }

        \Yii::info($TvAppsList, 'postDate');

        return Json::success(Common::key2lowerCamel($TvAppsList));
    }



    /**
     * 大屏消息列表
     *
     * @return array
     */
    public function actionMessageList()
    {
        $request = $this->getRequest();
        $deviceCode = trim($request->post('deviceCode'));
        \Yii::info($deviceCode, 'deviceCode');

        if(!$deviceCode){
            return Json::message('请求参数不能为空！');
        }
        
        $checkDeviceCode=CarInfo::checkPassengerScreen($deviceCode);
        \Yii::info($checkDeviceCode, 'checkDeviceCode');
        if(!$checkDeviceCode){
            return Json::message('设备不存在！');
        }

        /*$signModel = new Sign();
        $secret = $signModel->largeScreenGetSecretByToken($deviceCode);
        \Yii::info($secret, 'secret');
        $secretKey = $signModel->checkSign($postData = null, $secret);
        \Yii::info($secretKey, 'secretKey');
        if(!$secretKey){
            return Json::message('sign参数验证失败！');
        }*/

        $message = new MessageLogic();
        $messageData = $message->getMessageList(4, $deviceCode, $msgType=0);

        \Yii::info($messageData, 'messageData');

        return Json::success(Common::key2lowerCamel($messageData));
    }
}

<?php
namespace passenger\modules\user\controllers;

use common\util\Json;
use common\controllers\ClientBaseController;
use common\util\Cache;

use common\services\YesinCarHttpClient;
use yii\helpers\ArrayHelper;

//use common\models\InvoiceRecord;

/**
 * 乘客控制类
 */
class ControlController extends ClientBaseController
{

    public function actionIndex()
    {
        echo "hello world";
    }

    /**
     * 控制大屏音量/亮度
     * 极光推送
     * @return [type] [description]
     */
    public function actionLargescreen(){
        exit;
    	$request = $this->getRequest();
        $requestData = $request->post();
        $requestData['controlType'] = isset($requestData['controlType']) ? trim($requestData['controlType']) : '';
        $requestData['controlValue'] = isset($requestData['controlValue']) ? trim($requestData['controlValue']) : '';
        $requestData['deviceCode'] = isset($requestData['deviceCode']) ? trim($requestData['deviceCode']) : '';
        if(empty($requestData['controlType']) || empty($requestData['controlValue']) || empty($requestData['deviceCode'])){
        	return Json::message("Parameter error");
        }
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }else{
            $requestData['passengerId'] = $this->userInfo['id'];
        }

        $server = ArrayHelper::getValue(\Yii::$app->params,'api.message.serverName');
        $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.message.method.jpush');
        $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
        $data = [
                    "sendId" => $requestData['passengerId'],
                    "sendIdentity" => 1,//乘客
                    "acceptIdentity" => 4,//大屏
                    "acceptId" => $requestData['deviceCode'],
                    "title" => $requestData['controlType'],
                    "messageType" => 99,//控制大屏
                    "messageBody" => $requestData['controlValue'],
                ];
        $data = $httpClient->post($methodPath, $data);
        return $this->asJson($data);
    }


    /** 
     *
     * 用户登出乘客端
     * 
     */
    public function actionPassengerLogout(){
        //获取token
        $header = \Yii::$app->request->headers->toArray();
        $jwt = $header['authorization'][0] ?? null;
        if(empty($jwt)){
            return Json::message("error");
        }
        $server = ArrayHelper::getValue(\Yii::$app->params,'api.account.serverName');
        $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.account.method.checkOut');
        $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
        $_data = [
            "token" => $jwt,
            "equipType" => 1,//乘客端
        ];
        $data = $httpClient->post($methodPath, $_data);
        \Yii::info([$_data, $data], "logout user");
        return $this->asJson($data);
    }

    /**
     * 
     * 用户登出大屏端
     * 
     */
    public function actionLargescreenLogout(){
        exit;
        //获取token
        $header = \Yii::$app->request->headers->toArray();
        $jwt = $header['authorization'][0] ?? null;
        if(empty($jwt)){
            return Json::message("error");
        }
        $server = ArrayHelper::getValue(\Yii::$app->params,'api.account.serverName');
        $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.account.method.checkOut');
        $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
        $data = [
            "token" => $jwt,
            "equipType" => 4,//大屏端
        ];
        $data = $httpClient->post($methodPath, $data);
        return $this->asJson($data);
    }


}
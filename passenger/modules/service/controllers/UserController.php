<?php
namespace passenger\modules\service\controllers;

use common\logic\Sign;
use yii;
use common\util\Json;
use common\controllers\ClientBaseController;
use common\util\Common;
use common\services\traits\PublicMethodTrait;
use common\services\YesinCarHttpClient;
use yii\helpers\ArrayHelper;

use passenger\models\LargeScreenPassenger;
use common\models\SmsCode;
use yii\base\UserException;
use common\events\PassengerEvent;

class UserController extends ClientBaseController
{
    use PublicMethodTrait;

    public function actionIndex()
    {
        echo "hello world";
    }

    /**
     * 乘客查询是否已经登录
     * @return [type] [description]
     */
    public function actionLoginState()
    {
        exit;
        $request = $this->getRequest();
        $requestData = $request->post();
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }else{
            $requestData['passengerId'] = $this->userInfo['id'];
        }

        $condition=[];
        $condition['passengerId'] = $requestData['passengerId'];
        $rs = LargeScreenPassenger::loginState($condition, ['device_code AS deviceCode']);
        if($rs['code']==0){
            //登录状态
            return Json::success(["deviceCode"=>$rs['data']['device_code']]);
        }else{
            //没有登录大屏和其他情况
            return Json::message($rs['message']);
        }
    }
    
    /**
     * 乘客登录/注册
     */
    public function actionLogin()
    {
        $request = $this->getRequest();
        $requestData = $request->post();
        \Yii::info($requestData, "login 1");
        $requestData['phoneNum']  = isset($requestData['phoneNum']) ? trim($requestData['phoneNum']) : '';
        $requestData['verifyCode']  = isset($requestData['verifyCode']) ? trim($requestData['verifyCode']) : '';
        $requestData['marketChannel']  = isset($requestData['marketChannel']) ? trim($requestData['marketChannel']) : '';
        if(empty($requestData['phoneNum']) || empty($requestData['verifyCode'])){
            return Json::message("phoneNum or verifyCode error");
        }
        if(!is_numeric($requestData['phoneNum']) || strlen($requestData['phoneNum'])<8){
            return Json::message("手机号格式错误");
        }
        $result = SmsCode::validate($requestData['phoneNum'], $requestData['verifyCode'], "HX_0037");
        //echo $result;
        if($result!==true){
            $mes = SmsCode::getMessageByTimes($result);
            return Json::message($mes);
        }

        $header = \Yii::$app->request->headers->toArray();
        \Yii::info($header, "login 11");
        $register_source = "";
        if(isset($header['source'][0])){
            $register_source = $header['source'][0];
        }
        $server = ArrayHelper::getValue(\Yii::$app->params,'api.account.serverName');
        $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.account.method.regist');
        try{
            $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
            $_data = [
                "phoneNum" => $requestData['phoneNum'],
                "registerSource" => $register_source,
                "marketChannel" => $requestData['marketChannel'],
            ];
            $data = $httpClient->post($methodPath, $_data);
        }catch (UserException $exception){
            return $this->renderErrorJson($exception);
        }catch(\yii\httpclient\Exception $exception){
            return $exception->getMessage();
        }

        if(isset($data['data']['headImg'])){
            if(!empty($data['data']['headImg'])){
                $data['data']['headImg'] = \Yii::$app->params['ossFileUrl'].$data['data']['headImg'];
            }
            if(!empty($data['data']['birthday'])){
                $data['data']['birthday'] = date("Y-m-d", ceil($data['data']['birthday']/1000));
            }else{
                $data['data']['birthday'] = "";
            }
        }

        if(isset($data['data']['isNewer'])){
            if($data['data']['isNewer']==1){
                (new PassengerEvent())->register($data['data']['id']);
            }
        }

        // 增加签名密钥
        $token = $data['data']['accessToken'] ?? null;
        if ($token) {
            $data['data']['secret'] = (new Sign())->genKey($token);
        }
        \Yii::info([$_data, $data], "login 2");
        return $this->asJson($data);
    }

    public function actionSignTest()
    {
        $result = (new Sign())->checkSign();
        return Json::success(['result' => $result]);
    }




}
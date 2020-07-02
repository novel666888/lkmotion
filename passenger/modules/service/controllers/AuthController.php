<?php
namespace passenger\modules\service\controllers;

use yii;
use common\util\Json;
use common\controllers\ClientBaseController;
use common\util\Common;

use common\models\AppVersionUpdate;
use common\models\PushAccount;
use common\models\SmsCode;
use common\services\YesinCarHttpClient;
use yii\helpers\ArrayHelper;
/**
 * 服务器端验证、升级等
 */
class AuthController extends ClientBaseController
{

    public function actionIndex()
    {
        echo "hello world";
    }

    /**
     * 发送乘客端手机验证码
     */
    public function actionSendSms(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['mobile'] = isset($requestData['mobile']) ? trim($requestData['mobile']) : '';
        if(empty($requestData['mobile'])){
        	return Json::message("手机号格式错误");
        }
        try {
            $code = SmsCode::create($requestData['mobile'], "HX_0037", 6);
            if ($code == false) {
                return Json::message("不能发送短信验证码");
            }
            $data = Common::sendLoginCode($requestData['mobile'], $code);
            if(!$data){
                throw new yii\base\Exception('发送验证码失败',100011);
            }
            return Json::success();
        }catch (yii\base\Exception $e){
            $this->renderJson($e);
        }
    }
    
    /**
     * 升级乘客端
     */
    public function actionCheckVersion(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['platform'] = isset($requestData['platform']) ? trim($requestData['platform']) : '';
        $requestData['versionCode'] = isset($requestData['versionCode']) ? trim($requestData['versionCode']) : '';
        if(empty($requestData['platform']) || empty($requestData['versionCode'])){
            return Json::message("platform or versionCode error");
        }

        $header = \Yii::$app->request->headers->toArray();
        $source="";
        if(isset($header['source'][0])){
            if($header['source'][0]=='iOS' || $header['source'][0]=='ios'){
                $source=1;
            }elseif ($header['source'][0]=='Android' || $header['source'][0]=='android'){
                $source=2;
            }else{
                $source=3;
            }
        }
        if(!empty($source)){
            if($source!=$requestData['platform']){
                return Json::message("platform and source error");
            }
        }

        $field=['app_version', 'notice_type', 'prompt', 'download_url', 'version_code', 'note', 'start_time'];
        $data=AppVersionUpdate::checkVersion($requestData, $field);
        if($data['code']==0){
            if(!empty($data['data'])){
                $data['data']['status'] = 1;//可以更新
            }else{
                $data['data']['status'] = 0;//不更新
            }
            return Json::success(Common::key2lowerCamel($data['data']));
        }else{
            return Json::message("error");
        }
    }   

    /**
     * 注册保存极光ID
     */
    public function actionActivateJpush(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['regId'] = isset($requestData['regId']) ? trim($requestData['regId']) : '';
        //$requestData['phoneNum'] = isset($requestData['phoneNum']) ? trim($requestData['phoneNum']) : '';
        $requestData['source'] = isset($requestData['source']) ? trim($requestData['source']) : '';
        if(empty($requestData['regId']) || empty($requestData['source'])){
            return Json::message("regId or source error");
        }
        //yid使用用户ID
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        $header = \Yii::$app->request->headers->toArray();
        if(isset($header['source'][0])){
            if($header['source'][0]!=$requestData['source']){
                return Json::message("source error");
            }
        }
        $Data=[];
        $Data['jpush_id'] = $requestData['regId'];
        $Data['yid'] = $this->userInfo['id'];
        $Data['source'] = $requestData['source'];
        $Data['audience'] = 1;//别名
        $Data['identity_status'] = 1;//乘客身份
        $rs = PushAccount::addGpush($Data);
        if($rs===true){
            return Json::success();
        }else{
            return Json::message("Save failure");
        }
    }



}
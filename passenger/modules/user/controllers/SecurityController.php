<?php
namespace passenger\modules\user\controllers;

use yii;
use common\util\Json;
use common\util\Common;
use common\controllers\ClientBaseController;
use common\models\PassengerContact;
use common\models\PassengerInfo;
use common\logic\Passenger;

use common\services\YesinCarHttpClient;
use yii\helpers\ArrayHelper;
use yii\base\UserException;
use common\services\traits\PublicMethodTrait;
//use common\events\OrderEvent;

class SecurityController extends ClientBaseController
{
    use PublicMethodTrait;

    public function actionIndex()
    {
        echo "hello world";
    }

    //测试自动分享行程
    public function actionTest(){

        exit;
        $eventData = [
            'identity' => '',
            'orderId' => 17,
            'extInfo' => [],
        ];
        (new OrderEvent())->startOrder($eventData);
        exit;
        $a = new Passenger(212);
        $a->emergencyContact();
    }

    //私有
    private function passengerExt($data){
        $account = \Yii::$app->params['api']['account'];
        $YesinCarHttpClient = new YesinCarHttpClient(['serverURI'=>$account['serverName']]);
        try{
            \Yii::info($data, 'passengerExt_1');
            $result = $YesinCarHttpClient->post($account['method']['passengerExt'], $data);
            \Yii::info($result, 'passengerExt_2');
            if (!isset($result['code'])) {
                throw new UserException('java EditSharetime api error 1!');
            }elseif($result['code']!=0){
                throw new UserException('java EditSharetime api error 2!');
            }else{
                //send-sms
                if(!empty($this->userInfo['id'])){
                    $run = new Passenger($this->userInfo['id']);
                    $run->emergencyContact();
                }
                $this->asJson($result);
            }
        }catch (UserException $exception){
            return $this->renderErrorJson($exception);
        }catch(\yii\httpclient\Exception $exception){
            return $exception->getMessage();
        }
    }

    /**
     * 自动分享行程 - 时段修改
     */
    public function actionEditSharetime(){
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        $request = $this->getRequest();
        $postData = $request->post();
        \Yii::info($postData, 'EditSharetime');
        $requestData['time']  = isset($postData['time'])  ? trim($postData['time']) : "";//09-07
        if(empty($requestData['time'])){
            return Json::message("参数不完整");
        }
        $testime = explode("-", $requestData['time']);
        if(!isset($testime[0]) || !isset($testime[1])){
            return Json::message("time格式错误，正确格式为 09:00-07:00");
        }
        if(strlen($testime[0])!=5 || strlen($testime[1])!=5){
            return Json::message("time格式错误，正确格式为 09:00-07:00");
        }

        $data=[
            "id" => $this->userInfo['id'],
            "sharingTime" => $requestData['time']
        ];
        return $this->passengerExt($data);
        /**
        $rs = PassengerInfo::updateSharingTime($this->userInfo['id'], $requestData['time']);
        if($rs){
            return Json::success();
        }else{
            return Json::message("更新失败");
        }*/
    }

    /**
     * 启动/关闭自动分享行程
     */
    public function actionEnableShare(){
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        $request = $this->getRequest();
        $postData = $request->post();
        \Yii::info($postData, 'EnableShare');
        $requestData['enable']  = isset($postData['enable'])  ? trim($postData['enable']) : 0;
        if(!in_array((string)$requestData['enable'], [(string)0,(string)1])){
            return Json::message("enable取值范围错误，应该为0或1");
        }

        $data=[
            "id" => $this->userInfo['id'],
            "isShare" => $requestData['enable']
        ];
        return $this->passengerExt($data);
        /**
        $rs = PassengerInfo::setShare($this->userInfo['id'], $requestData['enable']);
        if($rs){
            return Json::success();
        }else{
            return Json::message("更新失败");
        }*/
    }

    /**
     * 启动/关闭紧急联系人
     */
    public function actionEnableContact(){
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        $request = $this->getRequest();
        $postData = $request->post();
        \Yii::info($postData, 'EnableContact');
        $requestData['enable']  = isset($postData['enable'])  ? trim($postData['enable']) : 0;
        if(!in_array((string)$requestData['enable'], [(string)0,(string)1])){
            return Json::message("enable取值范围错误，应该为0或1");
        }

        $data=[
            "id" => $this->userInfo['id'],
            "isContact" => $requestData['enable']
        ];
        return $this->passengerExt($data);
        /**
        $rs = PassengerInfo::setContact($this->userInfo['id'], $requestData['enable']);
        if($rs){
            return Json::success();
        }else{
            return Json::message("更新失败");
        }*/
    }

    /**
     * 返回联系人列表
     */
    public function actionListContact(){
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        $model = new PassengerContact();
        $data = $model::find()->select(["id AS contactId","passenger_info_id","name","phone"])->where(["passenger_info_id"=>$this->userInfo['id'], "is_del"=>0])
            ->asArray()->all();
        if(!empty($data)){
            $data = Common::key2lowerCamel($data);
        }else{
            $data = [];
        }
        return Json::success($data);
    }

    /**
     * 添加联系人
     */
    public function actionAddContact(){
        $request = $this->getRequest();
        $postData = $request->post();
        \Yii::info($postData, 'AddContact');
        $requestData['name']  = isset($postData['name'])  ? trim($postData['name'])  : "";
        $requestData['phone'] = isset($postData['phone']) ? trim($postData['phone']) : '';
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        if(empty($requestData['name']) || empty($requestData['phone'])){
            return Json::message("参数不完整");
        }

        $model = new PassengerContact();
        $model->passenger_info_id = $this->userInfo['id'];
        $model->name = $requestData['name'];
        $model->phone = $requestData['phone'];
        if ($model->validate()) {
            if ($model->save()) {
                return Json::success();
            }else{
                return Json::message($model->getFirstError());
            }
        } else {
            return Json::message($model->getErrors());
        }
    }

    /**
     * 删除联系人
     */
    public function actionDelContact(){
        $request = $this->getRequest();
        $postData = $request->post();
        \Yii::info($postData, 'DelContact');
        $requestData['id']  = isset($postData['id'])  ? trim($postData['id'])  : "";
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        if(empty($requestData['id'])){
            return Json::message("参数不完整");
        }
        $model = new PassengerContact();
        $data = $model::find()->select(["*"])->where(["id"=>$requestData['id'], "passenger_info_id"=>$this->userInfo['id']])->one();
        if(!empty($data->id)){
            $data->is_del=1;
            if($data->save()){
                return Json::success();
            }
        }
        return Json::message("修改失败");
    }

    /**
     * 修改联系人
     */
    public function actionEditContact(){
        $request = $this->getRequest();
        $postData = $request->post();
        \Yii::info($postData, 'EditContact');
        $requestData['id']  = isset($postData['id'])  ? trim($postData['id'])  : "";
        $requestData['name']  = isset($postData['name'])  ? trim($postData['name'])  : "";
        $requestData['phone']  = isset($postData['phone'])  ? trim($postData['phone'])  : "";
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        if(empty($requestData['id']) || empty($requestData['name']) || empty($requestData['phone'])){
            return Json::message("参数不完整");
        }
        $model = new PassengerContact();
        $data = $model::find()->select(["*"])->where(["id"=>$requestData['id'], "passenger_info_id"=>$this->userInfo['id']])->one();
        if(!empty($data->id)){
            $data->name = $requestData['name'];
            $data->phone = $requestData['phone'];
            if ($data->validate()) {
                if ($data->save()) {
                    return Json::success();
                }else{
                    return Json::message($data->getFirstError());
                }
            } else {
                return Json::message($data->getErrors());
            }
        }
        return Json::message("修改失败");
    }
}
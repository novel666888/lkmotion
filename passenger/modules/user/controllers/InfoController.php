<?php
namespace passenger\modules\user\controllers;

use yii;
use common\util\Json;
use common\controllers\ClientBaseController;
//use common\util\Cache;
use common\services\YesinCarHttpClient;
use yii\helpers\ArrayHelper;
//use passenger\models\PassengerInfo;

class InfoController extends ClientBaseController
{

    public function actionIndex()
    {
        echo "hello world";
    }


    /**
     * 查询个人信息
     */
    public function actionGet(){
        try {
            $requestData = [];
            if (empty($this->userInfo['id'])) {
                return Json::message("Identity error");
            } else {
                $requestData['passengerId'] = $this->userInfo['id'];
            }

            $server = ArrayHelper::getValue(\Yii::$app->params,'api.account.serverName');
            $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.account.method.passengerInfo');
            $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
            $_data = [
                "id" => $requestData['passengerId']
            ];
            $data = $httpClient->post($methodPath, $_data);
            \Yii::info([$_data, $data], "Get user info");
            if($data['code']!=0){
                throw new yii\base\Exception($data['message'],100001);
            }
            if(isset($data['data']['passengerAddressList'])){
                unset($data['data']['passengerAddressList']);
            }
            if(isset($data['data']) && empty($data['data'])){
                $data['data']['passengerInfo']=[];
            }
            if(isset($data['data']['passengerInfo']['headImg'])) {
                if (!empty($data['data']['passengerInfo']['headImg'])) {
                    $data['data']['passengerInfo']['headImg'] = \Yii::$app->params['ossFileUrl'] . $data['data']['passengerInfo']['headImg'];
                }
            }
            if(!empty($data['data']['passengerInfo']['birthday'])){
                $data['data']['passengerInfo']['birthday'] = date("Y-m-d", strtotime($data['data']['passengerInfo']['birthday']));
            }else{
                $data['data']['passengerInfo']['birthday'] = "";
            }
            return $this->asJson($data);
        }catch (yii\base\Exception $e){
            $this->renderJson($e);
        }
    }

    /**
     * 查询个人地址
     */
    public function actionGetAddress(){
        try {
            $requestData = [];
            if (empty($this->userInfo['id'])) {
                return Json::message("Identity error");
            } else {
                $requestData['passengerId'] = $this->userInfo['id'];
            }

            $server = ArrayHelper::getValue(\Yii::$app->params, 'api.account.serverName');
            $methodPath = ArrayHelper::getValue(\Yii::$app->params, 'api.account.method.passengerInfo');
            $httpClient = new YesinCarHttpClient(['serverURI' => $server]);
            $_data = [
                "id" => $requestData['passengerId']
            ];
            $data = $httpClient->post($methodPath, $_data);
            \Yii::info([$_data, $data], "Get user address");
            if($data['code']!=0){
                throw new yii\base\Exception($data['message'],100002);
            }
            if (isset($data['data']['passengerInfo'])) {
                unset($data['data']['passengerInfo']);
            }
            if (isset($data['data']) && empty($data['data'])) {
                $data['data']['passengerAddressList'] = [];
            }

            if (!empty($data['data']['passengerAddressList'])) {
                foreach ($data['data']['passengerAddressList'] as $k => &$v) {
                    if (isset($v['addressDesc'])) {
                        $v['addressDesc'] = Json_decode($v['addressDesc']);
                    }
                }
            }
            return $this->asJson($data);
        }catch (yii\base\Exception $e){
            $this->renderJson($e);
        }
    }

    /**
     * 修改个人信息
     * @return [type] [description]
     */
    public function actionEdit(){
    	try{
            $request = $this->getRequest();
            $requestData = $request->post();
            $requestData['passengerName'] = isset($requestData['passengerName']) ? trim($requestData['passengerName']) : '';
            $requestData['gender'] = isset($requestData['gender']) ? trim($requestData['gender']) : 0;
            $requestData['headImg'] = isset($requestData['headImg']) ? trim($requestData['headImg']) : '';
            $requestData['birthday'] = isset($requestData['birthday']) ? trim($requestData['birthday']) : '';
            if(empty($this->userInfo['id'])){
                return Json::message("身份错误");
            }else{
                $requestData['passengerId'] = $this->userInfo['id'];
            }
            if(empty($requestData['passengerName'])){
                return Json::message("姓名不可以为空");
            }
            if(mb_strlen($requestData['passengerName'], 'utf8')>16){
                return Json::message("姓名长度不可以超过16个字符");
            }
            if((!preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z_]+$/u", $requestData['passengerName']))){
                return Json::message("姓名只允许包含中英文及下划线");
            }
            if(!empty($requestData['birthday'])){
                $requestData['birthday'] = strtotime($requestData['birthday'])*1000;
            }
            $a = [
                "id"=>$requestData['passengerId'],
                "data" => [
                    "passengerInfo"=>[
                        "gender"=>$requestData['gender'],
                        "headImg"=>$requestData['headImg'],
                        "passengerName"=>$requestData['passengerName'],
                        "birthday"=>$requestData['birthday'],
                    ],
                ]
            ];
            if(empty($a["data"]['passengerInfo']['headImg'])){
                unset($a["data"]['passengerInfo']['headImg']);
            }
            if(empty($a["data"]['passengerInfo']['birthday'])){
                unset($a["data"]['passengerInfo']['birthday']);
            }
            $server = ArrayHelper::getValue(\Yii::$app->params,'api.account.serverName');
            $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.account.method.updatePassengerInfo');
            $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
            $data = $httpClient->post($methodPath, $a);
            \Yii::info([$a, $data], "edit user info");
            if($data['code']!=0){
                throw new yii\base\Exception($data['message'],100000);
            }
            return $this->asJson($data);
        }catch (yii\base\Exception $e){
            $this->renderJson($e);
        }
    }


    /**
     * 修改乘客地址信息
     */
    public function actionEditAddress(){
        try{
            $request = $this->getRequest();
            $requestData = $request->post();
            $requestData['type'] = isset($requestData['type']) ? trim($requestData['type']) : '';//地址类型
            $requestData['addressDesc'] = isset($requestData['addressDesc']) ? $requestData['addressDesc'] : '';
            $requestData['addressName'] = isset($requestData['addressName']) ? trim($requestData['addressName']) : '';
            $requestData['latitude'] = isset($requestData['latitude']) ? trim($requestData['latitude']) : '';
            $requestData['longitude'] = isset($requestData['longitude']) ? trim($requestData['longitude']) : '';

            if(empty($this->userInfo['id'])){
                return Json::message("身份错误");
            }else{
                $requestData['passengerId'] = $this->userInfo['id'];
            }

            if(empty($requestData['addressDesc']) || empty($requestData['addressName'])){
                return Json::message("参数错误");
            }

            $a = [
                "id" => $requestData['passengerId'],
                "data" => [
                    "passengerAddress"=>[
                        "addressDesc"=>Json_encode($requestData['addressDesc']),
                        "addressName"=>$requestData['addressName'],
                        "latitude"=>$requestData['latitude'],
                        "longitude"=>$requestData['longitude'],
                        "type"=>$requestData['type']
                    ],
                ]
            ];
            $server = ArrayHelper::getValue(\Yii::$app->params,'api.account.serverName');
            $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.account.method.updatePassengerInfo');
            $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
            $data = $httpClient->post($methodPath, $a);
            \Yii::info([$a, $data], "edit user address");
            if($data['code']!=0){
                throw new yii\base\Exception($data['message'],100003);
            }
            return $this->asJson($data);
        }catch (yii\base\Exception $e){
            $this->renderJson($e);
        }
    }

}
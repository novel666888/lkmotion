<?php
namespace passenger\modules\service\controllers;

use common\util\Json;
use common\controllers\ClientBaseController;
use common\util\Cache;
use common\util\Common;
use common\api\FlightApi;
use common\models\City;
use common\models\FlightNumber;

class InfoController extends ClientBaseController
{

    public function actionIndex()
    {
        echo "hello world";
    }

    /**
     * 返回已开通城市列表
     * @return [type] [description]
     */
    public function actionCityList()
    {
        //$request = $this->getRequest();
        //$requestData = $request->post();
        $condition=[];
        $condition['cityStatus'] = 1;
        $data = City::getCityDetailList($condition, ['id','city_name','city_code','city_longitude_latitude','order_risk_top'], null, false);
        if($data['code']==0){
            if(!empty($data['data'])){
                foreach($data['data'] as &$v){
                    $temp = explode(",", $v['city_longitude_latitude']);
                    $v['longitude'] = isset($temp[0]) ? trim($temp[0]) : 0;
                    $v['latitude']  = isset($temp[1]) ? trim($temp[1]) : 0;
                    unset($v['city_longitude_latitude']);//删除原来经纬度
                }
            }
            $data['data'] = array_values($data['data']);
            return Json::success(Common::key2lowerCamel($data['data']));
        }else{
            return Json::message("");
        }

    }
   
    /**
     * 返回服务器端时间戳信息
     */
    public function actionTimestamp(){
        $time = time();
        $time = $time*1000;
        return Json::success(["timestamp"=>(string)$time]);
    }

    /**
     * 通过航班号返回航班班次信息
     * @return array
     */
    public function actionFindFlight(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['flightNo']        = isset($requestData['flightNo']) ? trim($requestData['flightNo']) : '';
        $requestData['flightDate']      = isset($requestData['flightDate']) ? trim($requestData['flightDate']) : '';
        $requestData['phone']           = isset($requestData['phone']) ? trim($requestData['phone']) : '';
        $requestData['orderId']         = isset($requestData['orderId']) ? trim($requestData['orderId']) : '';
        if(empty($requestData['flightNo']) || empty($requestData['flightDate']) || empty($requestData['phone'])){
            return Json::message("参数错误");
        }
        if(empty($this->userInfo['id'])){
            return Json::message("身份错误");
        }
        $requestData['passengerId'] = $this->userInfo['id'];
        if($this->userInfo['phone']!=$requestData['phone']){
            return Json::message("手机号错误");
        }
        \Yii::info($requestData, "Flight Post");
        $flightres = new FlightApi();
        $res = $flightres->getFlightInfo($requestData['flightNo'], $requestData['flightDate'], $requestData['phone'], $requestData['passengerId']);
        \Yii::info($res, "flight info api return");
        if(isset($res['status']) && $res['status']==0 && isset($res['flightStatusList']) && !empty($res['flightStatusList']) && is_array($res['flightStatusList'])){
            //返回一条班次，直接订阅
            if(count($res['flightStatusList'])==1){
                $deptCityCode = $res['flightStatusList'][0]['deptCityCode'];
                $destCityCode = $res['flightStatusList'][0]['destCityCode'];
                if(empty($deptCityCode) || empty($destCityCode)){
                    return Json::message("Return format error");
                }
                $condition = [];
                $condition['flight_number'] = $requestData['flightNo'];
                $condition['flight_date'] = $requestData['flightDate'];
                $condition['passenger_info_id'] = $requestData['passengerId'];
                $condition['start_code'] = $deptCityCode;
                $condition['end_code'] = $destCityCode;
                $condition['phone'] = $requestData['phone'];
                $condition['is_subscribe'] = 1;//订阅
                $condition['order_id'] = $requestData['orderId'];
                $_rs = FlightNumber::checkAddFlight($condition);
                $__data = [
                    'code' => 0,
                    'message' => "",
                    'data' => [
                        'list'=> $res['flightStatusList'],
                    ]
                ];
                if($_rs){
                    $__data['message']="订阅成功";
                }else{
                    $__data['message']="订阅失败";
                }
                $this->asJson($__data);
            }
            else{
                $_res['list'] = $res['flightStatusList'];
                unset($res['flightStatusList']);
                return Json::success($_res);
            }   
        }else{
            return Json::message("没有获取到航班信息");
        }
    }


    /**
     * 乘客（订阅）录入航班号
     */
    public function actionAddFlight(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['flightNo']                = isset($requestData['flightNo']) ? trim($requestData['flightNo']) : '';
        $requestData['flightDate']              = isset($requestData['flightDate']) ? trim($requestData['flightDate']) : '';
        $requestData['deptCityCode']            = isset($requestData['deptCityCode']) ? trim($requestData['deptCityCode']) : '';
        $requestData['destCityCode']            = isset($requestData['destCityCode']) ? trim($requestData['destCityCode']) : '';
        $requestData['phone']                   = isset($requestData['phone']) ? trim($requestData['phone']) : '';
        $requestData['orderId']                 = isset($requestData['orderId']) ? trim($requestData['orderId']) : '';
        if(empty($requestData['flightNo']) || empty($requestData['flightDate']) || empty($requestData['deptCityCode']) || empty($requestData['destCityCode']) || empty($requestData['phone'])){
            return Json::message("Parameter error");
        }
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        $requestData['passengerId'] = $this->userInfo['id'];
        \Yii::info($requestData, "flight post");
        $condition = [];
        $condition['flight_number'] = $requestData['flightNo'];
        $condition['flight_date'] = $requestData['flightDate'];
        $condition['passenger_info_id'] = $requestData['passengerId'];
        $condition['start_code'] = $requestData['deptCityCode'];
        $condition['end_code'] = $requestData['destCityCode'];
        $condition['phone'] = $requestData['phone'];
        $condition['is_subscribe'] = 1;//订阅
        $condition['order_id'] = $requestData['orderId'];
        $data = FlightNumber::checkAddFlight($condition);
        if($data){
            return Json::message("订阅成功", 0);
        }else{
            return Json::message("订阅失败");
        }
    }

}
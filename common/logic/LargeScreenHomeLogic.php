<?php
/**
 * LargeScreenHomeLogic.php
 */

namespace common\logic;

use yii\helpers\ArrayHelper;
use common\services\YesinCarHttpClient;
use common\api\FlightApi;
use common\models\FlightNumber;
use common\models\CarInfo;
use common\logic\order\OrderOutputServiceTrait;
use common\services\traits\PublicMethodTrait;
class LargeScreenHomeLogic
{
    /**
     * weather --
     * @param $cityCode
     * @return array
     * @cache No
     */
    public static function weather($cityCode)
    {
        $weatherServer = ArrayHelper::getValue(\Yii::$app->params,'api.weather.serverName');
        \Yii::info($weatherServer, 'weatherServer');

        $weatherPath = ArrayHelper::getValue(\Yii::$app->params,'api.weather.method.weatherInfo');
        \Yii::info($weatherPath, 'weatherPath');

        $httpClient = new YesinCarHttpClient(['serverURI'=>$weatherServer]);
        $data['key']=ArrayHelper::getValue(\Yii::$app->params,'weatherKey');// \Yii::$app->params['umetripAppId'];
        $data['city']=$cityCode;
        $weatherData = $httpClient->get($weatherPath, $data,2);
        \Yii::info($weatherData, 'weatherData');
        if($weatherData['status']==1){
            $weatherData['lives'][0]['reporttime'] = time()."000";
            return $weatherData['lives'][0];
        }else{
            return array();
        }
    }


    /**
     * getFlightList --
     * @param $flightNo
     * @param $flightDate
     * @param $mobile
     * @param $userId
     * @return array|bool
     * @cache No
     */
    public static function getFlightList($flightNo,$flightDate,$mobile,$userId,$orderId=null)
    {
        $flightres = new FlightApi();
        $flightList = $flightres->getFlightInfo($flightNo,$flightDate);

        if($flightList['status'] < 0) return false;
        if(!$userId || !$mobile || $mobile==null){
            return $flightList;
        }
        if($orderId){
            $flightData=FlightNumber::checkFlightSubscribe($userId,$flightNo,$flightDate,$orderId);
        }else{
            $flightData=FlightNumber::checkFlight($userId,$flightNo,$flightDate);
        }

        \Yii::info($flightData, 'flightDataRes');
        
        if($flightData == 1) return $flightList;
        if($flightData){
            foreach ($flightData as $key => $value){
                $data['flightNo'] = $value['flight_number'];
                $data['flightDate'] = $value['flight_date'];
                $data['deptAirportCode'] = $value['start_code'];
                $data['destAirportCode'] = $value['end_code'];
                $data['userId'] = $userId;

                \Yii::info($flightres->cancelSubscribeFlight($data), 'cancelSubscribeFlight');

                $updata['is_subscribe'] = 0;
                $condition['passenger_info_id'] = $userId;
                if(!FlightNumber::updateFlight($updata, $condition)) return false;
            }


        }

        foreach ($flightList['flightStatusList'] as $key => $value){
            $data['flightNo'] = $flightNo;
            $data['flightDate'] = $flightDate;
            $data['deptAirportCode'] = $value['deptCityCode'];
            $data['destAirportCode'] = $value['destCityCode'];
            $data['mobile'] = $mobile;
            $data['userId'] = $userId;
            if(!$flightres->subscribeFlight($data)) return false;
            $upData['flight_number'] = $flightNo;
            $upData['flight_date'] = $flightDate;
            $upData['passenger_info_id'] = $userId;
            $upData['start_code'] = $value['deptCityCode'];
            $upData['end_code'] = $value['destCityCode'];
            $upData['is_subscribe'] = 1;
            if($orderId){
                $upData['order_id'] = $orderId;
            }

            if(!FlightNumber:: add($upData)) return false;
        }
        return $flightList;
    }

    /**
     * getCanceFlight --
     * @param $flightNo
     * @param $flightDate
     * @param $userId
     * @return array
     * @cache No
     */
    public static function getCanceFlight($flightNo,$flightDate,$userId)
    {
        $flightres = new FlightApi();
        $flightData=FlightNumber::flightInfo($userId,$flightNo,$flightDate);

        if($flightData){
            foreach ($flightData as $key => $value){
                $data['flightNo'] = $value['flight_number'];
                $data['flightDate'] = $value['flight_date'];
                $data['deptAirportCode'] = $value['start_code'];
                $data['destAirportCode'] = $value['end_code'];
                $data['userId'] = $userId;
                if(!$flightres->cancelSubscribeFlight($data)) return false;
                $updata['is_subscribe'] = 0;
                $condition['passenger_info_id'] = $userId;
                FlightNumber::updateFlight($updata, $condition);
            }
        }
        return true;
    }


    /**
     * flightChange --
     * @param string $flightNo
     * @param string $deptFlightDate
     * @return array|mixed
     * @cache No
     * @author Liurn
     */
    public static function  flightChange($flightNo,$deptFlightDate)
    {
        $userId = FlightNumber::findSubscribeUser($flightNo,$deptFlightDate);

        \Yii::info($userId, 'userId');
        if($userId){
            foreach ($userId as &$val){
                $carId[] = OrderOutputServiceTrait::getUnderWayOrderCarId($val['passenger_info_id']);
            }
            \Yii::info($carId, 'carId');
            if(array_filter($carId)){
                foreach (array_reduce(array_filter($carId), 'array_merge', array()) as $key => $val){
                    $deviceCode[] = json_decode(json_encode(CarInfo::getScreenDeviceCodesById($val)),true);
                }
            }else{
                $deviceCode = array();
            }
            \Yii::info($deviceCode, 'deviceCode');
            if($deviceCode){
                $data = array(
                    'messageType' => 4100,
                    'content' => "该乘客所订阅的航班发生航变"
                );

                foreach ($deviceCode as &$val){
                    if($val['passengerDevice']){

                        $jpushData = array(
                            'sendId' => 'system',//发送者id
                            'sendIdentity' => 1,//发送者身份
                            'acceptId' => (string)$val['passengerDevice'],//接收者id
                            'acceptIdentity' => 4,//接收者身份 1
                            'title' => '航变通知',
                            'messageType' => 2,//1:别名， 2：注册id
                            'messageBody' => json_encode($data)
                        );
                        \Yii::info($jpushData, 'jpushData');
                        PublicMethodTrait::jpush(2, $jpushData);//推送大屏
                    }

                }
            }
        }
    }

}
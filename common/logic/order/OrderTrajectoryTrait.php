<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/31
 * Time: 10:24
 */
namespace common\logic\order;

use common\models\Order;
use common\models\OrderRulePrice;
use common\services\YesinCarHttpClient;
use yii\base\UserException;
use yii\helpers\ArrayHelper;

trait OrderTrajectoryTrait
{
    /**
     * @param $orderId
     * @return array|mixed
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */
    public function getOrderTrajectoryByOrderId($orderId)
    {
        $order = Order::findOne($orderId);
        if(!$order){
            return 1001;
        }
        $vehicleId = $order->car_id;
        $orderRulePrice = OrderRulePrice::findOne(['order_id'=>$orderId,'category'=>OrderRulePrice::PRICE_TYPE_FORECAST]);
        if(!$orderRulePrice){
            return 1002;
        }
        $city = $orderRulePrice->city_code;
        /*if(!$order->driver_start_time){
            return 1003;
        }*/
        $driver_start_time = intval(strtotime($order->driver_start_time).'000');

        //$startTime = strtotime($order->driver_start_time) .'000';
        //$startTime = strtotime($order->driver_start_time) .'000';
        /**
         * vehicleId = 100106
         * city = 010
         * startTime = 1536738010000
         * endTime = 1536738790000
         */
        $startTime = $driver_start_time;
        $endTime = intval(time().'000');
        if(!empty($order->driver_arrived_time)){
            $startTime  = intval(strtotime($order->driver_arrived_time).'000');
        }
        if(!empty($order->receive_passenger_time)) {
            $startTime =intval(strtotime($order->receive_passenger_time).'000');
        }
        if(!empty($order->passenger_getoff_time)){
            $endTime =intval(strtotime($order->passenger_getoff_time).'000');
        }

        $clientData = compact('city','startTime','endTime','vehicleId');
        \Yii::info('订单轨迹数据');
        \Yii::info($clientData);
        /*$clientData = [
            'vehicleId' => 100106,
            'city' => '010',
            'startTime' => 1536738010000,
            'endTime' => 1536738790000,
        ];*/
        //var_dump($clientData);exit;

        $order_points_info['startPoint']['locateTime'] = null;
        $order_points_info['startPoint']['longitude'] = $order->start_longitude;
        $order_points_info['startPoint']['latitude'] = $order->start_latitude;
        $order_points_info['startPoint']['addressName'] = $order->start_address;
        $order_points_info['endPoint']['locateTime'] = null;
        $order_points_info['endPoint']['longitude'] = $order->end_longitude;
        $order_points_info['endPoint']['latitude'] = $order->end_latitude;
        $order_points_info['endPoint']['addressName'] = $order->end_address;
        $order_points_info['points'] = [];
        $order_points_info['pointCount'] = 0;

        $mapServer = ArrayHelper::getValue(\Yii::$app->params,'api.map');
        $client = new YesinCarHttpClient(['serverURI'=>$mapServer['serverName']]);
        //$client = new YesinCarHttpClient(['serverURI'=>'https://test-map.yesincarapi.com']);
        $pathInfo = $mapServer['method']['queryPoints'];
        //$pathInfo = 'route/points';
        $response = $client->get($pathInfo,$clientData,2);

        if($response['code']!=0){
        }else{
            if(empty($response['data']['startPoint'])){
                $response['data'] = $order_points_info;
            }
        }

        $response['data']['is_finished'] = 0;
        if($order->status >= 6){
            $response['data']['is_finished'] = 1;
        }

        return $response['data'];
    }

    public function getPassengerTrajectoryByOrderId($orderId){
        $data = $this->getOrderPeriodTrajectoryInfo($orderId, ['passenger']);
        $trim_data = $data['driver'];

        return $trim_data;
    }

    public function getAllTrajectoryByOrderId($orderId){
        $data = $this->getOrderPeriodTrajectoryInfo($orderId, ['driver','passenger']);
        $trim_data = $data['driver'];
        unset($trim_data['points']);
        $trim_data['driver_points'] = $data['driver']['points'];
        $trim_data['passenger_points'] = $data['passenger']['points'];

        return $trim_data;
    }


    public function getOrderPeriodTrajectoryInfo($orderId, $period){
        $order = Order::findOne($orderId);
        if(!$order){
            throw new UserException('查询失败', 100002);
        }
        $vehicleId = $order->car_id;
        $orderRulePrice = OrderRulePrice::findOne(['order_id'=>$orderId,'category'=>OrderRulePrice::PRICE_TYPE_FORECAST]);
        if(!$orderRulePrice){
            throw new UserException('查询失败', 100003);
        }
        $city = $orderRulePrice->city_code;

        $data = [];
        foreach ($period as $v){
            if($v == 'driver'){
                $startTime = intval(strtotime($order->driver_start_time).'000');
                $endTime = intval(strtotime($order->driver_arrived_time).'000');
            }else{
                $startTime = intval(strtotime($order->receive_passenger_time).'000');
                $endTime = intval(strtotime($order->passenger_getoff_time).'000');
            }
            if($endTime == 0){
                $endTime = time().'000';
            }

            $response = [];
            if(!empty($startTime)){
                $clientData = compact('city','startTime','endTime','vehicleId');
                $log['request_name'] = '获取订单轨迹数据';
                $log['request_params'] = $clientData;
                \Yii::info(json_encode($log, JSON_UNESCAPED_UNICODE), 'process');

                $mapServer = ArrayHelper::getValue(\Yii::$app->params,'api.map');
                $client = new YesinCarHttpClient(['serverURI'=>$mapServer['serverName']]);
                $pathInfo = $mapServer['method']['queryPoints'];
                $response = $client->get($pathInfo,$clientData,2);
                $log['request_name'] = '获取订单轨迹数据';
                $log['request_params'] = $clientData;
                $log['response'] = $response;
                \Yii::info(json_encode($log, JSON_UNESCAPED_UNICODE), 'process');
            }

            $order_points_info['startPoint']['locateTime'] = null;
            $order_points_info['startPoint']['longitude'] = $order->start_longitude;
            $order_points_info['startPoint']['latitude'] = $order->start_latitude;
            $order_points_info['startPoint']['addressName'] = $order->start_address;
            $order_points_info['endPoint']['locateTime'] = null;
            $order_points_info['endPoint']['longitude'] = $order->end_longitude;
            $order_points_info['endPoint']['latitude'] = $order->end_latitude;
            $order_points_info['endPoint']['addressName'] = $order->end_address;
            $order_points_info['points'] = [];
            $order_points_info['pointCount'] = 0;

            if(empty($response['data']['startPoint'])){
                $response['data'] = $order_points_info;
            }

            if(empty($response['data']['startPoint']['addressName'])){
                $response['data']['startPoint']['addressName'] = $order->start_address;
            }
            if(empty($response['data']['endPoint']['addressName'])){
                $response['data']['endPoint']['addressName'] = $order->end_address;
            }

            $response['data']['pickPoint'] = [
                'locateTime'=>$order->pick_up_passenger_time,
                'longitude'=>$order->pick_up_passenger_longitude,
                'latitude'=>$order->pick_up_passenger_latitude,
                'addressName'=>$order->pick_up_passenger_address
            ];

            $response['data']['receivePoint'] = [
                'locateTime'=>$order->receive_passenger_time,
                'longitude'=>$order->receive_passenger_longitude,
                'latitude'=>$order->receive_passenger_latitude,
                'addressName'=>$order->receive_passenger_address
            ];

            $response['data']['getOffPoint'] = [
                'locateTime'=>$order->passenger_getoff_time,
                'longitude'=>$order->passenger_getoff_longitude,
                'latitude'=>$order->passenger_getoff_latitude,
                'addressName'=>$order->passenger_getoff_address
            ];

            $response['data']['is_finished'] = 0;
            if($order->status >= 6){
                $response['data']['is_finished'] = 1;
            }

            $data[$v] = $response['data'];
        }

        return $data;
    }
}
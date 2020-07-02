<?php

namespace console\controllers;

use common\models\Order;
use common\models\FlightNumber;
use yii\console\Controller;
use common\services\CConstant;
use common\api\FlightApi;


class CancelSubscribeFlightController extends Controller
{
    public function actionIndex(){
        $orderTime = time()-1800;
        $results = Order::find()
            ->select("id,passenger_info_id")
            ->andWhere(['or',
                [
                    'and',
                    ['status' =>Order::STATUS_CANCEL],
                    ['service_type' =>CConstant::SERVICE_AIRPORT_PICK_UP],
                    ['>', 'order_start_time', date('Y-m-d H:i:s',$orderTime)]
                ],
                [
                    'and',
                    ['status' =>Order::STATUS_ARRIVED],
                    ['service_type' =>CConstant::SERVICE_AIRPORT_PICK_UP],
                    ['>', 'order_start_time', date('Y-m-d H:i:s',$orderTime)]
                ]
            ])->asArray()->all();

        \Yii::info($results, 'results');
        if($results){
            foreach ($results as $key => $value){
                $flight = FlightNumber::find()
                    ->select("flight_number,flight_date,start_code,end_code,passenger_info_id")
                    ->Where(['is_subscribe' =>FlightNumber::IS_SUBSCRIBE_YES])
                    ->andWhere(['order_id'=>$value['id']])
                    ->andWhere(['passenger_info_id'=>$value['passenger_info_id']])
                    ->asArray()->all();
                \Yii::info($flight, 'flight');
                if($flight){
                    $data= [];
                    foreach ($flight as $k=>$v){
                        $data['flightNo'] = $v['flight_number'];
                        $data['flightDate'] = $v['flight_date'];
                        $data['deptAirportCode'] = $v['start_code'];
                        $data['destAirportCode'] = $v['end_code'];
                        $data['userId'] = $v['passenger_info_id'];
                        \Yii::info($data, 'data');
                        $flightres = new FlightApi();
                        \Yii::info($flightres->cancelSubscribeFlight($data), 'cancelSubscribeFlight');

                        $updata['is_subscribe'] = 0;
                        $condition['passenger_info_id'] = $v['passenger_info_id'];;
                        \Yii::info(FlightNumber::updateFlight($updata, $condition), 'updateFlight');
                    }
                }
            }
        }
    }
}
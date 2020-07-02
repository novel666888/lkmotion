<?php

namespace common\api;

use yii\helpers\ArrayHelper;
use common\services\YesinCarHttpClient;


class StatisticsApi
{
    private $http_client;

    public function __construct()
    {
        $server_url = ArrayHelper::getValue(\Yii::$app->params, 'api.statistics.serverName');
        
        $this->http_client = new YesinCarHttpClient([
            'serverURI' => $server_url
        ]);
    }

    /**
     * 用户统计
     *
     * @param [type] $begin_time
     * @param [type] $end_time
     * @param integer $check
     * @param integer $equipment
     * @param integer $order_type
     * @param integer $period
     * @return void
     */
    public function userStatistics($begin_time, $end_time, $check = 1, $equipment = 0, $period = 2)
    {
        $data = [
            'check' => $check,
            'equipment' => $equipment,
            'period' => $period,
            'begin' => $begin_time,
            'end' => $end_time
        ];

        $method_path = ArrayHelper::getValue(\Yii::$app->params, 'api.statistics.method.userStatistics');

        $result = $this->http_client->post($method_path, $data);
        \Yii::info(json_encode($result, JSON_UNESCAPED_UNICODE), 'StatisticsApi/userStatistics return data');

        if ($result['code'] === 0) {
            return $result['data'];
        }
        return false;
    }

    /**
     * 订单统计
     *
     * @param [type] $begin_time
     * @param [type] $end_time
     * @param integer $check
     * @param integer $order_type
     * @param integer $period
     * @return void
     */
    public function orderStatistics($begin_time, $end_time, $check = 1, $order_type = 0, $period = 1)
    {
        $data = [
            'check' => $check,
            'type' => $order_type,
            'period' => $period,
            'begin' => $begin_time,
            'end' => $end_time
        ];

        $method_path = ArrayHelper::getValue(\Yii::$app->params, 'api.statistics.method.orderStatistics');

        $result = $this->http_client->post($method_path, $data);
        
        \Yii::info(json_encode($result, JSON_UNESCAPED_UNICODE), 'StatisticsApi/orderStatistics return data');

        if ($result['code'] === 0) {
            return $result['data'];
        }
        return false;
    }

    /**
     * 优惠券统计
     *
     * @param [type] $begin_time
     * @param [type] $end_time
     * @param integer $period
     * @return void
     */
    public function couponStatistics($begin_time, $end_time, $period = 1)
    {
        $data = [
            'period' => $period,
            'begin' => $begin_time,
            'end' => $end_time
        ];

        $method_path = ArrayHelper::getValue(\Yii::$app->params, 'api.statistics.method.counponStatistics');

        $result = $this->http_client->post($method_path, $data);
        \Yii::info(json_encode($result, JSON_UNESCAPED_UNICODE), 'StatisticsApi/couponStatistics return data');
 
        if ($result['code'] === 0) {
            return $result['data'];
        }
        return false;
    }

    /**
     * 车辆统计
     *
     * @param [type] $begin_time
     * @param [type] $end_time
     * @param integer $period
     * @return void
     */
    public function carStatistics($begin_time, $end_time, $period = 1)
    {
        $data = [
            'period' => $period,
            'begin' => $begin_time,
            'end' => $end_time
        ];

        $method_path = ArrayHelper::getValue(\Yii::$app->params, 'api.statistics.method.carStatistics');

        $result = $this->http_client->post($method_path, $data);
        \Yii::info(json_encode($result, JSON_UNESCAPED_UNICODE), 'StatisticsApi/carStatistics return data');

        if ($result['code'] === 0) {
            return $result['data'];
        }
        return false;
    }
}
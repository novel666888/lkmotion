<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/16
 * Time: 11:08
 */

namespace passenger\modules\order\controllers;

use common\controllers\BaseController;
use common\models\CarLevel;
use common\util\Json;
use yii\httpclient\Client;

class DefaultController extends BaseController
{
    public function actionIndex()
    {
        $client = new Client();
        var_dump($client);
        exit;
        return Json::success();
    }


    public function actionForecastCost()
    {
        /**
         *  "startLng":116.414581,//起点经度
         * "startLat":39.955432,//起点纬度
         * "endLng":116.431649,//终点经度
         * "endLat":39.965498,//终点纬度
         * "startTime":1528251071000,//订单开始时间
         * //"phoneNum":"13620683679",//手机号
         * "city":"330100",//城市码
         * "carLevelId":1,//车型
         * "serviceType":2 //服务类型
         */
        $request  = $this->getRequest();
        $postData = $request->post();
        $regData  = [
            'cityCode'        => 330100,
            'cityName'        => '西安市',
            'serviceTypeId'   => 0,
            'serviceTypeName' => '预估订单',
            'channelId'       => 1,
            'channelName'     => '渠道',
            'carTypeId'       => 1,
            "carTypeName"     => "普通",
            'passengerId'     => 309,
            'passengerPhone'  => '17710662549',
            'deviceCode'      => 'A0001',
            'startLongitude'  => 116.3395500183,
            'startLatitude'   => 39.9400145395,
            'startAddress'    => '动物园',
            'endLongitude'    => 116.3544845581,
            'endLatitude'     => 39.9361975779,
            'endAddress'      => '北京大学人民医院',
            'orderType'       => 1,
            'otherName'       => '他人姓名',
            'otherPhone'      => 13566788989,
            'userLongitude'   => 116.3419103622,
            'userLatitude'    => 39.9396196913,
            'orderStartTime'  => 1534291688000,
            'source'          => 1
        ];
        /*if(!isset($postData['phoneNum'])){
            $postData['phoneNum'] = '13002530034';
        }*/

        $client      = new Client();
        $httpRequest = $client->createRequest()
            ->setFormat(Client::FORMAT_JSON)
            ->setMethod('post')
            ->setUrl('http://192.8.19.103/order/create_update')
            ->setData($regData);
        $response    = $httpRequest->send();
        return $this->asJson($response->getData());

    }



    /**
     * @return \yii\web\Response
     */

    public function actionCallingCar()
    {

        $data        = ['orderId' => 24, 'status' => 1];
        $client      = new Client();
        $httpRequest = $client->createRequest()
            ->setFormat(Client::FORMAT_JSON)
            ->setMethod('post')
            ->setUrl('http://192.8.19.103/order/create_update')
            ->setData($data);
        $response    = $httpRequest->send();
        return $this->asJson($response->getData());

    }

    /**
     * @return array
     */

    public function actionCanReserveCar()
    {
        return Json::success();
    }

}
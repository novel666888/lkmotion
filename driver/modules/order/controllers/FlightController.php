<?php

namespace driver\modules\order\controllers;

use common\controllers\BaseController;
use common\logic\DriverFlightLogic;
use common\util\Common;
use common\util\Json;


/**
 * Flight controller
 */
class FlightController extends BaseController
{
    /**
     * 航班动态
     * @param  int  $orderId 订单号
     * @return array
     */
    public function actionGetFlightInfo()
    {
        $request = $this->getRequest();
        $orderId = trim($request->post('orderId'));
        \Yii::info($orderId, 'orderId');

        if(!$orderId) {
            return Json::message('参数为空或不支持的数据类型');
        }

        $flight = new DriverFlightLogic();
        $FlightInfo = $flight->getFlightInfo($orderId);

        \Yii::info($FlightInfo, 'FlightInfo');
        return Json::success(Common::key2lowerCamel($FlightInfo));
    }


}
